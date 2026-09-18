<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\AvenueController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDocumentController;
use App\Http\Controllers\CustomerStatementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\InstallmentScheduleController;
use App\Http\Controllers\NeighborhoodController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\PaymentReversalController;
use App\Http\Controllers\PlotController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\InstallmentController as PortalInstallmentController;
use App\Http\Controllers\Portal\PaymentController as PortalPaymentController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use App\Http\Controllers\Portal\ReceiptController as PortalReceiptController;
use App\Http\Controllers\Portal\SubscriptionController as PortalSubscriptionController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionStatusController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifyReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/verify/receipts/{verificationCode}', VerifyReceiptController::class)->middleware('throttle:30,1')->name('receipts.verify');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/two-factor-challenge', [TwoFactorAuthenticationController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorAuthenticationController::class, 'verify'])->middleware('throttle:6,1')->name('two-factor.verify');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('2fa')->group(function (): void {
        Route::get('/', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');
        Route::get('/account/two-factor', [TwoFactorAuthenticationController::class, 'setup'])->name('two-factor.setup');
        Route::post('/account/two-factor', [TwoFactorAuthenticationController::class, 'enable'])->middleware('throttle:6,1')->name('two-factor.enable');
        Route::delete('/account/two-factor', [TwoFactorAuthenticationController::class, 'disable'])->name('two-factor.disable');

        Route::get('/customers', [CustomerController::class, 'index'])->middleware('can:customers.view')->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->middleware('can:customers.create')->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->middleware('can:customers.create')->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('can:customers.view')->name('customers.show');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->middleware('can:customers.update')->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('can:customers.update')->name('customers.update');
        Route::patch('/customers/{customer}/archive', [CustomerController::class, 'archive'])->middleware('can:customers.delete')->name('customers.archive');
        Route::post('/customers/{customer}/documents', [CustomerDocumentController::class, 'store'])->middleware('can:customers.update')->name('customers.documents.store');
        Route::get('/customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'download'])->middleware('can:documents.download')->scopeBindings()->name('customers.documents.download');
        Route::resource('neighborhoods', NeighborhoodController::class)->except(['show'])->middleware('can:plots.manage');
        Route::resource('avenues', AvenueController::class)->except(['show'])->middleware('can:plots.manage');
        Route::get('/plots', [PlotController::class, 'index'])->middleware('can:plots.view')->name('plots.index');
        Route::get('/plots/{plot}', [PlotController::class, 'show'])->middleware('can:plots.view')->name('plots.show');
        Route::resource('plots', PlotController::class)->except(['index', 'show'])->middleware('can:plots.manage');
        Route::get('/payment-plans', [PaymentPlanController::class, 'index'])->middleware('can:payment_plans.view')->name('payment-plans.index');
        Route::resource('payment-plans', PaymentPlanController::class)->except(['index', 'show'])->middleware('can:payment_plans.manage');
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->middleware('can:subscriptions.view')->name('subscriptions.index');
        Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->middleware('can:subscriptions.create')->name('subscriptions.create');
        Route::post('/subscriptions', [SubscriptionController::class, 'store'])->middleware('can:subscriptions.create')->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show'])->middleware('can:subscriptions.view')->name('subscriptions.show');
        Route::patch('/subscriptions/{subscription}/status', SubscriptionStatusController::class)->middleware('can:subscriptions.update')->name('subscriptions.status');
        Route::post('/subscriptions/{subscription}/contract', [ContractController::class, 'store'])->middleware('can:subscriptions.update')->name('subscriptions.contract.store');
        Route::get('/subscriptions/{subscription}/contract/{contract}', [ContractController::class, 'download'])->middleware('can:documents.download')->scopeBindings()->name('subscriptions.contract.download');
        Route::get('/subscriptions/{subscription}/installments', [InstallmentController::class, 'index'])->middleware('can:installments.view')->name('subscriptions.installments.index');
        Route::post('/subscriptions/{subscription}/installments/generate', [InstallmentScheduleController::class, 'store'])->middleware('can:installments.manage')->name('subscriptions.installments.generate');
        Route::get('/payments', [PaymentController::class, 'index'])->middleware('can:payments.view')->name('payments.index');
        Route::get('/subscriptions/{subscription}/payments/create', [PaymentController::class, 'create'])->middleware('can:payments.create')->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->middleware('can:payments.create')->name('payments.store');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('can:payments.view')->name('payments.show');
        Route::patch('/payments/{payment}/reverse', PaymentReversalController::class)->middleware('can:payments.cancel')->name('payments.reverse');
        Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->middleware('can:receipts.view')->name('receipts.show');
        Route::get('/receipts/{receipt}/download', [ReceiptController::class, 'download'])->middleware('can:receipts.download')->name('receipts.download');
        Route::get('/reports', [ReportController::class, 'index'])->middleware('can:reports.view')->name('reports.index');
        Route::get('/reports/export/{report}', [ReportController::class, 'export'])->middleware('can:reports.view')->name('reports.export');
        Route::get('/reports/customers/{customer}/statement', CustomerStatementController::class)->middleware('can:reports.view')->name('reports.customers.statement');
        Route::get('/audit-logs', AuditLogController::class)->middleware('can:audit_logs.view')->name('audit-logs.index');
        Route::get('/settings', [SettingController::class, 'index'])->middleware('can:settings.manage')->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->middleware('can:settings.manage')->name('settings.update');

        Route::middleware('can:users.manage')->group(function (): void {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        });

        Route::prefix('portal')->middleware('can:portal.view')->name('portal.')->group(function (): void {
            Route::get('/', PortalDashboardController::class)->name('dashboard');
            Route::get('/subscriptions', [PortalSubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::get('/subscriptions/{subscription}', [PortalSubscriptionController::class, 'show'])->whereNumber('subscription')->name('subscriptions.show');
            Route::get('/payments', [PortalPaymentController::class, 'index'])->name('payments.index');
            Route::get('/installments', [PortalInstallmentController::class, 'index'])->name('installments.index');
            Route::get('/receipts', [PortalReceiptController::class, 'index'])->name('receipts.index');
            Route::get('/receipts/{receipt}/download', [PortalReceiptController::class, 'download'])->middleware('signed')->whereNumber('receipt')->name('receipts.download');
            Route::get('/profile', [PortalProfileController::class, 'edit'])->middleware('can:profile.view')->name('profile.edit');
            Route::put('/profile', [PortalProfileController::class, 'update'])->middleware('can:profile.update')->name('profile.update');
        });
    });
});
