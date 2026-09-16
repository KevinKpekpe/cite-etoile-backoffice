<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Subscription;
use App\Services\ReferenceGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function store(StoreContractRequest $request, Subscription $subscription, ReferenceGenerator $references): RedirectResponse
    {
        $attributes = $request->safe()->only(['signed_at', 'status']);

        if ($request->hasFile('document')) {
            $attributes['document_path'] = $request->file('document')->store("subscriptions/{$subscription->id}/contracts", 'local');
        }

        $contract = Contract::query()->updateOrCreate(
            ['subscription_id' => $subscription->id],
            [...$attributes, 'contract_number' => Contract::query()->where('subscription_id', $subscription->id)->value('contract_number') ?? $references->generate(Contract::class, 'contract_number', 'contract', 'CTR')],
        );
        AuditLog::query()->create(['user_id' => $request->user()->id, 'action' => 'contract.saved', 'entity_type' => Contract::class, 'entity_id' => $contract->id, 'new_values' => ['subscription_id' => $subscription->id, 'status' => $contract->status], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', __('Dossier contrat enregistré.'));
    }

    public function download(Request $request, Subscription $subscription, Contract $contract): StreamedResponse
    {
        abort_unless($request->user()?->can('documents.download'), 403);
        abort_unless($contract->subscription_id === $subscription->id && $contract->document_path !== null, 404);

        return Storage::disk('local')->download($contract->document_path, $contract->contract_number.'.pdf');
    }
}
