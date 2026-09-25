<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Mail\UserCredentialsMail;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InstallmentScheduleService;
use App\Services\PaymentService;
use App\Services\ReferenceGenerator;
use App\Services\SettingService;
use App\Services\UserCredentialsMarkdownService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, InstallmentScheduleService $scheduleService): View
    {
        $scheduleService->syncOverdueStatuses();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:prospect,active,settled,suspended,archived,overdue'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));

        $query = Customer::query()
            ->with(['assignedAgent:id,first_name,last_name', 'subscriptions.plot.avenue.neighborhood', 'subscriptions.paymentPlan', 'subscriptions.installments'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('customer_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }));

        if (($filters['status'] ?? null) === 'overdue') {
            $query->whereHas('subscriptions.installments', fn ($q) => $q->where('status', 'overdue'));
        } elseif (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $customers = $query->latest()->paginate(10)->withQueryString();

        return view('customers.index', compact('customers', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $today = now()->toDateString();

        return view('customers.create', [
            'agents' => $this->agents(),
            'plots' => Plot::query()->where('commercial_status', 'available')->with('avenue.neighborhood')->orderBy('reference')->get(),
            'paymentPlans' => PaymentPlan::query()->where('active', true)
                ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today))
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today))
                ->orderBy('total_price')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreCustomerRequest $request,
        ReferenceGenerator $references,
        InstallmentScheduleService $scheduleService,
        AuditService $auditService,
        PaymentService $paymentService,
        SettingService $settings,
        UserCredentialsMarkdownService $markdownService,
    ): RedirectResponse {
        $customerFields = $request->safe()->only([
            'first_name', 'last_name', 'middle_name', 'gender', 'birth_date',
            'phone', 'secondary_phone', 'whatsapp', 'email', 'address',
            'commune', 'city', 'country', 'nationality', 'internal_notes',
            'status', 'assigned_to',
        ]);

        if ($request->hasFile('avatar')) {
            $customerFields['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        [$customer, $subscription] = DB::transaction(function () use (
            $request, $references, $customerFields, $scheduleService, $auditService, $markdownService
        ): array {
            $customer = Customer::query()->create([
                ...$customerFields,
                'customer_number' => $references->generate(Customer::class, 'customer_number', 'customer', 'CLI'),
                'created_by' => $request->user()->id,
            ]);
            $this->audit($request, 'customer.created', $customer, null, $customer->getAttributes());

            // Création automatique du compte utilisateur lié
            $email = $customerFields['email'] ?? null;
            if (blank($email)) {
                $email = strtolower(Str::slug($customer->customer_number)).'@client.cite-etoile.cd';
            }

            $temporaryPassword = Str::password(12);
            $customerRole = Role::query()->where('name', 'customer')->first();

            $clientUser = User::query()->create([
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $email,
                'phone' => $customer->phone,
                'password' => Hash::make($temporaryPassword),
                'avatar_path' => $customer->avatar_path,
                'status' => 'active',
                'must_change_password' => true,
            ]);

            if ($customerRole !== null) {
                $clientUser->roles()->syncWithoutDetaching([$customerRole->id]);
            }

            $customer->update(['user_id' => $clientUser->id]);

            $markdown = $markdownService->generate($clientUser, $temporaryPassword, 'Client Portail');
            session()->flash('user_credentials_markdown', $markdown);
            session()->flash('temporary_password', $temporaryPassword);

            try {
                Mail::to($clientUser->email)->send(new UserCredentialsMail($clientUser, $temporaryPassword, $markdown, 'Client Portail'));
            } catch (\Throwable) {
                // Ignore mail failure if mail driver is offline
            }

            $subscription = null;

            if ($request->filled('plot_id') && $request->filled('payment_plan_id')) {
                $plot = Plot::query()->lockForUpdate()->findOrFail($request->integer('plot_id'));
                $plan = PaymentPlan::query()->findOrFail($request->integer('payment_plan_id'));

                abort_unless(in_array($plot->commercial_status, ['available', 'reserved'], true), 422, 'Cette parcelle n\'est plus disponible.');

                $subscriptionDate = CarbonImmutable::parse($request->input('subscription_date') ?? now());
                $startDate = CarbonImmutable::parse($request->input('start_date') ?? now());

                $subscription = Subscription::query()->create([
                    'customer_id' => $customer->id,
                    'plot_id' => $plot->id,
                    'payment_plan_id' => $plan->id,
                    'subscription_number' => 'SUB-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                    'subscription_date' => $subscriptionDate->toDateString(),
                    'start_date' => $startDate->toDateString(),
                    'expected_end_date' => $plan->duration_months > 0
                        ? $startDate->addMonths($plan->duration_months)->toDateString()
                        : $startDate->toDateString(),
                    'contract_total' => $plan->total_price,
                    'monthly_amount' => $plan->monthly_amount,
                    'duration_months' => $plan->duration_months,
                    'commercial_status' => 'pending',
                    'created_by' => $request->user()->id,
                ]);

                $plot->update(['commercial_status' => 'reserved']);
                $scheduleService->generate($subscription);
                $auditService->record($request->user(), 'subscription.created', $subscription, null, $subscription->getAttributes(), $request);
            }

            return [$customer, $subscription];
        });

        // Si une souscription a été créée avec un acompte, on l'enregistre hors transaction principale.
        if ($subscription !== null && $request->filled('deposit') && (float) $request->input('deposit') > 0) {
            $payment = $paymentService->record($subscription, $request->user(), [
                'idempotency_key' => (string) Str::uuid(),
                'payment_date' => now(),
                'amount' => $request->input('deposit'),
                'currency' => $settings->value('finance', 'currency', 'USD'),
                'payment_method' => $request->input('deposit_method', 'cash'),
                'notes' => 'Acompte initial à la souscription.',
            ]);

            return redirect()
                ->route('payments.show', $payment)
                ->with('status', __('Client et souscription créés. Voici le reçu de l\'acompte.'));
        }

        // Souscription sans acompte → formulaire de paiement pré-rempli.
        if ($subscription !== null) {
            return redirect()
                ->route('payments.create', $subscription)
                ->with('status', __('Client et souscription créés. Encaissez le premier paiement.'));
        }

        // Pas de souscription → fiche client.
        return redirect()->route('customers.show', $customer)->with('status', __('Client créé.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer, InstallmentScheduleService $scheduleService): View
    {
        $scheduleService->syncOverdueStatuses();
        $customer->load([
            'assignedAgent:id,first_name,last_name', 'documents',
            'subscriptions' => fn ($query) => $query->with(['plot', 'paymentPlan', 'installments'])->latest(),
            'payments' => fn ($query) => $query->with('receipt')->latest('payment_date'),
            'receipts' => fn ($query) => $query->latest('issued_at'),
        ]);

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): View
    {
        return view('customers.edit', ['customer' => $customer, 'agents' => $this->agents()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->safe()->except(['avatar', 'remove_avatar']);

        if ($request->boolean('remove_avatar')) {
            if ($customer->avatar_path) {
                Storage::disk('public')->delete($customer->avatar_path);
            }
            $data['avatar_path'] = null;
        } elseif ($request->hasFile('avatar')) {
            if ($customer->avatar_path) {
                Storage::disk('public')->delete($customer->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        DB::transaction(function () use ($request, $customer, $data): void {
            $oldValues = $customer->only(array_keys($data));
            $customer->update($data);
            if ($customer->user) {
                $customer->user->update([
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'phone' => $customer->phone,
                    'email' => $customer->email ?: $customer->user->email,
                    'avatar_path' => $customer->avatar_path,
                ]);
            }
            $this->audit($request, 'customer.updated', $customer, $oldValues, $customer->only(array_keys($data)));
        });

        return redirect()->route('customers.show', $customer)->with('status', __('Client mis à jour.'));
    }

    /**
     * Soft-delete a customer.
     */
    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()?->can('customers.delete'), 403);

        DB::transaction(function () use ($request, $customer): void {
            $customer->delete();
            $this->audit($request, 'customer.deleted', $customer, $customer->only(['first_name', 'last_name', 'customer_number', 'email']), null);
        });

        return redirect()->route('customers.index')->with('status', "{$customer->first_name} {$customer->last_name} a été placé en corbeille.");
    }

    /**
     * List soft-deleted customers (corbeille).
     */
    public function trashed(Request $request): View
    {
        abort_unless($request->user()?->can('customers.delete'), 403);

        $customers = Customer::onlyTrashed()
            ->with(['assignedAgent:id,first_name,last_name'])
            ->latest('deleted_at')
            ->paginate(10);

        return view('customers.trashed', compact('customers'));
    }

    /**
     * Restore a soft-deleted customer.
     */
    public function restore(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()?->can('customers.restore'), 403);

        DB::transaction(function () use ($request, $customer): void {
            $customer->restore();
            $this->audit($request, 'customer.restored', $customer, null, $customer->only(['first_name', 'last_name', 'customer_number', 'email']));
        });

        return redirect()->route('customers.show', $customer)->with('status', __('Client restauré avec succès.'));
    }

    /**
     * Permanently delete a customer (super_admin only).
     */
    public function forceDelete(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()?->can('customers.force_delete'), 403);

        DB::transaction(function () use ($request, $customer): void {
            $this->audit($request, 'customer.force_deleted', $customer, $customer->only(['first_name', 'last_name', 'customer_number', 'email']), null);
            $customer->forceDelete();
        });

        return redirect()->route('customers.trashed')->with('status', __('Client supprimé définitivement.'));
    }

    /**
     * Archive a customer.
     */
    public function archive(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()?->can('customers.delete'), 403);
        $oldStatus = $customer->status;
        $customer->update(['status' => 'archived']);
        $this->audit($request, 'customer.archived', $customer, ['status' => $oldStatus], ['status' => 'archived']);

        return redirect()->route('customers.show', $customer)->with('status', __('Client archivé.'));
    }

    /** @return Collection<int, User> */
    private function agents(): Collection
    {
        return User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['super_admin', 'admin', 'commercial']))
            ->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function audit(Request $request, string $action, Customer $customer, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id, 'action' => $action, 'entity_type' => Customer::class,
            'entity_id' => $customer->id, 'old_values' => $oldValues, 'new_values' => $newValues,
            'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);
    }
}
