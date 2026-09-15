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
            'dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'plots.view', 'plots.manage', 'payment_plans.view', 'payment_plans.manage',
            'subscriptions.view', 'subscriptions.create', 'subscriptions.update', 'subscriptions.cancel',
            'installments.view', 'installments.manage', 'payments.view', 'payments.create', 'payments.cancel',
            'receipts.view', 'receipts.download', 'reports.view', 'audit_logs.view', 'users.manage',
            'settings.manage', 'portal.view', 'profile.view', 'profile.update', 'documents.view', 'documents.download',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }

        $matrix = [
            'admin' => $permissions,
            'direction' => ['dashboard.view', 'customers.view', 'plots.view', 'payment_plans.view', 'subscriptions.view', 'installments.view', 'payments.view', 'receipts.view', 'reports.view', 'audit_logs.view'],
            'commercial' => ['dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'plots.view', 'payment_plans.view', 'subscriptions.view', 'subscriptions.create', 'subscriptions.update', 'installments.view'],
            'cashier' => ['dashboard.view', 'customers.view', 'subscriptions.view', 'installments.view', 'payments.view', 'payments.create', 'receipts.view', 'receipts.download'],
            'finance_manager' => ['dashboard.view', 'customers.view', 'plots.view', 'payment_plans.view', 'subscriptions.view', 'installments.view', 'installments.manage', 'payments.view', 'payments.create', 'payments.cancel', 'receipts.view', 'receipts.download', 'reports.view'],
            'customer' => ['portal.view', 'profile.view', 'profile.update', 'documents.view', 'documents.download', 'payments.view', 'receipts.view', 'receipts.download', 'installments.view', 'subscriptions.view'],
        ];

        foreach ($matrix as $roleName => $rolePermissions) {
            Role::query()->where('name', $roleName)->firstOrFail()->permissions()->sync(
                Permission::query()->whereIn('name', $rolePermissions)->pluck('id'),
            );
        }

        Role::query()->where('name', 'super_admin')->firstOrFail()->permissions()->sync(Permission::query()->pluck('id'));
    }
}
