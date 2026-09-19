<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\InstallmentScheduleService;
use App\Services\PaymentService;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request, InstallmentScheduleService $scheduleService): View
    {
        $scheduleService->syncOverdueStatuses();

        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? '');
        $subscriptions = Subscription::query()->with(['customer', 'plot', 'contract', 'installments'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('subscription_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('customer_number', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('plot', fn ($query) => $query->where('reference', 'like', "%{$search}%"))
                    ->orWhereHas('contract', fn ($query) => $query->where('contract_number', 'like', "%{$search}%"));
            }))->latest()->paginate(20)->withQueryString();

        $recentPayments = Payment::query()
            ->with(['customer', 'subscription.plot', 'receipt'])
            ->latest('payment_date')
            ->take(30)
            ->get();

        $overdueInstallments = Installment::query()->where('status', 'overdue')->get();
        $overdueCount = $overdueInstallments->count();
        $overdueTotal = (float) $overdueInstallments->sum('balance');

        return view('payments.index', compact('subscriptions', 'recentPayments', 'search', 'overdueCount', 'overdueTotal'));
    }

    public function create(Subscription $subscription, SettingService $settings): View
    {
        $subscription->load(['customer', 'plot.avenue.neighborhood', 'paymentPlan', 'installments' => fn ($query) => $query->orderBy('due_date')]);

        // Garde-fous : pas de paiement sur une souscription close.
        if ($subscription->financial_status === 'paid') {
            return redirect()->route('subscriptions.show', $subscription)
                ->with('warning', 'Cette souscription est intégralement réglée. Aucun paiement supplémentaire n\'est possible.');
        }
        if (in_array($subscription->commercial_status, ['completed', 'cancelled', 'terminated'], true)) {
            return redirect()->route('subscriptions.show', $subscription)
                ->with('warning', 'Cette souscription est clôturée. Aucun paiement n\'est possible.');
        }

        $nextInstallment = $subscription->installments
            ->whereIn('status', ['overdue', 'due', 'upcoming'])
            ->sortBy('due_date')
            ->first();

        return view('payments.create', [
            'subscription' => $subscription, 'idempotencyKey' => (string) Str::uuid(),
            'currency' => $settings->value('finance', 'currency', 'USD'),
            'paymentMethods' => $settings->stringList('finance', 'payment_methods', ['cash', 'bank_transfer', 'mobile_money', 'card', 'other']),
            'nextInstallment' => $nextInstallment,
        ]);
    }

    public function store(StorePaymentRequest $request, PaymentService $paymentService): RedirectResponse
    {
        $subscription = Subscription::query()->findOrFail($request->integer('subscription_id'));
        $data = $request->safe()->except('proof');
        $proofPath = $request->file('proof')?->store("subscriptions/{$subscription->id}/payment-proofs", 'local');

        try {
            $payment = $paymentService->record($subscription, $request->user(), [...$data, 'proof_path' => $proofPath]);
        } catch (Throwable $exception) {
            if ($proofPath !== null) {
                Storage::disk('local')->delete($proofPath);
            }
            throw $exception;
        }

        return redirect()->route('payments.show', $payment)->with('status', __('Paiement enregistré et affecté.'));
    }

    public function show(Payment $payment): View
    {
        $payment->load(['customer', 'subscription.plot.avenue.neighborhood', 'subscription.paymentPlan', 'subscription.installments', 'allocations.installment', 'receipt']);

        $nextInstallment = $payment->subscription?->installments
            ->whereIn('status', ['overdue', 'due', 'upcoming'])
            ->sortBy('due_date')
            ->first();

        return view('payments.show', compact('payment', 'nextInstallment'));
    }
}
