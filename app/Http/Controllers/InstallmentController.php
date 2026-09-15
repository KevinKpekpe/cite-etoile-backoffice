<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\InstallmentScheduleService;
use Illuminate\Contracts\View\View;

class InstallmentController extends Controller
{
    public function index(Subscription $subscription, InstallmentScheduleService $scheduleService): View
    {
        $scheduleService->refreshStatuses($subscription);
        $subscription->load(['customer', 'plot', 'installments' => fn ($query) => $query->orderBy('installment_number')]);

        return view('installments.index', compact('subscription'));
    }
}
