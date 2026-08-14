<?php

namespace Tests\Feature\CustomOrder;

use App\Models\CustomOrderRequest;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\User;
use App\Support\CustomOrderRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomOrderRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 200]);

        $this->client = User::factory()->create();
    }

    private function addressId(): int
    {
        return $this->client->addresses()->create(['label' => 'Domicile', 'city' => 'Nouakchott'])->id;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'address_id' => $this->addressId(),
            'payment_method' => 'bankily',
            'items' => [
                [
                    'product_url' => 'https://boutique-exemple.es/produit/123',
                    'quantity' => 2,
                    'estimated_price_eur' => 30,
                    'notes' => 'Taille M, couleur bleue',
                ],
            ],
        ], $overrides);
    }

    public function test_a_client_can_submit_a_custom_order_request_without_creating_an_order(): void
    {
        $response = $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/custom-order-requests', $this->validPayload())
            ->assertCreated();

        $response
            ->assertJsonPath('data.status', CustomOrderRequestStatus::PENDING)
            ->assertJsonPath('data.stage', 'review')
            ->assertJsonMissingPath('data.order')
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseCount('custom_order_requests', 1);
        $this->assertDatabaseCount('custom_order_items', 1);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_submission_rejects_an_address_belonging_to_another_user(): void
    {
        $stranger = User::factory()->create();
        $strangerAddressId = $stranger->addresses()->create(['label' => 'Domicile', 'city' => 'Nouakchott'])->id;

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/custom-order-requests', $this->validPayload(['address_id' => $strangerAddressId]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address_id']);
    }

    public function test_submission_requires_at_least_one_item(): void
    {
        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/custom-order-requests', $this->validPayload(['items' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_a_client_can_only_see_their_own_custom_order_requests(): void
    {
        $this->actingAs($this->client, 'api')->postJson('/api/v1/custom-order-requests', $this->validPayload())->assertCreated();
        $requestId = CustomOrderRequest::query()->first()->id;

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')->getJson("/api/v1/custom-order-requests/{$requestId}")->assertForbidden();
        $this->actingAs($this->client, 'api')->getJson("/api/v1/custom-order-requests/{$requestId}")->assertOk();
    }
}
