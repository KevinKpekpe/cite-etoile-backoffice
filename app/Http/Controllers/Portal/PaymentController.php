<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = $request->user()->customer()->firstOrFail()->payments()
            ->with(['subscription.plot', 'receipt'])->latest('payment_date')->paginate(20);

        return view('portal.payments.index', compact('payments'));
    }
}
