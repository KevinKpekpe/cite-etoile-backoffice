<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'customers.delete', 'customers.restore', 'customers.force_delete',
            'plots.view', 'plots.manage', 'plots.restore', 'plots.force_delete',
            'payment_plans.view', 'payment_plans.manage', 'payment_plans.restore', 'payment_plans.force_delete',
            'subscriptions.view', 'subscriptions.create', 'subscriptions.update', 'subscriptions.cancel',
            'installments.view', 'installments.manage', 'payments.view', 'payments.create', 'payments.cancel',
            'receipts.view', 'receipts.download', 'reports.view', 'audit_logs.view',
            'users.manage', 'users.delete', 'users.force_delete',
            'settings.manage', 'portal.view', 'profile.view', 'profile.update', 'documents.view', 'documents.download',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }

        $matrix = [
            'admin' => array_values(array_diff($permissions, ['audit_logs.view', 'users.force_delete', 'customers.force_delete', 'plots.force_delete', 'payment_plans.force_delete'])),
            'direction' => ['dashboard.view', 'customers.view', 'plots.view', 'payment_plans.view', 'subscriptions.view', 'installments.view', 'payments.view', 'receipts.view', 'reports.view', 'audit_logs.view'],
            'commercial' => ['dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'plots.view', 'payment_plans.view', 'subscriptions.view', 'subscriptions.create', 'subscriptions.update', 'installments.view', 'documents.view', 'documents.download'],
            'cashier' => ['dashboard.view', 'customers.view', 'subscriptions.view', 'installments.view', 'payments.view', 'payments.create', 'receipts.view', 'receipts.download'],
            'finance_manager' => ['dashboard.view', 'customers.view', 'plots.view', 'payment_plans.view', 'subscriptions.view', 'installments.view', 'installments.manage', 'payments.view', 'payments.create', 'payments.cancel', 'receipts.view', 'receipts.download', 'reports.view'],
            'customer' => ['portal.view', 'profile.view', 'profile.update'],
        ];

        foreach ($matrix as $roleName => $rolePermissions) {
            Role::query()->where('name', $roleName)->firstOrFail()->permissions()->sync(
                Permission::query()->whereIn('name', $rolePermissions)->pluck('id'),
            );
        }

        Role::query()->where('name', 'super_admin')->firstOrFail()->permissions()->sync(Permission::query()->pluck('id'));
    }
}
