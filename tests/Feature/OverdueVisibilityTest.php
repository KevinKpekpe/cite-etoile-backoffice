<?php

use App\Models\Customer;
use App\Models\Installment;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(SettingSeeder::class);

    $role = Role::where('name', 'admin')->firstOrFail();
    $this->user = User::factory()->create();
    $this->user->roles()->attach($role->id);
});

it('can filter subscriptions with overdue status', function () {
    $customer = Customer::factory()->create();
    $plot1 = Plot::factory()->create(['commercial_status' => 'subscribed']);
    $plot2 = Plot::factory()->create(['commercial_status' => 'subscribed']);
    $plan = PaymentPlan::factory()->create(['monthly_amount' => '200.00', 'duration_months' => 6]);

    // Regular active subscription
    $subNormal = Subscription::factory()->create([
        'customer_id' => $customer->id, 'plot_id' => $plot1->id, 'payment_plan_id' => $plan->id,
        'commercial_status' => 'active', 'financial_status' => 'partially_paid',
    ]);
    Installment::factory()->create([
        'subscription_id' => $subNormal->id, 'due_date' => now()->addDays(10)->toDateString(),
        'amount_due' => '200.00', 'amount_paid' => '0.00', 'status' => 'upcoming',
    ]);

    // Overdue subscription
    $subOverdue = Subscription::factory()->create([
        'customer_id' => $customer->id, 'plot_id' => $plot2->id, 'payment_plan_id' => $plan->id,
        'commercial_status' => 'active', 'financial_status' => 'partially_paid',
    ]);
    Installment::factory()->create([
        'subscription_id' => $subOverdue->id, 'due_date' => now()->subDays(5)->toDateString(),
        'amount_due' => '200.00', 'amount_paid' => '0.00', 'status' => 'upcoming',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('subscriptions.index', ['status' => 'overdue']));

    $response->assertOk();
    $response->assertSee($subOverdue->subscription_number);
    $response->assertDontSee($subNormal->subscription_number);
});

it('can filter customers with overdue payments', function () {
    $customerGood = Customer::factory()->create(['first_name' => 'Alice']);
    $customerLate = Customer::factory()->create(['first_name' => 'Bob']);
    $plot1 = Plot::factory()->create(['commercial_status' => 'subscribed']);
    $plot2 = Plot::factory()->create(['commercial_status' => 'subscribed']);
    $plan = PaymentPlan::factory()->create();

    $subGood = Subscription::factory()->create([
        'customer_id' => $customerGood->id, 'plot_id' => $plot1->id, 'payment_plan_id' => $plan->id,
    ]);
    Installment::factory()->create([
        'subscription_id' => $subGood->id, 'due_date' => now()->addDays(5)->toDateString(),
        'amount_due' => '100.00', 'amount_paid' => '100.00', 'status' => 'paid',
    ]);

    $subLate = Subscription::factory()->create([
        'customer_id' => $customerLate->id, 'plot_id' => $plot2->id, 'payment_plan_id' => $plan->id,
    ]);
    Installment::factory()->create([
        'subscription_id' => $subLate->id, 'due_date' => now()->subDays(3)->toDateString(),
        'amount_due' => '100.00', 'amount_paid' => '0.00', 'status' => 'upcoming',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('customers.index', ['status' => 'overdue']));

    $response->assertOk();
    $response->assertSee('Bob');
    $response->assertDontSee('Alice');
});
