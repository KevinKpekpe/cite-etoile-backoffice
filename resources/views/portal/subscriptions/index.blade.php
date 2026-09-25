<x-layouts.app title="Mes souscriptions">
    <div class="resource-page portal-page">
        <header class="resource-heading"><div><p class="app-kicker">Mes acquisitions</p><h1 class="resource-heading__title">Mes souscriptions</h1><p class="resource-heading__description">Contrats, parcelles et soldes restant à régler.</p></div></header>
        <section class="resource-table"><div class="resource-table__header"><div><h2>Dossiers contractuels</h2><p>{{ $subscriptions->total() }} {{ Str::plural('souscription', $subscriptions->total()) }}</p></div></div><div class="table-responsive"><table class="table resource-data-table portal-data-table"><thead><tr><th>Référence</th><th>Parcelle</th><th>Localisation</th><th>Formule</th><th>Statut</th><th class="text-end">Reste</th></tr></thead><tbody>
            @forelse($subscriptions as $subscription)
                <tr><td><a class="resource-reference" href="{{ route('portal.subscriptions.show', $subscription) }}">{{ $subscription->subscription_number }}</a></td><td><strong>{{ $subscription->plot->reference }}</strong></td><td class="resource-data-table__secondary">{{ $subscription->plot->avenue->neighborhood->name }}</td><td>{{ $subscription->paymentPlan->name }}</td><td><span class="status-badge status-badge--{{ $subscription->commercial_status === 'active' ? 'active' : 'neutral' }}">{{ str($subscription->commercial_status)->replace('_', ' ')->title() }}</span></td><td class="record-money text-end">{{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</td></tr>
            @empty<tr><td colspan="6"><div class="resource-empty"><strong>Aucune souscription</strong><span>Aucun dossier contractuel disponible.</span></div></td></tr>@endforelse
        </tbody></table></div></section>
        @if($subscriptions->hasPages())<div class="resource-pagination">{{ $subscriptions->links() }}</div>@endif
    </div>
</x-layouts.app>
