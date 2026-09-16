<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSubscriptionStatusRequest;
use App\Models\Subscription;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionStatusController extends Controller
{
    public function __invoke(UpdateSubscriptionStatusRequest $request, Subscription $subscription, AuditService $auditService): RedirectResponse
    {
        $transitions = [
            'draft' => ['pending', 'cancelled'], 'pending' => ['active', 'cancelled'],
            'active' => ['suspended', 'completed', 'terminated'], 'suspended' => ['active', 'terminated'],
            'cancelled' => [], 'terminated' => [], 'completed' => [],
        ];
        $newStatus = $request->string('commercial_status')->toString();

        if (! in_array($newStatus, $transitions[$subscription->commercial_status], true)) {
            throw ValidationException::withMessages(['commercial_status' => __('Transition de statut non autorisée.')]);
        }

        DB::transaction(function () use ($request, $subscription, $newStatus, $auditService): void {
            $oldStatus = $subscription->commercial_status;
            $subscription->update(['commercial_status' => $newStatus]);

            if (in_array($newStatus, ['cancelled', 'terminated'], true)) {
                $subscription->plot()->update(['commercial_status' => 'available']);
            } elseif (in_array($newStatus, ['pending', 'active', 'suspended'], true)) {
                $subscription->plot()->update(['commercial_status' => 'subscribed']);
            }

            $auditService->record($request->user(), 'subscription.status_changed', $subscription, ['commercial_status' => $oldStatus], ['commercial_status' => $newStatus], $request);
        });

        return back()->with('status', __('Statut mis à jour.'));
    }
}
