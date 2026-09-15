<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $this->customer($request);
        $subscriptions = $customer->subscriptions()->with(['plot.avenue.neighborhood', 'paymentPlan'])
            ->withSum(['payments as validated_paid' => fn ($query) => $query->where('status', 'validated')], 'amount')
            ->latest()->get();
        $nextInstallment = Installment::query()->whereHas('subscription', fn ($query) => $query->whereBelongsTo($customer))
            ->whereIn('status', ['upcoming', 'due', 'partially_paid', 'overdue'])->orderBy('due_date')->first();

        return view('portal.dashboard', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
            'paid' => (float) Payment::query()->whereBelongsTo($customer)->where('status', 'validated')->sum('amount'),
            'remaining' => (float) $subscriptions->sum('balance'),
            'nextInstallment' => $nextInstallment,
        ]);
    }

    private function customer(Request $request): Customer
    {
        return $request->user()->customer()->firstOrFail();
    }
}
