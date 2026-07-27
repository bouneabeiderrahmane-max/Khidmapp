<?php

namespace App\Support;

/**
 * Permission names used with spatie/laravel-permission. Kept as constants
 * so controllers/policies never reference a role or permission by a raw
 * string typo — see docs/PLAN.md section 4 for the confirmed matrix.
 */
final class Permissions
{
    public const ORDERS_CREATE_FOR_CLIENT = 'orders.create_for_client';

    /**
     * Supervision globale des commandes et intervention manuelle (7.4 :
     * "Gestion des commandes : supervision globale, intervention manuelle
     * en cas de blocage") — transitions de statut par le service client ou
     * l'administrateur (Sprint 6, extension du RBAC granulaire 8.9.6).
     */
    public const ORDERS_MANAGE_STATUS = 'orders.manage_status';

    public const PAYMENTS_VALIDATE_MANUAL = 'payments.validate_manual';

    public const PRICING_MANAGE_MARGIN = 'pricing.manage_margin';

    public const BOUTIQUES_MANAGE = 'boutiques.manage';

    /**
     * Catégories, mapping catégorie source→unifiée, correction manuelle des
     * produits synchronisés, déclenchement/consultation de la
     * synchronisation catalogue (module 8.2, introduit au Sprint 3 — pas
     * dans la matrice de synthèse 7.5 du CDC, qui ne détaille pas ce
     * module ; extension naturelle du système de permissions granulaire
     * demandé en 8.9.6).
     */
    public const CATALOG_MANAGE = 'catalog.manage';

    public const ROLES_MANAGE = 'roles.manage';

    public const DASHBOARD_VIEW_FULL = 'dashboard.view_full';

    public const DASHBOARD_VIEW_LIMITED = 'dashboard.view_limited';

    /**
     * Consultation de l'historique des notifications envoyées, tous
     * clients confondus (8.7.2 : "consultable... dans l'administration") —
     * pas dans la matrice de synthèse 7.5, extension naturelle du RBAC
     * granulaire (8.9.6), au même titre que orders.manage_status/
     * payments.validate_manual (Sprints 6/7).
     */
    public const NOTIFICATIONS_VIEW = 'notifications.view';

    /**
     * Prise en charge, suivi, clôture des réclamations (7.3 : "Gestion des
     * réclamations : prise en charge, suivi, clôture, historique") —
     * extension naturelle du RBAC granulaire (8.9.6), Sprint 10.
     */
    public const COMPLAINTS_MANAGE = 'complaints.manage';

    /**
     * Gestion du contenu du centre d'aide (FAQ, 8.8). Le CDC n'attribue ce
     * module à aucun rôle précis ; réservé à l'administrateur par défaut,
     * comme la gestion des boutiques/catégories, plutôt qu'ouvert au
     * service client — à confirmer si besoin.
     */
    public const CONTENT_MANAGE = 'content.manage';

    /**
     * Recherche/consultation des comptes clients et internes, blocage
     * temporaire ou définitif d'un compte client en cas d'abus (8.9.2).
     * Distincte de `roles.manage` (création de comptes internes,
     * attribution des rôles) : l'une gère les comptes, l'autre les droits.
     */
    public const USERS_MANAGE = 'users.manage';

    /**
     * Consultation du journal d'audit des opérations sensibles (validation
     * de paiement, modification de marge, changement de rôle — exemple
     * donné tel quel par le CDC, 8.9.6).
     */
    public const AUDIT_VIEW = 'audit.view';

    public static function all(): array
    {
        return [
            self::ORDERS_CREATE_FOR_CLIENT,
            self::ORDERS_MANAGE_STATUS,
            self::PAYMENTS_VALIDATE_MANUAL,
            self::PRICING_MANAGE_MARGIN,
            self::BOUTIQUES_MANAGE,
            self::CATALOG_MANAGE,
            self::ROLES_MANAGE,
            self::DASHBOARD_VIEW_FULL,
            self::DASHBOARD_VIEW_LIMITED,
            self::NOTIFICATIONS_VIEW,
            self::COMPLAINTS_MANAGE,
            self::CONTENT_MANAGE,
            self::USERS_MANAGE,
            self::AUDIT_VIEW,
        ];
    }
}
