<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSubscriptionStatusRequest;
use App\Models\AuditLog;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionStatusController extends Controller
{
    public function __invoke(UpdateSubscriptionStatusRequest $request, Subscription $subscription): RedirectResponse
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

        DB::transaction(function () use ($request, $subscription, $newStatus): void {
            $oldStatus = $subscription->commercial_status;
            $subscription->update(['commercial_status' => $newStatus]);

            if (in_array($newStatus, ['cancelled', 'terminated'], true)) {
                $subscription->plot()->update(['commercial_status' => 'available']);
            } elseif (in_array($newStatus, ['pending', 'active', 'suspended'], true)) {
                $subscription->plot()->update(['commercial_status' => 'subscribed']);
            }

            AuditLog::query()->create(['user_id' => $request->user()->id, 'action' => 'subscription.status_changed', 'entity_type' => Subscription::class, 'entity_id' => $subscription->id, 'old_values' => ['commercial_status' => $oldStatus], 'new_values' => ['commercial_status' => $newStatus], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        });

        return back()->with('status', __('Statut mis à jour.'));
    }
}
