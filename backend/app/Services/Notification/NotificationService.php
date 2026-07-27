<?php

namespace App\Services\Notification;

use App\Contracts\PushGateway;
use App\Contracts\SmsGateway;
use App\Mail\NotificationMail;
use App\Models\NotificationLog;
use App\Models\User;
use App\Support\NotificationChannel;
use App\Support\NotificationStatus;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Point d'entrée unique pour les notifications automatiques du parcours de
 * commande (CDC 8.7). Rend le contenu bilingue dans la langue préférée du
 * client (8.7.2), résout les canaux applicables (préférences du client +
 * éligibilité par gabarit, avec repli garanti sur push pour les gabarits
 * critiques), et historise chaque envoi — succès ou échec.
 */
class NotificationService
{
    public function __construct(
        private readonly PushGateway $push,
        private readonly SmsGateway $sms,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function notify(User $user, string $templateKey, array $data = []): void
    {
        $locale = $user->locale ?? config('app.locale');
        $title = trans('notifications.title.'.$templateKey, $data, $locale);
        $body = trans('notifications.body.'.$templateKey, $data, $locale);

        foreach ($this->resolveChannels($user, $templateKey) as $channel) {
            $this->dispatch($channel, $user, $templateKey, $locale, $title, $body, $data);
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveChannels(User $user, string $templateKey): array
    {
        $preferences = $user->notificationPreferences()->pluck('enabled', 'channel');
        $isCritical = in_array($templateKey, config('notifications.critical_templates'), true);

        $channels = [];

        // Push est le canal par défaut, tenté pour tous les gabarits.
        if ($preferences->get(NotificationChannel::PUSH, true)) {
            $channels[] = NotificationChannel::PUSH;
        }

        if (
            in_array($templateKey, config('notifications.sms_eligible_templates'), true)
            && $preferences->get(NotificationChannel::SMS, true)
        ) {
            $channels[] = NotificationChannel::SMS;
        }

        if (
            in_array($templateKey, config('notifications.email_eligible_templates'), true)
            && $preferences->get(NotificationChannel::EMAIL, true)
        ) {
            $channels[] = NotificationChannel::EMAIL;
        }

        // 8.7.2 : un gabarit critique ne peut pas être totalement désactivé
        // — repli garanti sur push, seul canal sans coût ni dépendance
        // externe, si le client a désactivé tous ses canaux.
        if ($isCritical && $channels === []) {
            $channels[] = NotificationChannel::PUSH;
        }

        return $channels;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatch(string $channel, User $user, string $templateKey, string $locale, string $title, string $body, array $data): void
    {
        $status = NotificationStatus::SENT;
        $error = null;

        try {
            match ($channel) {
                NotificationChannel::PUSH => $this->push->send($user, $title, $body, $data),
                NotificationChannel::SMS => $this->sendSms($user, $body),
                NotificationChannel::EMAIL => $this->sendEmail($user, $title, $body),
            };
        } catch (Throwable $e) {
            $status = NotificationStatus::FAILED;
            $error = $e->getMessage();
        }

        NotificationLog::query()->create([
            'user_id' => $user->id,
            'channel' => $channel,
            'locale' => $locale,
            'template_key' => $templateKey,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'status' => $status,
            'error' => $error,
            'sent_at' => $status === NotificationStatus::SENT ? now() : null,
        ]);
    }

    private function sendSms(User $user, string $body): void
    {
        if ($user->phone === null) {
            throw new RuntimeException('Utilisateur sans numéro de téléphone.');
        }

        $this->sms->send($user->phone, $body);
    }

    private function sendEmail(User $user, string $title, string $body): void
    {
        if ($user->email === null) {
            throw new RuntimeException('Utilisateur sans adresse e-mail.');
        }

        Mail::to($user->email)->send(new NotificationMail($title, $body));
    }
}
