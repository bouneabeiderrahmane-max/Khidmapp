<?php

namespace App\Support;

/**
 * Actions journalisées dans le journal d'audit (exigence transverse :
 * "validation de paiement, modification de marge, changement de rôle" —
 * exemple donné tel quel par le cahier des charges, 8.9.6). Les
 * transitions de statut de commande ne sont pas dupliquées ici : elles
 * disposent déjà de leur propre trace complète (order_status_histories,
 * Sprint 6).
 */
final class AuditAction
{
    public const MARGIN_RULE_CREATED = 'margin_rule.created';

    public const EXCHANGE_RATE_CREATED = 'exchange_rate.created';

    public const PAYMENT_VALIDATED = 'payment.validated';

    public const PAYMENT_REJECTED = 'payment.rejected';

    public const PAYMENT_INFO_REQUESTED = 'payment.info_requested';

    /**
     * Remplacement de l'ensemble des rôles d'un compte interne. Une seule
     * entrée par changement (le tableau `from`/`to` dans `changes` couvre
     * aussi bien les attributions que les révocations, syncRoles()
     * remplaçant intégralement l'ensemble).
     */
    public const USER_ROLE_ASSIGNED = 'user.role_assigned';

    public const USER_BLOCKED = 'user.blocked';

    public const USER_UNBLOCKED = 'user.unblocked';

    public const USER_CREATED = 'user.created';
}
