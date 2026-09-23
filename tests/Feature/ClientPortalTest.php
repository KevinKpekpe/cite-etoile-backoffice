<?php

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    $this->user = User::factory()->create(['first_name' => 'Amina', 'last_name' => 'Mbuyi']);
    $this->user->roles()->attach(Role::query()->where('name', 'customer')->firstOrFail());
    $this->customer = Customer::factory()->create(['user_id' => $this->user->id, 'first_name' => 'Amina', 'last_name' => 'Mbuyi']);
});

it('shows only the connected customer dashboard totals and next installment', function () {
    $subscription = Subscription::factory()->for($this->customer)->create(['contract_total' => '1000.00', 'amount_paid' => '400.00']);
    Payment::factory()->for($subscription)->create(['customer_id' => $this->customer->id, 'amount' => '400.00', 'status' => 'validated']);
    Installment::factory()->for($subscription)->create(['due_date' => now()->addDays(5), 'status' => 'upcoming']);
    $other = Subscription::factory()->create(['contract_total' => '9000.00']);
    Payment::factory()->for($other)->create(['customer_id' => $other->customer_id, 'amount' => '8000.00', 'status' => 'validated']);

    $this->actingAs($this->user)->get(route('portal.dashboard'))->assertOk()
        ->assertSee('400,00 USD')->assertSee('600,00 USD')->assertDontSee('8 000,00 USD');
});

it('lists only owned subscriptions payments installments and receipts', function () {
    $owned = Subscription::factory()->for($this->customer)->create(['subscription_number' => 'SUB-OWNED']);
    $foreign = Subscription::factory()->create(['subscription_number' => 'SUB-FOREIGN']);
    Payment::factory()->for($owned)->create(['customer_id' => $this->customer->id, 'payment_reference' => 'PAY-OWNED']);
    Payment::factory()->for($foreign)->create(['customer_id' => $foreign->customer_id, 'payment_reference' => 'PAY-FOREIGN']);
    Installment::factory()->for($owned)->create(['status' => 'upcoming']);
    Receipt::factory()->for(Payment::query()->where('payment_reference', 'PAY-OWNED')->firstOrFail())->create(['customer_id' => $this->customer->id, 'subscription_id' => $owned->id, 'receipt_number' => 'REC-OWNED']);

    $this->actingAs($this->user)->get(route('portal.subscriptions.index'))->assertSee('SUB-OWNED')->assertDontSee('SUB-FOREIGN');
    $this->get(route('portal.payments.index'))->assertSee('PAY-OWNED')->assertDontSee('PAY-FOREIGN');
    $this->get(route('portal.installments.index'))->assertOk();
    $this->get(route('portal.receipts.index'))->assertSee('REC-OWNED');
});

it('returns not found when a customer changes a subscription identifier', function () {
    $foreign = Subscription::factory()->create();

    $this->actingAs($this->user)->get(route('portal.subscriptions.show', $foreign))->assertNotFound();
});

it('requires a valid signed link and ownership to download receipts', function () {
    $ownedPayment = Payment::factory()->create(['customer_id' => $this->customer->id, 'subscription_id' => Subscription::factory()->for($this->customer)->create()->id]);
    $ownedReceipt = Receipt::factory()->for($ownedPayment)->create(['customer_id' => $this->customer->id, 'subscription_id' => $ownedPayment->subscription_id, 'pdf_path' => 'receipts/owned.pdf']);
    Storage::disk('local')->put('receipts/owned.pdf', '%PDF test');
    $foreignReceipt = Receipt::factory()->create(['pdf_path' => 'receipts/foreign.pdf']);
    Storage::disk('local')->put('receipts/foreign.pdf', '%PDF foreign');

    $this->actingAs($this->user)->get(route('portal.receipts.download', $ownedReceipt))->assertForbidden();
    $this->get(URL::temporarySignedRoute('portal.receipts.download', now()->addMinute(), $ownedReceipt))->assertDownload($ownedReceipt->receipt_number.'.pdf');
    $this->get(URL::temporarySignedRoute('portal.receipts.download', now()->addMinute(), $foreignReceipt))->assertNotFound();
});

it('updates allowed profile fields and audits the change', function () {
    $payload = ['first_name' => 'Aminata', 'last_name' => 'Mbuyi', 'middle_name' => null, 'phone' => '+243999000111', 'secondary_phone' => null, 'whatsapp' => null, 'email' => 'aminata@example.test', 'address' => 'Kinshasa', 'commune' => 'Gombe', 'city' => 'Kinshasa', 'country' => 'RDC'];

    $this->actingAs($this->user)->put(route('portal.profile.update'), $payload)->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('customers', ['id' => $this->customer->id, 'first_name' => 'Aminata', 'phone' => '+243999000111']);
    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'email' => 'aminata@example.test']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'portal.profile.updated', 'entity_id' => $this->customer->id]);
});

it('lets a client change their password after verifying the current password', function () {
    $this->user->update(['password' => Hash::make('CurrentPassword123!')]);

    $this->actingAs($this->user)->put(route('portal.profile.password.update'), [
        'current_password' => 'CurrentPassword123!',
        'password' => 'UpdatedPassword456!',
        'password_confirmation' => 'UpdatedPassword456!',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Hash::check('UpdatedPassword456!', $this->user->fresh()->password))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'portal.profile.password_changed',
        'user_id' => $this->user->id,
        'entity_id' => $this->user->id,
    ]);
});

it('rejects a client password change when the current password or confirmation is invalid', function () {
    $this->user->update(['password' => Hash::make('CurrentPassword123!')]);

    $this->actingAs($this->user)->put(route('portal.profile.password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'UpdatedPassword456!',
        'password_confirmation' => 'not-the-same',
    ])->assertSessionHasErrors(['current_password', 'password']);
});

it('rejects invalid profile data and users without portal permission', function () {
    $this->actingAs($this->user)->put(route('portal.profile.update'), ['first_name' => '', 'last_name' => '', 'phone' => ''])->assertSessionHasErrors(['first_name', 'last_name', 'phone']);

    $staff = User::factory()->create();
    $staff->roles()->attach(Role::query()->where('name', 'commercial')->firstOrFail());
    $this->actingAs($staff)->get(route('portal.dashboard'))->assertForbidden();
});
