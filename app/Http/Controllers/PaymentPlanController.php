<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentPlanRequest;
use App\Models\AuditLog;
use App\Models\PaymentPlan;
use App\Services\ReferenceGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentPlanController extends Controller
{
    public function index(): View
    {
        return view('payment-plans.index', ['paymentPlans' => PaymentPlan::query()->withCount('subscriptions')->orderBy('total_price')->paginate(10)]);
    }

    public function create(): View
    {
        return view('payment-plans.form', ['paymentPlan' => new PaymentPlan]);
    }

    public function store(StorePaymentPlanRequest $request, ReferenceGenerator $generator): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = $generator->generate(PaymentPlan::class, 'code', 'payment_plans', 'PLN');
        }

        $paymentPlan = PaymentPlan::query()->create([...$data, 'active' => $request->boolean('active')]);
        $this->audit($request, 'payment_plan.created', $paymentPlan, null, $paymentPlan->getAttributes());

        return redirect()->route('payment-plans.index')->with('status', __('Formule créée.'));
    }

    public function edit(PaymentPlan $paymentPlan): View
    {
        return view('payment-plans.form', compact('paymentPlan'));
    }

    public function update(StorePaymentPlanRequest $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            unset($data['code']);
        }

        $attributes = [...$data, 'active' => $request->boolean('active')];
        $oldValues = $paymentPlan->only(array_keys($attributes));
        $paymentPlan->update($attributes);
        $this->audit($request, 'payment_plan.updated', $paymentPlan, $oldValues, $paymentPlan->only(array_keys($attributes)));

        return redirect()->route('payment-plans.index')->with('status', __('Formule mise à jour. Les contrats existants conservent leurs conditions.'));
    }

    public function destroy(Request $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        abort_unless($request->user()?->can('payment_plans.manage'), 403);

        $paymentPlan->delete();
        $this->audit($request, 'payment_plan.deleted', $paymentPlan, $paymentPlan->only(['code', 'name']), null);

        return redirect()->route('payment-plans.index')->with('status', __('Formule placée en corbeille.'));
    }

    public function trashed(Request $request): View
    {
        abort_unless($request->user()?->can('payment_plans.manage'), 403);

        $paymentPlans = PaymentPlan::onlyTrashed()
            ->withCount('subscriptions')
            ->latest('deleted_at')
            ->get();

        return view('payment-plans.trashed', compact('paymentPlans'));
    }

    public function restore(Request $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        abort_unless($request->user()?->can('payment_plans.restore'), 403);

        $paymentPlan->restore();
        $this->audit($request, 'payment_plan.restored', $paymentPlan, null, $paymentPlan->only(['code', 'name']));

        return redirect()->route('payment-plans.index')->with('status', __('Formule restaurée avec succès.'));
    }

    public function forceDelete(Request $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        abort_unless($request->user()?->can('payment_plans.force_delete'), 403);
        abort_if($paymentPlan->subscriptions()->exists(), 409, __('Cette formule possède des souscriptions associées et ne peut pas être supprimée définitivement.'));

        $this->audit($request, 'payment_plan.force_deleted', $paymentPlan, $paymentPlan->only(['code', 'name']), null);
        $paymentPlan->forceDelete();

        return redirect()->route('payment-plans.trashed')->with('status', __('Formule supprimée définitivement.'));
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
