<?php

namespace Tests\Feature\Complaint;

use App\Models\Order;
use App\Models\User;
use App\Support\ComplaintCategory;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = User::factory()->create();
        Storage::fake('local');
    }

    private function makeOrder(?User $owner = null): Order
    {
        return Order::query()->create([
            'user_id' => ($owner ?? $this->client)->id, 'status' => OrderStatus::DELIVERED, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
    }

    public function test_a_client_can_open_a_complaint_on_their_own_order(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/complaints', [
                'order_id' => $order->id,
                'category' => ComplaintCategory::PRODUIT_NON_CONFORME,
                'message' => 'La couleur reçue ne correspond pas.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'ouverte')
            ->assertJsonCount(1, 'data.messages');
    }

    public function test_a_client_cannot_open_a_complaint_on_another_clients_order(): void
    {
        $stranger = User::factory()->create();
        $order = $this->makeOrder($stranger);

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/complaints', [
                'order_id' => $order->id,
                'category' => ComplaintCategory::AUTRE,
                'message' => 'Test.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_a_complaint_can_be_opened_with_a_photo_attachment(): void
    {
        $order = $this->makeOrder();

        $response = $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/complaints', [
                'order_id' => $order->id,
                'category' => ComplaintCategory::DOMMAGE,
                'message' => 'Emballage endommagé.',
                'attachments' => [UploadedFile::fake()->image('photo.jpg')],
            ])
            ->assertCreated();

        $this->assertCount(1, $response->json('data.messages.0.attachment_urls'));
    }

    public function test_a_client_only_sees_their_own_complaints(): void
    {
        $order = $this->makeOrder();
        $this->actingAs($this->client, 'api')->postJson('/api/v1/complaints', [
            'order_id' => $order->id, 'category' => ComplaintCategory::AUTRE, 'message' => 'Test.',
        ]);

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')->getJson('/api/v1/complaints')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->client, 'api')->getJson('/api/v1/complaints')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_client_cannot_view_another_clients_complaint(): void
    {
        $order = $this->makeOrder();
        $response = $this->actingAs($this->client, 'api')->postJson('/api/v1/complaints', [
            'order_id' => $order->id, 'category' => ComplaintCategory::AUTRE, 'message' => 'Test.',
        ]);
        $complaintId = $response->json('data.id');

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')->getJson("/api/v1/complaints/{$complaintId}")->assertForbidden();
    }

    public function test_a_client_can_add_a_follow_up_message(): void
    {
        $order = $this->makeOrder();
        $response = $this->actingAs($this->client, 'api')->postJson('/api/v1/complaints', [
            'order_id' => $order->id, 'category' => ComplaintCategory::AUTRE, 'message' => 'Test.',
        ]);
        $complaintId = $response->json('data.id');

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/complaints/{$complaintId}/messages", ['message' => 'Un complément.'])
            ->assertCreated()
            ->assertJsonPath('data.sender_type', 'client');
    }

    public function test_a_message_requires_text_or_an_attachment(): void
    {
        $order = $this->makeOrder();
        $response = $this->actingAs($this->client, 'api')->postJson('/api/v1/complaints', [
            'order_id' => $order->id, 'category' => ComplaintCategory::AUTRE, 'message' => 'Test.',
        ]);
        $complaintId = $response->json('data.id');

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/complaints/{$complaintId}/messages", [])
            ->assertStatus(422);
    }
}
