<?php

namespace App\Support;

/**
 * Statuts d'un enregistrement de paiement (CDC 8.5), communs aux deux
 * modes : "pending" à l'initiation (Bankily) ou à la soumission de la
 * preuve (manuel), "validated" au succès des deux, puis des statuts
 * terminaux propres à chaque mode (rejected/info_requested pour le
 * manuel — 8.5.2 ; failed/expired/cancelled pour Bankily — 8.5.1).
 *
 * "info_requested" ne débloque pas la commande : le client doit soumettre
 * une nouvelle preuve (nouvel enregistrement Payment), l'ancien restant
 * comme trace historique de l'échange.
 */
final class PaymentStatus
{
    public const PENDING = 'pending';

    public const VALIDATED = 'validated';

    public const REJECTED = 'rejected';

    public const INFO_REQUESTED = 'info_requested';

    public const FAILED = 'failed';

    public const EXPIRED = 'expired';

    public const CANCELLED = 'cancelled';

    private const TERMINAL_STATUSES = [
        self::VALIDATED,
        self::REJECTED,
        self::INFO_REQUESTED,
        self::FAILED,
        self::EXPIRED,
        self::CANCELLED,
    ];

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL_STATUSES, true);
    }
}
