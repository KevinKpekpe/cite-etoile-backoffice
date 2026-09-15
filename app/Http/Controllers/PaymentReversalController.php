<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReversePaymentRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentReversalController extends Controller
{
    public function __invoke(ReversePaymentRequest $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        $paymentService->reverse($payment, $request->user(), $request->string('reason')->toString());

        return back()->with('status', __('Paiement extourné et soldes recalculés.'));
    }
}
