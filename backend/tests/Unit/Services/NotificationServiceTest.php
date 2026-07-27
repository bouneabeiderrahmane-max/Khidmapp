<?php

namespace Tests\Unit\Services;

use App\Mail\NotificationMail;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Support\NotificationChannel;
use App\Support\NotificationStatus;
use App\Support\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationService::class);
    }

    public function test_a_non_critical_template_sends_via_push_by_default(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->service->notify($user, NotificationTemplate::RECEIVED_MADRID, ['order_id' => 42]);

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $user->id,
            'channel' => NotificationChannel::PUSH,
            'template_key' => NotificationTemplate::RECEIVED_MADRID,
            'status' => NotificationStatus::SENT,
        ]);
        $this->assertSame(1, NotificationLog::query()->count());
    }

    public function test_disabling_push_skips_it_for_a_non_critical_template(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->notificationPreferences()->create(['channel' => NotificationChannel::PUSH, 'enabled' => false]);

        $this->service->notify($user, NotificationTemplate::RECEIVED_MADRID, ['order_id' => 42]);

        $this->assertSame(0, NotificationLog::query()->count());
    }

    public function test_a_critical_template_forces_push_when_every_channel_is_disabled(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        foreach (NotificationChannel::all() as $channel) {
            $user->notificationPreferences()->create(['channel' => $channel, 'enabled' => false]);
        }

        $this->service->notify($user, NotificationTemplate::PAYMENT_VALIDATED, ['order_id' => 42]);

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $user->id,
            'channel' => NotificationChannel::PUSH,
            'template_key' => NotificationTemplate::PAYMENT_VALIDATED,
        ]);
        $this->assertSame(1, NotificationLog::query()->where('user_id', $user->id)->count());
    }

    public function test_a_critical_template_uses_whatever_channel_remains_enabled(): void
    {
        // Push désactivé mais SMS actif : le message atteint quand même le
        // client (8.7.2 exige "au moins un canal", pas spécifiquement push).
        $user = User::factory()->create(['locale' => 'fr']);
        $user->notificationPreferences()->create(['channel' => NotificationChannel::PUSH, 'enabled' => false]);

        $this->service->notify($user, NotificationTemplate::PAYMENT_VALIDATED, ['order_id' => 42]);

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $user->id,
            'channel' => NotificationChannel::SMS,
            'template_key' => NotificationTemplate::PAYMENT_VALIDATED,
        ]);
        $this->assertDatabaseMissing('notification_logs', ['user_id' => $user->id, 'channel' => NotificationChannel::PUSH]);
    }

    public function test_sms_is_only_attempted_for_sms_eligible_templates(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->service->notify($user, NotificationTemplate::PAYMENT_VALIDATED, ['order_id' => 42]);
        $this->assertDatabaseHas('notification_logs', ['user_id' => $user->id, 'channel' => NotificationChannel::SMS]);

        NotificationLog::query()->delete();

        // received_madrid n'est pas éligible SMS (8.7.1 : uniquement "paiement, livraison").
        $this->service->notify($user, NotificationTemplate::RECEIVED_MADRID, ['order_id' => 42]);
        $this->assertDatabaseMissing('notification_logs', ['user_id' => $user->id, 'channel' => NotificationChannel::SMS]);
    }

    public function test_email_is_only_sent_for_the_order_confirmation_template(): void
    {
        Mail::fake();
        $user = User::factory()->create(['locale' => 'fr', 'email' => 'client@example.test']);

        $this->service->notify($user, NotificationTemplate::ORDER_CONFIRMED, ['order_id' => 42, 'total' => 1500]);
        Mail::assertSent(NotificationMail::class, 1);

        $this->service->notify($user, NotificationTemplate::DELIVERED, ['order_id' => 42]);
        Mail::assertSent(NotificationMail::class, 1);
    }

    public function test_a_channel_failure_is_logged_without_blocking_the_others(): void
    {
        // Sans numéro de téléphone : le canal SMS échoue, mais push reste envoyé.
        $user = User::factory()->create(['locale' => 'fr', 'phone' => null, 'email' => null]);

        $this->service->notify($user, NotificationTemplate::PAYMENT_VALIDATED, ['order_id' => 42]);

        $this->assertDatabaseHas('notification_logs', ['user_id' => $user->id, 'channel' => NotificationChannel::PUSH, 'status' => NotificationStatus::SENT]);
        $this->assertDatabaseHas('notification_logs', ['user_id' => $user->id, 'channel' => NotificationChannel::SMS, 'status' => NotificationStatus::FAILED]);
    }

    public function test_content_is_rendered_in_the_users_preferred_locale(): void
    {
        $arabicUser = User::factory()->create(['locale' => 'ar']);

        $this->service->notify($arabicUser, NotificationTemplate::DELIVERED, ['order_id' => 42]);

        $log = NotificationLog::query()->where('user_id', $arabicUser->id)->first();
        $this->assertSame('ar', $log->locale);
        $this->assertSame(__('notifications.title.delivered', [], 'ar'), $log->title);
    }
}
