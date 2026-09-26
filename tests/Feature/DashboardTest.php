<?php

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
});

it('calculates coherent customer plot and financial aggregates', function () {
    Customer::factory()->count(2)->create(['status' => 'active']);
    Customer::factory()->create(['status' => 'settled']);
    Plot::factory()->count(2)->create(['commercial_status' => 'available']);
    $blockedPlot = Plot::factory()->create(['commercial_status' => 'blocked']);
    $subscription = Subscription::factory()->for($blockedPlot)->create(['contract_total' => '3600.00']);
    Payment::factory()->for($subscription)->create(['customer_id' => $subscription->customer_id, 'amount' => '500.00', 'status' => 'validated']);

    $metrics = app(DashboardMetricsService::class)->metrics('month', true);

    expect($metrics['clients']['total'])->toBe(4)
        ->and($metrics['clients']['active'])->toBe(3)
        ->and($metrics['clients']['settled'])->toBe(1)
        ->and($metrics['plots']['available'])->toBe(2)
        ->and($metrics['plots']['blocked'])->toBe(1)
        ->and($metrics['finances']['contractual'])->toBe(3600.0)
        ->and($metrics['finances']['collected'])->toBe(500.0)
        ->and($metrics['finances']['remaining'])->toBe(3100.0);
});

it('compares validated payment chart totals with the same period before it', function () {
    $this->travelTo('2025-09-23 12:00:00');

    $plan = PaymentPlan::factory()->create(['name' => 'Crédit Test']);
    $subscription = Subscription::factory()->for($plan, 'paymentPlan')->create();
    Payment::factory()->for($subscription)->create(['customer_id' => $subscription->customer_id, 'amount' => '300.00', 'status' => 'validated', 'payment_date' => now()->setTime(10, 0)]);
    Payment::factory()->for($subscription)->create(['customer_id' => $subscription->customer_id, 'amount' => '200.00', 'status' => 'validated', 'payment_date' => now()->setTime(11, 0)]);
    Payment::factory()->for($subscription)->create(['customer_id' => $subscription->customer_id, 'amount' => '125.00', 'status' => 'validated', 'payment_date' => now()->subDay()->setTime(10, 0)]);

    $metrics = app(DashboardMetricsService::class)->metrics('day', true);

    expect((float) $metrics['payment_chart']->sum('current'))->toBe(500.0)
        ->and((float) $metrics['payment_chart']->sum('previous'))->toBe(125.0)
        ->and($metrics['plan_distribution']->first()->name)->toBe('Crédit Test')
        ->and((int) $metrics['plan_distribution']->first()->total)->toBe(1);

    $this->actingAs($this->admin)->get(route('dashboard', ['period' => 'day']))
        ->assertOk()
        ->assertSee('dashboard-line-chart__plot')
        ->assertSee('bar-gradient-current');
});

it('shows actionable overdue and upcoming installments to direction roles', function () {
    $subscription = Subscription::factory()->create();
    Installment::factory()->for($subscription)->create(['due_date' => now()->subDay(), 'status' => 'overdue']);
    Installment::factory()->for($subscription)->create(['installment_number' => 2, 'due_date' => now()->addDays(3), 'status' => 'upcoming']);

    $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()
        ->assertSee('Échéances en retard')->assertSee('Échéances à venir');
});

it('hides finance alerts from limited commercial dashboards', function () {
    $commercial = User::factory()->create();
    $commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());

    $this->actingAs($commercial)->get(route('dashboard'))->assertOk()->assertDontSee('Échéances à venir');
});

it('rejects unsupported chart periods', function () {
    $this->actingAs($this->admin)->get(route('dashboard', ['period' => 'decade']))->assertSessionHasErrors('period');
});
