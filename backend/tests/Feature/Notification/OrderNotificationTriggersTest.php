<?php

namespace Tests\Feature\Notification;

use App\Models\Boutique;
use App\Models\Cart;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\NotificationTemplate;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie que les déclencheurs de notification (CDC 8.7) sont bien câblés
 * de bout en bout via les vrais parcours HTTP, pas seulement via
 * NotificationService pris isolément.
 */
class OrderNotificationTriggersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 0, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 200]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);
    }

    private function variant(float $priceEur = 50): ProductVariant
    {
        $product = Product::factory()->create(['boutique_id' => Boutique::factory(), 'base_price_eur' => $priceEur]);

        return $product->variants()->create(['external_variant_ref' => 'v-'.$product->id, 'price_eur' => $priceEur, 'stock_status' => 'in_stock']);
    }

    public function test_checkout_triggers_the_order_confirmed_notification(): void
    {
        $variant = $this->variant();
        $addressId = $this->client->addresses()->create(['label' => 'Domicile', 'city' => 'Nouakchott'])->id;
        $cart = Cart::query()->create(['user_id' => $this->client->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/orders', ['address_id' => $addressId, 'payment_method' => 'bankily'])
            ->assertCreated();

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->client->id,
            'template_key' => NotificationTemplate::ORDER_CONFIRMED,
        ]);
    }

    public function test_a_status_transition_triggers_the_matching_notification(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => OrderStatus::PAYMENT_VALIDATED])
            ->assertOk();

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->client->id,
            'template_key' => NotificationTemplate::PAYMENT_VALIDATED,
        ]);
    }

    public function test_a_non_notifiable_status_transition_does_not_create_a_notification(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::PAYMENT_VALIDATED, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        // achat_en_cours n'est pas dans la liste des 10 événements notifiés (8.7).
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => OrderStatus::PURCHASING])
            ->assertOk();

        $this->assertSame(0, NotificationLog::query()->where('user_id', $this->client->id)->count());
    }

    public function test_a_non_conforming_quality_control_report_triggers_a_notification(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::QUALITY_CONTROL, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
        $product = Product::factory()->create(['boutique_id' => Boutique::factory()]);
        $item = $order->items()->create([
            'product_variant_id' => null, 'boutique_id' => $product->boutique_id,
            'product_name_snapshot' => $product->name, 'quantity' => 1,
            'unit_price_eur' => 10, 'unit_price_mru_snapshot' => 100,
            'margin_percent_snapshot' => 0, 'margin_source_snapshot' => 'global',
        ]);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/items/{$item->id}/quality-control", [
                'is_conforme' => false,
                'notes' => 'Article endommagé.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->client->id,
            'template_key' => NotificationTemplate::QUALITY_CONTROL_ANOMALY,
        ]);
        $this->assertDatabaseHas('complaints', [
            'order_id' => $order->id,
            'user_id' => $this->client->id,
            'category' => 'produit_non_conforme',
            'status' => 'ouverte',
        ]);
    }

    public function test_a_conforming_quality_control_report_does_not_trigger_a_notification(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::QUALITY_CONTROL, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
        $product = Product::factory()->create(['boutique_id' => Boutique::factory()]);
        $item = $order->items()->create([
            'product_variant_id' => null, 'boutique_id' => $product->boutique_id,
            'product_name_snapshot' => $product->name, 'quantity' => 1,
            'unit_price_eur' => 10, 'unit_price_mru_snapshot' => 100,
            'margin_percent_snapshot' => 0, 'margin_source_snapshot' => 'global',
        ]);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/items/{$item->id}/quality-control", ['is_conforme' => true])
            ->assertCreated();

        $this->assertSame(0, NotificationLog::query()->where('template_key', NotificationTemplate::QUALITY_CONTROL_ANOMALY)->count());
        $this->assertDatabaseCount('complaints', 0);
    }
}
