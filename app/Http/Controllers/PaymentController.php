<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Subscription;
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
    public function index(Request $request): View
    {
        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? '');
        $subscriptions = Subscription::query()->with(['customer', 'plot', 'contract'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('subscription_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('customer_number', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('plot', fn ($query) => $query->where('reference', 'like', "%{$search}%"))
                    ->orWhereHas('contract', fn ($query) => $query->where('contract_number', 'like', "%{$search}%"));
            }))->latest()->paginate(20)->withQueryString();

        return view('payments.index', compact('subscriptions', 'search'));
    }

    public function create(Subscription $subscription, SettingService $settings): View
    {
        $subscription->load(['customer', 'plot', 'installments' => fn ($query) => $query->orderBy('due_date')]);

        return view('payments.create', [
            'subscription' => $subscription, 'idempotencyKey' => (string) Str::uuid(),
            'currency' => $settings->value('finance', 'currency', 'USD'),
            'paymentMethods' => $settings->stringList('finance', 'payment_methods', ['cash', 'bank_transfer', 'mobile_money', 'card', 'other']),
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
        $payment->load(['customer', 'subscription.plot', 'allocations.installment', 'receipt']);

        return view('payments.show', compact('payment'));
    }
}
