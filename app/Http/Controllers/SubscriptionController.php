<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Customer;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Subscription;
use App\Services\AuditService;
use App\Services\InstallmentScheduleService;
use App\Services\PaymentService;
use App\Services\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        return view('subscriptions.index', ['subscriptions' => Subscription::query()->with(['customer', 'plot.avenue.neighborhood', 'paymentPlan'])->latest()->paginate(20)]);
    }

    public function create(): View
    {
        $today = now()->toDateString();
        $preselectedCustomer = request()->integer('customer_id') > 0
            ? Customer::query()->find(request()->integer('customer_id'))
            : null;
        $preselectedPlot = request()->integer('plot_id') > 0
            ? Plot::query()->with('avenue.neighborhood')->find(request()->integer('plot_id'))
            : null;

        return view('subscriptions.create', [
            'customers' => Customer::query()->whereNot('status', 'archived')->orderBy('last_name')->get(),
            'plots' => Plot::query()->whereIn('commercial_status', ['available', 'reserved'])->with('avenue.neighborhood')->orderBy('reference')->get(),
            'paymentPlans' => PaymentPlan::query()->where('active', true)->where(fn ($query) => $query->whereNull('valid_from')->orWhere('valid_from', '<=', $today))->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', $today))->orderBy('total_price')->get(),
            'preselectedCustomer' => $preselectedCustomer,
            'preselectedPlot' => $preselectedPlot,
        ]);
    }

    public function store(
        StoreSubscriptionRequest $request,
        InstallmentScheduleService $scheduleService,
        AuditService $auditService,
        PaymentService $paymentService,
        SettingService $settings,
    ): RedirectResponse {
        $subscription = DB::transaction(function () use ($request, $scheduleService, $auditService, $paymentService, $settings): Subscription {
            $plot = Plot::query()->lockForUpdate()->findOrFail($request->integer('plot_id'));
            $plan = PaymentPlan::query()->lockForUpdate()->findOrFail($request->integer('payment_plan_id'));
            $subscriptionDate = CarbonImmutable::parse($request->date('subscription_date'));

            abort_unless(in_array($plot->commercial_status, ['available', 'reserved'], true), 422, __('Cette parcelle n\'est plus disponible.'));
            abort_unless($plan->active
                && ($plan->valid_from === null || CarbonImmutable::parse($plan->valid_from)->lte($subscriptionDate))
                && ($plan->valid_until === null || CarbonImmutable::parse($plan->valid_until)->gte($subscriptionDate)), 422, __('Cette formule n\'est pas valide à la date choisie.'));

            $startDate = CarbonImmutable::parse($request->date('start_date'));
            $subscription = Subscription::query()->create([
                ...$request->safe()->only(['customer_id', 'plot_id', 'payment_plan_id', 'subscription_date', 'start_date']),
                'commercial_status' => 'pending',
                'subscription_number' => 'SUB-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'expected_end_date' => $plan->duration_months > 0 ? $startDate->addMonths($plan->duration_months)->toDateString() : $startDate->toDateString(),
                'contract_total' => $plan->total_price, 'monthly_amount' => $plan->monthly_amount,
                'duration_months' => $plan->duration_months, 'created_by' => $request->user()->id,
            ]);

            // Parcelle → reserved (devient subscribed automatiquement au premier paiement).
            $plot->update(['commercial_status' => 'reserved']);

            $scheduleService->generate($subscription);
            $auditService->record($request->user(), 'subscription.created', $subscription, null, $subscription->getAttributes(), $request);

            // Acompte optionnel — si renseigné, enregistrement immédiat du premier paiement.
            if ($request->filled('deposit') && (float) $request->input('deposit') > 0) {
                $paymentService->record($subscription, $request->user(), [
                    'idempotency_key' => (string) Str::uuid(),
                    'payment_date' => now(),
                    'amount' => $request->input('deposit'),
                    'currency' => $settings->value('finance', 'currency', 'USD'),
                    'payment_method' => $request->input('deposit_method', 'cash'),
                    'notes' => 'Acompte initial à la souscription.',
                ]);
            }

            return $subscription;
        });

        return redirect()->route('subscriptions.show', $subscription)->with('status', __('Souscription créée.'));
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['customer', 'plot.avenue.neighborhood', 'paymentPlan', 'contract', 'installments', 'payments', 'receipts']);

        return view('subscriptions.show', compact('subscription'));
    }
}
