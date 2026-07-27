<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = User::factory()->create();
        Storage::fake('local');
    }

    private function makeOrder(string $method, string $status = OrderStatus::AWAITING_PAYMENT, ?User $owner = null): Order
    {
        return Order::query()->create([
            'user_id' => ($owner ?? $this->client)->id, 'status' => $status, 'payment_method' => $method,
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
    }

    public function test_a_client_can_initiate_a_bankily_payment_for_their_own_order(): void
    {
        $order = $this->makeOrder('bankily');

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payments/bankily/initiate")
            ->assertCreated()
            ->assertJsonPath('data.method', 'bankily')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonStructure(['data' => ['external_reference']]);
    }

    public function test_a_client_cannot_initiate_payment_for_another_clients_order(): void
    {
        $stranger = User::factory()->create();
        $order = $this->makeOrder('bankily', owner: $stranger);

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payments/bankily/initiate")
            ->assertForbidden();
    }

    public function test_initiating_bankily_fails_when_the_order_uses_manual_payment(): void
    {
        $order = $this->makeOrder('manual');

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payments/bankily/initiate")
            ->assertStatus(422);
    }

    public function test_a_client_can_submit_a_manual_payment_proof(): void
    {
        $order = $this->makeOrder('manual');

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment-proof", [
                'proof' => UploadedFile::fake()->image('recu.jpg'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.method', 'manual')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_submitting_a_proof_requires_an_image_file(): void
    {
        $order = $this->makeOrder('manual');

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment-proof", [
                'proof' => UploadedFile::fake()->create('recu.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['proof']);
    }

    public function test_a_client_cannot_submit_a_proof_for_another_clients_order(): void
    {
        $stranger = User::factory()->create();
        $order = $this->makeOrder('manual', owner: $stranger);

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment-proof", ['proof' => UploadedFile::fake()->image('recu.jpg')])
            ->assertForbidden();
    }
}
