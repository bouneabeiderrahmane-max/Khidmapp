<?php

namespace App\Support;

/**
 * The three internal roles confirmed by the cahier des charges (section 7.1):
 * Client, Service client, Administrateur. No "super_admin" is defined there.
 */
final class Roles
{
    public const CLIENT = 'client';

    public const SERVICE_CLIENT = 'service_client';

    public const ADMINISTRATEUR = 'administrateur';

    public static function all(): array
    {
        return [self::CLIENT, self::SERVICE_CLIENT, self::ADMINISTRATEUR];
    }
}
