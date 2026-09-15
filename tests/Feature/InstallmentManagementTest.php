<?php

use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InstallmentScheduleService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a protected and readable administrative schedule', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $subscription = Subscription::factory()->create();
    app(InstallmentScheduleService::class)->generate($subscription);

    $this->get(route('subscriptions.installments.index', $subscription))->assertRedirect(route('login'));
    $this->actingAs($admin)->get(route('subscriptions.installments.index', $subscription))->assertOk()
        ->assertSee('Échéancier')->assertSee('300.00 USD');
});
