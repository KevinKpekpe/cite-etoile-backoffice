<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Subscription;
use App\Services\InstallmentScheduleService;
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

        return view('subscriptions.create', [
            'customers' => Customer::query()->whereNot('status', 'archived')->orderBy('last_name')->get(),
            'plots' => Plot::query()->whereIn('commercial_status', ['available', 'reserved'])->with('avenue.neighborhood')->orderBy('reference')->get(),
            'paymentPlans' => PaymentPlan::query()->where('active', true)->where(fn ($query) => $query->whereNull('valid_from')->orWhere('valid_from', '<=', $today))->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', $today))->orderBy('total_price')->get(),
        ]);
    }

    public function store(StoreSubscriptionRequest $request, InstallmentScheduleService $scheduleService): RedirectResponse
    {
        $subscription = DB::transaction(function () use ($request, $scheduleService): Subscription {
            $plot = Plot::query()->lockForUpdate()->findOrFail($request->integer('plot_id'));
            $plan = PaymentPlan::query()->lockForUpdate()->findOrFail($request->integer('payment_plan_id'));
            $subscriptionDate = CarbonImmutable::parse($request->date('subscription_date'));

            abort_unless(in_array($plot->commercial_status, ['available', 'reserved'], true), 422, __('Cette parcelle n’est plus disponible.'));
            abort_unless($plan->active
                && ($plan->valid_from === null || CarbonImmutable::parse($plan->valid_from)->lte($subscriptionDate))
                && ($plan->valid_until === null || CarbonImmutable::parse($plan->valid_until)->gte($subscriptionDate)), 422, __('Cette formule n’est pas valide à la date choisie.'));

            $startDate = CarbonImmutable::parse($request->date('start_date'));
            $subscription = Subscription::query()->create([
                ...$request->safe()->only(['customer_id', 'plot_id', 'payment_plan_id', 'subscription_date', 'start_date', 'commercial_status']),
                'subscription_number' => 'SUB-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'expected_end_date' => $plan->duration_months > 0 ? $startDate->addMonths($plan->duration_months)->toDateString() : $startDate->toDateString(),
                'contract_total' => $plan->total_price, 'monthly_amount' => $plan->monthly_amount,
                'duration_months' => $plan->duration_months, 'created_by' => $request->user()->id,
            ]);
            $plot->update(['commercial_status' => 'subscribed']);
            $scheduleService->generate($subscription);
            $this->audit($request, 'subscription.created', $subscription, null, $subscription->getAttributes());

            return $subscription;
        });

        return redirect()->route('subscriptions.show', $subscription)->with('status', __('Souscription créée.'));
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['customer', 'plot.avenue.neighborhood', 'paymentPlan', 'contract', 'installments', 'payments', 'receipts']);

        return view('subscriptions.show', compact('subscription'));
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function audit(StoreSubscriptionRequest $request, string $action, Subscription $subscription, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::query()->create(['user_id' => $request->user()->id, 'action' => $action, 'entity_type' => Subscription::class, 'entity_id' => $subscription->id, 'old_values' => $oldValues, 'new_values' => $newValues, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
    }
}
