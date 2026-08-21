<?php

namespace Tests\Feature\Order;

use App\Models\Boutique;
use App\Models\Cart;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\PaymentMethod;
use App\Support\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 0, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 200]);

        $this->client = User::factory()->create();
    }

    private function variant(float $priceEur = 50): ProductVariant
    {
        $product = Product::factory()->create(['boutique_id' => Boutique::factory(), 'base_price_eur' => $priceEur]);

        return $product->variants()->create(['external_variant_ref' => 'v-'.$product->id, 'price_eur' => $priceEur, 'stock_status' => 'in_stock']);
    }

    private function addressId(): int
    {
        return $this->client->addresses()->create(['label' => 'Domicile', 'city' => 'Nouakchott'])->id;
    }

    public function test_checkout_fails_when_the_cart_is_empty(): void
    {
        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/orders', ['address_id' => $this->addressId(), 'payment_method' => 'bankily'])
            ->assertStatus(422);
    }

    public function test_checkout_rejects_an_address_belonging_to_another_user(): void
    {
        $variant = $this->variant();
        $cart = Cart::query()->create(['user_id' => $this->client->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $stranger = User::factory()->create();
        $strangerAddressId = $stranger->addresses()->create(['label' => 'Domicile', 'city' => 'Nouakchott'])->id;

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/orders', ['address_id' => $strangerAddressId, 'payment_method' => 'bankily'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address_id']);
    }

    public function test_checkout_creates_an_order_from_the_cart_and_empties_it(): void
    {
        $variant = $this->variant(50);
        $cart = Cart::query()->create(['user_id' => $this->client->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 2]);

        $response = $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/orders', ['address_id' => $this->addressId(), 'payment_method' => 'bankily'])
            ->assertCreated();

        $response
            ->assertJsonPath('data.status', OrderStatus::AWAITING_PAYMENT)
            ->assertJsonPath('data.subtotal_mru', '1000.00')
            ->assertJsonPath('data.delivery_fee_mru', '200.00')
            // Coût de gestion 5% sur (sous-total + livraison) : (1000+200)*5% = 60.
            ->assertJsonPath('data.management_fee_mru', '60.00')
            ->assertJsonPath('data.total_mru', '1260.00')
            ->assertJsonPath('data.is_manual_order', false)
            ->assertJsonCount(1, 'data.items');

        $this->assertSame(0, $cart->fresh()->items()->count());
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_a_client_can_only_see_their_own_orders(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'bankily',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')->getJson("/api/v1/orders/{$order->id}")->assertForbidden();
        $this->actingAs($this->client, 'api')->getJson("/api/v1/orders/{$order->id}")->assertOk();
    }

    public function test_a_client_can_self_cancel_within_the_free_window(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'bankily',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/orders/{$order->id}/cancel", ['reason' => 'Changement d\'avis'])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::CANCELLED)
            ->assertJsonPath('data.cancellation_fee_applicable', false);
    }

    public function test_a_client_cannot_self_cancel_past_the_free_window(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::SHIPPED_BY_BOUTIQUE, 'payment_method' => 'bankily',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertStatus(422);

        $this->assertSame(OrderStatus::SHIPPED_BY_BOUTIQUE, $order->fresh()->status);
    }

    public function test_a_client_cannot_cancel_another_clients_order(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'bankily',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertForbidden();
    }

    public function test_checkout_is_blocked_while_a_manual_payment_proof_is_pending(): void
    {
        $existingOrder = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
        Payment::query()->create([
            'order_id' => $existingOrder->id, 'submitted_by' => $this->client->id, 'method' => PaymentMethod::MANUAL,
            'status' => PaymentStatus::PENDING, 'amount_mru' => 300, 'proof_file_path' => 'payment-proofs/x.jpg', 'initiated_at' => now(),
        ]);

        $variant = $this->variant();
        $cart = Cart::query()->create(['user_id' => $this->client->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/orders', ['address_id' => $this->addressId(), 'payment_method' => 'bankily'])
            ->assertStatus(422);
    }

    public function test_checkout_succeeds_once_the_pending_proof_has_been_reviewed(): void
    {
        $existingOrder = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
        Payment::query()->create([
            'order_id' => $existingOrder->id, 'submitted_by' => $this->client->id, 'method' => PaymentMethod::MANUAL,
            'status' => PaymentStatus::REJECTED, 'amount_mru' => 300, 'proof_file_path' => 'payment-proofs/x.jpg', 'initiated_at' => now(),
        ]);

        $variant = $this->variant();
        $cart = Cart::query()->create(['user_id' => $this->client->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/orders', ['address_id' => $this->addressId(), 'payment_method' => 'bankily'])
            ->assertCreated();
    }
}
