<?php

namespace Tests\Feature\Admin;

use App\Models\Complaint;
use App\Models\Order;
use App\Models\User;
use App\Support\ComplaintCategory;
use App\Support\ComplaintStatus;
use App\Support\NotificationTemplate;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminComplaintControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->assignRole(Roles::SERVICE_CLIENT);

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);
    }

    private function makeComplaint(string $status = ComplaintStatus::OUVERTE): Complaint
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::DELIVERED, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $complaint = Complaint::query()->create([
            'order_id' => $order->id, 'user_id' => $this->client->id,
            'category' => ComplaintCategory::AUTRE, 'status' => $status,
        ]);

        $complaint->messages()->create(['sender_type' => 'client', 'sender_id' => $this->client->id, 'message' => 'Bonjour.']);

        return $complaint;
    }

    public function test_a_client_is_forbidden_from_every_admin_complaint_endpoint(): void
    {
        $complaint = $this->makeComplaint();

        $this->actingAs($this->client, 'api')->getJson('/api/v1/admin/complaints')->assertForbidden();
        $this->actingAs($this->client, 'api')->patchJson("/api/v1/admin/complaints/{$complaint->id}/status", ['status' => ComplaintStatus::EN_COURS])->assertForbidden();
    }

    public function test_service_client_can_list_and_filter_complaints(): void
    {
        $this->makeComplaint(ComplaintStatus::OUVERTE);
        $this->makeComplaint(ComplaintStatus::CLOTUREE);

        $response = $this->actingAs($this->agent, 'api')
            ->getJson('/api/v1/admin/complaints?status='.ComplaintStatus::CLOTUREE)
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_an_agent_reply_takes_the_complaint_in_charge_and_notifies_the_client(): void
    {
        $complaint = $this->makeComplaint(ComplaintStatus::OUVERTE);

        $this->actingAs($this->agent, 'api')
            ->postJson("/api/v1/admin/complaints/{$complaint->id}/messages", ['message' => 'Nous examinons votre demande.'])
            ->assertCreated()
            ->assertJsonPath('data.sender_type', 'service_client');

        $this->assertSame(ComplaintStatus::EN_COURS, $complaint->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->client->id,
            'template_key' => NotificationTemplate::COMPLAINT_REPLY,
        ]);
    }

    public function test_updating_to_a_disallowed_status_returns_a_422(): void
    {
        $complaint = $this->makeComplaint(ComplaintStatus::OUVERTE);

        $this->actingAs($this->agent, 'api')
            ->patchJson("/api/v1/admin/complaints/{$complaint->id}/status", ['status' => ComplaintStatus::RESOLUE])
            ->assertStatus(422);
    }

    public function test_a_valid_status_update_succeeds(): void
    {
        $complaint = $this->makeComplaint(ComplaintStatus::EN_COURS);

        $this->actingAs($this->agent, 'api')
            ->patchJson("/api/v1/admin/complaints/{$complaint->id}/status", ['status' => ComplaintStatus::RESOLUE])
            ->assertOk()
            ->assertJsonPath('data.status', ComplaintStatus::RESOLUE);
    }
}
