<?php

namespace Tests\Feature\Admin;

use App\Models\Boutique;
use App\Models\CustomOrderRequest;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Order;
use App\Models\User;
use App\Support\CustomOrderRequestStatus;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomOrderRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 200]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);
    }

    private function pendingRequest(float $estimatedPriceEur = 30, int $quantity = 2, ?int $boutiqueId = null): CustomOrderRequest
    {
        $address = $this->client->addresses()->create(['label' => 'Domicile', 'city' => 'Nouakchott']);

        $request = CustomOrderRequest::query()->create([
            'user_id' => $this->client->id,
            'status' => CustomOrderRequestStatus::PENDING,
            'address_id' => $address->id,
            'payment_method' => 'bankily',
        ]);

        $request->items()->create([
            'boutique_id' => $boutiqueId,
            'product_url' => 'https://boutique-exemple.es/produit/123',
            'quantity' => $quantity,
            'estimated_price_eur' => $estimatedPriceEur,
            'notes' => 'Taille M',
        ]);

        return $request;
    }

    public function test_a_client_is_forbidden_from_every_admin_custom_order_endpoint(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->client, 'api')->getJson('/api/v1/admin/custom-order-requests')->assertForbidden();
        $this->actingAs($this->client, 'api')->postJson("/api/v1/admin/custom-order-requests/{$request->id}/approve")->assertForbidden();
    }

    public function test_approving_a_request_creates_an_order_with_the_margin_engine_applied(): void
    {
        // 30 EUR * 10 (taux) = 300 MRU, +20% de marge = 360 MRU/unité, x2 = 720 MRU sous-total,
        // + 200 MRU livraison = 920, + coût de gestion 5% sur (720+200)=46 => 966 MRU.
        $request = $this->pendingRequest(estimatedPriceEur: 30, quantity: 2);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/custom-order-requests/{$request->id}/approve", ['note' => 'Faisable'])
            ->assertOk();

        $response
            ->assertJsonPath('data.status', CustomOrderRequestStatus::APPROVED)
            ->assertJsonPath('data.order.status', OrderStatus::AWAITING_PAYMENT)
            ->assertJsonPath('data.order.subtotal_mru', '720.00')
            ->assertJsonPath('data.order.management_fee_mru', '46.00')
            ->assertJsonPath('data.order.total_mru', '966.00');

        $this->assertDatabaseCount('orders', 1);
        $order = Order::query()->first();
        $this->assertSame($this->client->id, $order->user_id);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(CustomOrderRequestStatus::APPROVED, $request->fresh()->status);
        $this->assertSame($order->id, $request->fresh()->order_id);
        $this->assertSame($this->admin->id, $request->fresh()->reviewed_by);
    }

    public function test_approving_applies_the_boutique_margin_over_the_global_default(): void
    {
        $boutique = Boutique::factory()->create(['default_margin_percent' => 0]);
        MarginRule::query()->create(['scope_type' => 'boutique', 'scope_id' => $boutique->id, 'percent' => 50, 'effective_at' => now()->subDay()]);

        // 10 EUR * 10 = 100 MRU, +50% boutique = 150 MRU, x1 = 150, + 200 livraison = 350,
        // + coût de gestion 5% sur (150+200)=17.5 => 367.5.
        $request = $this->pendingRequest(estimatedPriceEur: 10, quantity: 1, boutiqueId: $boutique->id);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/custom-order-requests/{$request->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.order.subtotal_mru', '150.00')
            ->assertJsonPath('data.order.management_fee_mru', '17.50')
            ->assertJsonPath('data.order.total_mru', '367.50');
    }

    public function test_rejecting_a_request_requires_a_reason_and_creates_no_order(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/custom-order-requests/{$request->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/custom-order-requests/{$request->id}/reject", ['reason' => 'Produit indisponible'])
            ->assertOk()
            ->assertJsonPath('data.status', CustomOrderRequestStatus::REJECTED)
            ->assertJsonPath('data.admin_note', 'Produit indisponible');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_request_already_reviewed_cannot_be_approved_or_rejected_again(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->admin, 'api')->postJson("/api/v1/admin/custom-order-requests/{$request->id}/approve")->assertOk();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/custom-order-requests/{$request->id}/reject", ['reason' => 'Trop tard'])
            ->assertStatus(422);
    }

    public function test_index_can_filter_by_status(): void
    {
        $this->pendingRequest();
        $approved = $this->pendingRequest();
        $this->actingAs($this->admin, 'api')->postJson("/api/v1/admin/custom-order-requests/{$approved->id}/approve")->assertOk();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/custom-order-requests?status='.CustomOrderRequestStatus::PENDING)
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }
}
