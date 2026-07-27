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

    public const PAYMENTS_VALIDATE_MANUAL = 'payments.validate_manual';

    public const PRICING_MANAGE_MARGIN = 'pricing.manage_margin';

    public const BOUTIQUES_MANAGE = 'boutiques.manage';

    public const ROLES_MANAGE = 'roles.manage';

    public const DASHBOARD_VIEW_FULL = 'dashboard.view_full';

    public const DASHBOARD_VIEW_LIMITED = 'dashboard.view_limited';

    public static function all(): array
    {
        return [
            self::ORDERS_CREATE_FOR_CLIENT,
            self::PAYMENTS_VALIDATE_MANUAL,
            self::PRICING_MANAGE_MARGIN,
            self::BOUTIQUES_MANAGE,
            self::ROLES_MANAGE,
            self::DASHBOARD_VIEW_FULL,
            self::DASHBOARD_VIEW_LIMITED,
        ];
    }
}
