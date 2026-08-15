<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and permissions exactly per spec §9.1–9.2. Idempotent.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<int, string> */
    private array $permissions = [
        'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish',
        'cohorts.view', 'cohorts.create', 'cohorts.update', 'cohorts.delete',
        'enrollments.view', 'enrollments.manage', 'enrollments.export',
        'links.create', 'links.revoke',
        'finance.view', 'finance.record_income', 'finance.record_expense',
        'finance.settle', 'finance.reverse', 'finance.manage_accounts', 'finance.manage_policies',
        'partners.view_own_statement', 'partners.view_all_statements', 'partners.record_payout',
        'reports.view', 'reports.export', 'reports.tax',
        'users.view', 'users.manage', 'roles.manage',
        'settings.manage', 'audit.view',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // مالك — everything
        Role::findOrCreate('owner')->syncPermissions($this->permissions);

        // مدير — operations + basic finance recording; never settlements,
        // policies, accounts, or other partners' statements (spec §9.1)
        Role::findOrCreate('admin')->syncPermissions([
            'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish',
            'cohorts.view', 'cohorts.create', 'cohorts.update', 'cohorts.delete',
            'enrollments.view', 'enrollments.manage', 'enrollments.export',
            'links.create', 'links.revoke',
            'finance.view', 'finance.record_income', 'finance.record_expense',
            'reports.view', 'reports.export',
        ]);

        // منسق — no financial access at all (spec §9.1)
        Role::findOrCreate('coordinator')->syncPermissions([
            'courses.view', 'courses.create', 'courses.update',
            'cohorts.view', 'cohorts.create', 'cohorts.update',
            'enrollments.view', 'enrollments.manage', 'enrollments.export',
        ]);

        // مطلع — read-only catalog
        Role::findOrCreate('viewer')->syncPermissions([
            'courses.view',
            'cohorts.view',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
