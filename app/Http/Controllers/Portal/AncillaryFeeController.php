<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AncillaryFee;
use App\Services\AncillaryFeeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AncillaryFeeController extends Controller
{
    public function index(Request $request, AncillaryFeeService $fees): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $fees->syncOverdueStatuses();
        $ancillaryFees = AncillaryFee::query()
            ->whereHas('subscription', fn ($query) => $query->whereBelongsTo($customer))
            ->with(['subscription.plot', 'payments.receipt'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate(30);

        return view('portal.ancillary-fees.index', compact('ancillaryFees'));
    }
}
