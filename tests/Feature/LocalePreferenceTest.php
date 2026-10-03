<?php

use App\Models\Customer;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('uses the saved customer language across portal requests', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, SettingSeeder::class]);
    Cache::forget('setting.platform.locale');

    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());
    Customer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->put(route('portal.settings.update'), [
        'locale' => 'en',
        'email_reminders' => '1',
        'email_receipts' => '1',
        'email_updates' => '1',
    ])->assertRedirect()->assertSessionHas('locale', 'en')
        ->assertSessionHas('status', 'Your preferences were updated successfully.');

    $this->get(route('portal.settings.index'))
        ->assertSee('<html lang="en"', false)
        ->assertSee('Customer area')
        ->assertSee('Portal language')
        ->assertDontSee('Espace client');
});

it('falls back to the platform language when a session preference is unsupported', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, SettingSeeder::class]);
    Cache::forget('setting.platform.locale');
    Setting::query()->where('setting_group', 'platform')->where('setting_key', 'locale')->update(['value' => 'en']);
    Cache::forget('setting.platform.locale');

    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());
    Customer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->withSession(['locale' => 'de'])->get(route('portal.settings.index'))
        ->assertSee('<html lang="en"', false)
        ->assertSee('Customer area');
});

it('shows validation messages in the active language', function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, SettingSeeder::class]);
    Cache::forget('setting.platform.locale');

    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());
    Customer::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $this->withSession(['locale' => 'en'])->put(route('portal.settings.update'), ['locale' => 'de'])
        ->assertSessionHasErrors(['locale' => 'The selected language is invalid.']);

    $this->withSession(['locale' => 'fr'])->put(route('portal.settings.update'), ['locale' => 'de'])
        ->assertSessionHasErrors(['locale' => 'La valeur sélectionnée pour langue est invalide.']);
});
