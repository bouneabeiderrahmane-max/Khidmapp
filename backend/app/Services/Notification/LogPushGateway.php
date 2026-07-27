<?php

namespace App\Services\Notification;

use App\Contracts\PushGateway;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder gateway: writes the push payload to the log instead of
 * delivering a real Firebase Cloud Messaging notification. The cahier des
 * charges confirms FCM as the push channel (8.7.1) but no Firebase
 * project/credentials exist yet — this stands in until they do. Do not
 * treat this as a production-ready delivery channel.
 */
class LogPushGateway implements PushGateway
{
    public function send(User $user, string $title, string $body, array $data = []): void
    {
        Log::info('[Push placeholder] would send to user #'.$user->id, [
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
