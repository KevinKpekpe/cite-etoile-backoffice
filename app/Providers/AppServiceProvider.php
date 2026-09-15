<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Gate::before(fn (User $user): ?bool => $user->hasRole('super_admin') ? true : null);

        foreach (['dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'plots.view', 'plots.manage', 'payment_plans.view', 'payment_plans.manage', 'subscriptions.view',
            'subscriptions.create', 'subscriptions.update', 'subscriptions.cancel', 'installments.view',
            'installments.manage', 'payments.view', 'payments.create', 'payments.cancel', 'receipts.view',
            'receipts.download', 'reports.view', 'audit_logs.view', 'users.manage', 'settings.manage',
            'portal.view', 'profile.view', 'profile.update', 'documents.view', 'documents.download'] as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
