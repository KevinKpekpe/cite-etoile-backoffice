<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDocumentController;
use Illuminate\Support\Facades\Route;

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
        Route::view('/', 'dashboard')->middleware('can:dashboard.view')->name('dashboard');
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
    });
});
