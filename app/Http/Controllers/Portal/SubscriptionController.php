<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = $request->user()->customer()->firstOrFail()->subscriptions()
            ->with(['plot.avenue.neighborhood', 'paymentPlan'])->latest()->paginate(15);

        return view('portal.subscriptions.index', compact('subscriptions'));
    }

    public function show(Request $request, int $subscription): View
    {
        $subscription = $request->user()->customer()->firstOrFail()->subscriptions()
            ->with(['plot.avenue.neighborhood', 'paymentPlan', 'installments'])->findOrFail($subscription);

        return view('portal.subscriptions.show', compact('subscription'));
    }
}
