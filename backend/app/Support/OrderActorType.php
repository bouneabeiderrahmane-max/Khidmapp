<?php

namespace App\Support;

use App\Models\User;

final class OrderActorType
{
    public const SYSTEM = 'system';

    public const CLIENT = 'client';

    public const SERVICE_CLIENT = 'service_client';

    public const ADMINISTRATEUR = 'administrateur';

    /**
     * Déduit le type d'acteur (pour la traçabilité 8.4.1) du rôle de
     * l'agent interne connecté.
     */
    public static function forAgent(User $user): string
    {
        return $user->hasRole(Roles::ADMINISTRATEUR) ? self::ADMINISTRATEUR : self::SERVICE_CLIENT;
    }
}
