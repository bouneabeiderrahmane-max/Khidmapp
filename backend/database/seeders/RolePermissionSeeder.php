<?php

namespace Database\Seeders;

use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the three roles confirmed by the cahier des charges (7.1) and the
     * permission matrix (section 4 of docs/PLAN.md, synthétisée du 7.5/8.9.6
     * du cahier des charges).
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $client = Role::findOrCreate(Roles::CLIENT);

        $serviceClient = Role::findOrCreate(Roles::SERVICE_CLIENT);
        $serviceClient->givePermissionTo([
            Permissions::ORDERS_CREATE_FOR_CLIENT,
            Permissions::ORDERS_MANAGE_STATUS,
            Permissions::PAYMENTS_VALIDATE_MANUAL,
            Permissions::DASHBOARD_VIEW_LIMITED,
            Permissions::NOTIFICATIONS_VIEW,
            Permissions::COMPLAINTS_MANAGE,
        ]);

        $administrateur = Role::findOrCreate(Roles::ADMINISTRATEUR);
        $administrateur->givePermissionTo([
            Permissions::ORDERS_CREATE_FOR_CLIENT,
            Permissions::ORDERS_MANAGE_STATUS,
            Permissions::PAYMENTS_VALIDATE_MANUAL,
            Permissions::PRICING_MANAGE_MARGIN,
            Permissions::BOUTIQUES_MANAGE,
            Permissions::CATALOG_MANAGE,
            Permissions::ROLES_MANAGE,
            Permissions::DASHBOARD_VIEW_FULL,
            Permissions::NOTIFICATIONS_VIEW,
            Permissions::COMPLAINTS_MANAGE,
            Permissions::CONTENT_MANAGE,
            Permissions::USERS_MANAGE,
            Permissions::AUDIT_VIEW,
        ]);
    }
}
