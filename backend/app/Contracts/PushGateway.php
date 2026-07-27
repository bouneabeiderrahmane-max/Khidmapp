<?php

namespace App\Contracts;

use App\Models\User;

interface PushGateway
{
    /**
     * Send an in-app push notification to the given user (Firebase Cloud
     * Messaging per the cahier des charges, 8.7.1). Implementations are
     * swapped via the binding in AppServiceProvider so a real FCM
     * integration can replace the placeholder without touching any caller.
     *
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $title, string $body, array $data = []): void;
}
