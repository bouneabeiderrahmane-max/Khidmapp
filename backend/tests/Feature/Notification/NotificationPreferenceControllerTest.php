<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_default_to_enabled_for_every_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/notification-preferences')
            ->assertOk()
            ->assertJson(['data' => ['push' => true, 'sms' => true, 'email' => true]]);
    }

    public function test_a_client_can_disable_a_single_channel_without_affecting_the_others(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->putJson('/api/v1/notification-preferences', ['sms' => false])
            ->assertOk()
            ->assertJson(['data' => ['push' => true, 'sms' => false, 'email' => true]]);
    }

    public function test_updating_preferences_requires_authentication(): void
    {
        $this->putJson('/api/v1/notification-preferences', ['push' => false])->assertUnauthorized();
    }
}
