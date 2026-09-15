<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Installment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $installments = Installment::query()->whereHas('subscription', fn ($query) => $query->whereBelongsTo($customer))
            ->with('subscription.plot')->orderBy('due_date')->paginate(20);

        return view('portal.installments.index', compact('installments'));
    }
}
