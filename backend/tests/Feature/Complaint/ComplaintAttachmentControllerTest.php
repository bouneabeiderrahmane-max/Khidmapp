<?php

namespace Tests\Feature\Complaint;

use App\Models\Order;
use App\Models\User;
use App\Support\ComplaintCategory;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);

        $this->agent = User::factory()->create();
        $this->agent->assignRole(Roles::SERVICE_CLIENT);
    }

    private function complaintMessageId(): array
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::DELIVERED, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $response = $this->actingAs($this->client, 'api')->postJson('/api/v1/complaints', [
            'order_id' => $order->id,
            'category' => ComplaintCategory::DOMMAGE,
            'message' => 'Endommagé.',
            'attachments' => [UploadedFile::fake()->image('photo.jpg')],
        ]);

        return [$response->json('data.id'), $response->json('data.messages.0.id')];
    }

    public function test_the_owner_can_download_the_attachment(): void
    {
        [, $messageId] = $this->complaintMessageId();

        $this->actingAs($this->client, 'api')
            ->get("/api/v1/complaint-messages/{$messageId}/attachments/0")
            ->assertOk();
    }

    public function test_an_agent_with_permission_can_download_the_attachment(): void
    {
        [, $messageId] = $this->complaintMessageId();

        $this->actingAs($this->agent, 'api')
            ->get("/api/v1/complaint-messages/{$messageId}/attachments/0")
            ->assertOk();
    }

    public function test_a_stranger_cannot_download_the_attachment(): void
    {
        [, $messageId] = $this->complaintMessageId();
        $stranger = User::factory()->create();
        $stranger->assignRole(Roles::CLIENT);

        $this->actingAs($stranger, 'api')
            ->get("/api/v1/complaint-messages/{$messageId}/attachments/0")
            ->assertForbidden();
    }
}
