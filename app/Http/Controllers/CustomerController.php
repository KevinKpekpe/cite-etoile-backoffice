<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InstallmentScheduleService;
use App\Services\PaymentService;
use App\Services\ReferenceGenerator;
use App\Services\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            ->with(['assignedAgent:id,first_name,last_name', 'subscriptions.installments'])
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

        $customers = $query->latest()->paginate(20)->withQueryString();

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
    ): RedirectResponse {
        $customerFields = $request->safe()->only([
            'first_name', 'last_name', 'middle_name', 'gender', 'birth_date',
            'phone', 'secondary_phone', 'whatsapp', 'email', 'address',
            'commune', 'city', 'country', 'nationality', 'internal_notes',
            'status', 'assigned_to',
        ]);

        [$customer, $subscription] = DB::transaction(function () use (
            $request, $references, $customerFields, $scheduleService, $auditService
        ): array {
            $customer = Customer::query()->create([
                ...$customerFields,
                'customer_number' => $references->generate(Customer::class, 'customer_number', 'customer', 'CLI'),
                'created_by' => $request->user()->id,
            ]);
            $this->audit($request, 'customer.created', $customer, null, $customer->getAttributes());

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
            'payments' => fn ($query) => $query->latest('payment_date'),
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
        DB::transaction(function () use ($request, $customer): void {
            $oldValues = $customer->only(array_keys($request->validated()));
            $customer->update($request->validated());
            $this->audit($request, 'customer.updated', $customer, $oldValues, $customer->only(array_keys($request->validated())));
        });

        return redirect()->route('customers.show', $customer)->with('status', __('Client mis à jour.'));
    }

    /**
     * Remove the specified resource from storage.
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
