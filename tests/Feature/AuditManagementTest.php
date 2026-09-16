<?php

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PaymentService;
use App\Services\PermissionAssignmentService;
use App\Services\SettingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->direction = User::factory()->create(['first_name' => 'Direction', 'last_name' => 'Audit']);
    $this->direction->roles()->attach(Role::query()->where('name', 'direction')->firstOrFail());
});

it('records audit entries through one service with context and before after values', function () {
    $customer = Customer::factory()->create();

    $log = app(AuditService::class)->record($this->direction, 'customer.tested', $customer, ['status' => 'prospect'], ['status' => 'active']);

    expect($log->user_id)->toBe($this->direction->id)->and($log->entity_type)->toBe(Customer::class)
        ->and($log->old_values)->toBe(['status' => 'prospect'])->and($log->new_values)->toBe(['status' => 'active']);
});

it('keeps payment creation and reversal snapshots', function () {
    $subscription = Subscription::factory()->create(['duration_months' => 0, 'monthly_amount' => null]);
    $payment = app(PaymentService::class)->record($subscription, $this->direction, ['idempotency_key' => (string) Str::uuid(), 'payment_date' => now(), 'amount' => '300.00', 'currency' => 'USD', 'payment_method' => 'cash']);
    app(PaymentService::class)->reverse($payment, $this->direction, 'Correction comptable');

    $created = AuditLog::query()->where('action', 'payment.created')->firstOrFail();
    $reversed = AuditLog::query()->where('action', 'payment.reversed')->firstOrFail();
    expect($created->new_values['status'])->toBe('validated')->and($reversed->old_values['status'])->toBe('validated')->and($reversed->new_values['status'])->toBe('reversed');
});

it('audits settings and permission assignments', function () {
    $setting = Setting::factory()->create(['value' => 'old']);
    app(SettingService::class)->update($setting, $this->direction, 'new');
    $role = Role::query()->where('name', 'customer')->firstOrFail();
    $permission = Permission::query()->where('name', 'profile.view')->firstOrFail();
    app(PermissionAssignmentService::class)->sync($role, $this->direction, [$permission->id]);

    $this->assertDatabaseHas('audit_logs', ['action' => 'setting.updated', 'entity_id' => $setting->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'role.permissions_updated', 'entity_id' => $role->id]);
});

it('filters logs by user action and date for direction', function () {
    AuditLog::factory()->create(['user_id' => $this->direction->id, 'action' => 'payment.reversed', 'created_at' => '2026-09-16 10:00:00']);
    AuditLog::factory()->create(['action' => 'customer.created', 'created_at' => '2026-08-01 10:00:00']);

    $this->actingAs($this->direction)->get(route('audit-logs.index', ['user_id' => $this->direction->id, 'action' => 'payment', 'from' => '2026-09-01']))
        ->assertOk()->assertSee('payment.reversed')->assertDontSee('customer.created');
});

it('allows only direction and super administrators to view audit logs', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $superAdmin = User::factory()->create();
    $superAdmin->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());

    $this->actingAs($admin)->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs($superAdmin)->get(route('audit-logs.index'))->assertOk();
});

it('prevents updating or deleting audit records through the model', function () {
    $log = AuditLog::factory()->create();

    expect(fn () => $log->update(['action' => 'tampered']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});
