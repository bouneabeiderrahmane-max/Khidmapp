<?php

namespace Tests\Feature\Notification;

use App\Models\NotificationLog;
use App\Models\User;
use App\Support\NotificationChannel;
use App\Support\NotificationStatus;
use App\Support\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function log(User $user): NotificationLog
    {
        return NotificationLog::query()->create([
            'user_id' => $user->id, 'channel' => NotificationChannel::PUSH, 'locale' => 'fr',
            'template_key' => NotificationTemplate::DELIVERED, 'title' => 'Commande livrée', 'body' => 'Body',
            'status' => NotificationStatus::SENT, 'sent_at' => now(),
        ]);
    }

    public function test_a_client_only_sees_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        $this->log($user);
        $this->log($stranger);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/notifications')->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_notifications_require_authentication(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }
}
