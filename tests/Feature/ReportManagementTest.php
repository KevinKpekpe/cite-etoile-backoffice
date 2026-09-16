<?php

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->finance = User::factory()->create(['first_name' => 'Finance', 'last_name' => 'Agent']);
    $this->finance->roles()->attach(Role::query()->where('name', 'finance_manager')->firstOrFail());
});

it('filters customer and plot reports by status and period', function () {
    Customer::factory()->create(['first_name' => 'ActiveInPeriod', 'status' => 'active', 'created_at' => '2026-09-10']);
    Customer::factory()->create(['first_name' => 'ArchivedOutside', 'status' => 'archived', 'created_at' => '2026-08-01']);
    Plot::factory()->create(['reference' => 'LOT-AVAILABLE', 'commercial_status' => 'available']);
    Plot::factory()->create(['reference' => 'LOT-SUBSCRIBED', 'commercial_status' => 'subscribed']);

    $this->actingAs($this->finance)->get(route('reports.index', ['from' => '2026-09-01', 'to' => '2026-09-30', 'customer_status' => 'active', 'plot_status' => 'available']))
        ->assertOk()->assertSee('ActiveInPeriod')->assertDontSee('ArchivedOutside')->assertSee('LOT-AVAILABLE')->assertDontSee('LOT-SUBSCRIBED');
});

it('filters validated payments by period formula and receiving agent', function () {
    $selectedPlan = PaymentPlan::factory()->create(['name' => 'Plan sélectionné']);
    $selected = Subscription::factory()->for($selectedPlan, 'paymentPlan')->create();
    Payment::factory()->for($selected)->create(['customer_id' => $selected->customer_id, 'payment_reference' => 'PAY-MATCH', 'payment_date' => '2026-09-12', 'received_by' => $this->finance->id, 'amount' => '450.00']);
    Payment::factory()->create(['payment_reference' => 'PAY-OTHER', 'payment_date' => '2026-08-01', 'amount' => '900.00']);

    $report = app(ReportService::class)->generate(['from' => '2026-09-01', 'to' => '2026-09-30', 'payment_plan_id' => $selectedPlan->id, 'agent_id' => $this->finance->id]);

    expect($report['payments'])->toHaveCount(1)->and($report['payments']->first()->payment_reference)->toBe('PAY-MATCH')->and($report['paymentTotal'])->toBe(450.0);
});

it('calculates overdue balances and age from due dates', function () {
    $subscription = Subscription::factory()->create();
    Installment::factory()->for($subscription)->create(['due_date' => now()->subDays(15), 'amount_due' => '300.00', 'amount_paid' => '100.00', 'status' => 'overdue']);
    Installment::factory()->for($subscription)->create(['installment_number' => 2, 'due_date' => now()->addDay(), 'amount_due' => '500.00', 'amount_paid' => '0.00']);

    $report = app(ReportService::class)->generate([]);

    expect($report['overdue'])->toHaveCount(1)->and($report['overdueTotal'])->toBe(200.0)
        ->and($report['overdue']->first()->due_date->diffInDays(now()))->toBeGreaterThanOrEqual(15);
});

it('exports csv using the active report filters', function () {
    Customer::factory()->create(['first_name' => 'ExportedClient', 'status' => 'active']);
    Customer::factory()->create(['first_name' => 'HiddenClient', 'status' => 'archived']);

    $response = $this->actingAs($this->finance)->get(route('reports.export', ['report' => 'customers', 'customer_status' => 'active']));

    $response->assertOk()->assertDownload();
    expect($response->streamedContent())->toContain('ExportedClient')->not->toContain('HiddenClient');
});

it('generates a customer statement PDF with history balance and schedule', function () {
    $customer = Customer::factory()->create(['first_name' => 'PDFClient']);
    $subscription = Subscription::factory()->for($customer)->create(['amount_paid' => '300.00']);
    Payment::factory()->for($subscription)->create(['customer_id' => $customer->id, 'amount' => '300.00']);
    Installment::factory()->for($subscription)->create();

    $response = $this->actingAs($this->finance)->get(route('reports.customers.statement', $customer));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it('restricts reports and exports to authorized finance roles', function () {
    $commercial = User::factory()->create();
    $commercial->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());

    $this->actingAs($commercial)->get(route('reports.index'))->assertForbidden();
    $this->get(route('reports.export', 'payments'))->assertForbidden();
});
