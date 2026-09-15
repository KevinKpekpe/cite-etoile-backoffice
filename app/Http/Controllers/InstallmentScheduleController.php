<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Subscription;
use App\Services\InstallmentScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstallmentScheduleController extends Controller
{
    public function store(Request $request, Subscription $subscription, InstallmentScheduleService $scheduleService): RedirectResponse
    {
        abort_unless($request->user()?->can('installments.manage'), 403);
        $installments = $scheduleService->generate($subscription);
        $scheduleService->refreshStatuses($subscription);
        AuditLog::query()->create(['user_id' => $request->user()->id, 'action' => 'installment_schedule.generated', 'entity_type' => Subscription::class, 'entity_id' => $subscription->id, 'new_values' => ['installments_count' => $installments->count()], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return redirect()->route('subscriptions.installments.index', $subscription)->with('status', __('Échéancier généré.'));
    }
}
