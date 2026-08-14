<?php

namespace App\Support;

/**
 * Cycle de vie d'un "pedido personalizado" (CDC — extension signalée,
 * absente du cahier des charges d'origine) : une demande client, avant
 * toute conversion en Commande réelle, ne connaît que trois états. Une
 * fois "confirmée", c'est la Commande créée (`order_id`) qui porte la
 * suite du parcours via les 15 statuts d'OrderStatus.
 */
final class CustomOrderRequestStatus
{
    public const PENDING = 'en_attente';

    public const APPROVED = 'confirmee';

    public const REJECTED = 'rejetee';

    public static function all(): array
    {
        return [self::PENDING, self::APPROVED, self::REJECTED];
    }
}
