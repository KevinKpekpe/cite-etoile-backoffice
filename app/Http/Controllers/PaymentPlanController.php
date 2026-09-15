<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentPlanRequest;
use App\Models\AuditLog;
use App\Models\PaymentPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentPlanController extends Controller
{
    public function index(): View
    {
        return view('payment-plans.index', ['paymentPlans' => PaymentPlan::query()->withCount('subscriptions')->orderBy('total_price')->get()]);
    }

    public function create(): View
    {
        return view('payment-plans.form', ['paymentPlan' => new PaymentPlan]);
    }

    public function store(StorePaymentPlanRequest $request): RedirectResponse
    {
        $paymentPlan = PaymentPlan::query()->create([...$request->validated(), 'active' => $request->boolean('active')]);
        $this->audit($request, 'payment_plan.created', $paymentPlan, null, $paymentPlan->getAttributes());

        return redirect()->route('payment-plans.index')->with('status', __('Formule créée.'));
    }

    public function edit(PaymentPlan $paymentPlan): View
    {
        return view('payment-plans.form', compact('paymentPlan'));
    }

    public function update(StorePaymentPlanRequest $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        $attributes = [...$request->validated(), 'active' => $request->boolean('active')];
        $oldValues = $paymentPlan->only(array_keys($attributes));
        $paymentPlan->update($attributes);
        $this->audit($request, 'payment_plan.updated', $paymentPlan, $oldValues, $paymentPlan->only(array_keys($attributes)));

        return redirect()->route('payment-plans.index')->with('status', __('Formule mise à jour. Les contrats existants conservent leurs conditions.'));
    }

    public function destroy(Request $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        $paymentPlan->update(['active' => false]);
        $this->audit($request, 'payment_plan.disabled', $paymentPlan, ['active' => true], ['active' => false]);

        return redirect()->route('payment-plans.index')->with('status', __('Formule désactivée.'));
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function audit(Request $request, string $action, PaymentPlan $paymentPlan, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::query()->create(['user_id' => $request->user()?->id, 'action' => $action, 'entity_type' => PaymentPlan::class, 'entity_id' => $paymentPlan->id, 'old_values' => $oldValues, 'new_values' => $newValues, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
    }
}
