<?php

namespace App\Support;

/**
 * Canaux de notification confirmés par le cahier des charges (8.7.1) :
 * push in-app (FCM), SMS (événements critiques uniquement), e-mail
 * (récapitulatifs de commande).
 */
final class NotificationChannel
{
    public const PUSH = 'push';

    public const SMS = 'sms';

    public const EMAIL = 'email';

    public static function all(): array
    {
        return [self::PUSH, self::SMS, self::EMAIL];
    }
}
