<x-layouts.app title="{{ __('portal.my_subscriptions') }}">
    <div class="resource-page portal-page">
        <header class="resource-heading"><div><p class="app-kicker">{{ __('portal.my_acquisitions') }}</p><h1 class="resource-heading__title">Mes souscriptions</h1><p class="resource-heading__description">{{ __('portal.subscriptions_description') }}</p></div></header>
        <section class="resource-table"><div class="resource-table__header"><div><h2>{{ __('portal.contract_files') }}</h2><p>{{ trans_choice('portal.subscription_count', $subscriptions->total()) }}</p></div></div><div class="table-responsive"><table class="table resource-data-table portal-data-table"><thead><tr><th>{{ __('portal.reference') }}</th><th>{{ __('portal.plot') }}</th><th>{{ __('portal.location') }}</th><th>{{ __('portal.plan') }}</th><th>{{ __("Statut") }}</th><th class="text-end">{{ __('portal.remaining') }}</th></tr></thead><tbody>
            @forelse($subscriptions as $subscription)
                <tr><td><a class="resource-reference" href="{{ route('portal.subscriptions.show', $subscription) }}">{{ $subscription->subscription_number }}</a></td><td><strong>{{ $subscription->plot->reference }}</strong></td><td class="resource-data-table__secondary">{{ $subscription->plot->avenue->neighborhood->name }}</td><td>{{ $subscription->paymentPlan->name }}</td><td><span class="status-badge status-badge--{{ $subscription->commercial_status === 'active' ? 'active' : 'neutral' }}">{{ __('statuses.'.$subscription->commercial_status) }}</span></td><td class="record-money text-end">{{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</td></tr>
            @empty<tr><td colspan="6"><div class="resource-empty"><strong>{{ __('portal.none') }} souscription</strong><span>{{ __('portal.no_contracts_available') }}</span></div></td></tr>@endforelse
        </tbody></table></div></section>
        @if($subscriptions->hasPages())<div class="resource-pagination">{{ $subscriptions->links() }}</div>@endif
    </div>
</x-layouts.app>
