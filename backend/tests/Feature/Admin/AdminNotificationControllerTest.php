<?php

namespace Tests\Feature\Admin;

use App\Models\NotificationLog;
use App\Models\User;
use App\Support\NotificationChannel;
use App\Support\NotificationStatus;
use App\Support\NotificationTemplate;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationControllerTest extends TestCase
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

    private function log(User $user, string $channel = NotificationChannel::PUSH, string $status = NotificationStatus::SENT): NotificationLog
    {
        return NotificationLog::query()->create([
            'user_id' => $user->id, 'channel' => $channel, 'locale' => 'fr',
            'template_key' => NotificationTemplate::DELIVERED, 'title' => 'Commande livrée', 'body' => 'Body',
            'status' => $status, 'sent_at' => $status === NotificationStatus::SENT ? now() : null,
        ]);
    }

    public function test_a_client_is_forbidden(): void
    {
        $this->actingAs($this->client, 'api')->getJson('/api/v1/admin/notifications')->assertForbidden();
    }

    public function test_service_client_can_list_all_notifications(): void
    {
        $this->log($this->client);
        $this->log($this->agent);

        $response = $this->actingAs($this->agent, 'api')->getJson('/api/v1/admin/notifications')->assertOk();

        $this->assertCount(2, $response->json('data'));
    }

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $this->log($this->client, status: NotificationStatus::SENT);
        $this->log($this->client, status: NotificationStatus::FAILED);

        $response = $this->actingAs($this->agent, 'api')
            ->getJson('/api/v1/admin/notifications?status='.NotificationStatus::FAILED)
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame(NotificationStatus::FAILED, $response->json('data.0.status'));
    }
}
