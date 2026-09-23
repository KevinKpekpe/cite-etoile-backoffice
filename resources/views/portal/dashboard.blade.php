<x-layouts.app title="Mon espace">
    <div class="resource-page portal-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Espace client</p><h1 class="resource-heading__title">Bonjour {{ $customer->first_name }}</h1><p class="resource-heading__description">Situation actuelle de vos acquisitions et de votre échéancier.</p></div>
            <a href="{{ route('portal.profile.edit') }}" class="btn btn-outline"><i class="bi bi-person" aria-hidden="true"></i>Mon profil</a>
        </header>

        <section class="report-metrics portal-metrics" aria-label="Synthèse financière">
            <div class="report-metric--success"><span>Total payé</span><strong>{{ number_format($paid, 2, ',', ' ') }} <em>USD</em></strong><small>Paiements validés</small></div>
            <div><span>Reste à payer</span><strong>{{ number_format($remaining, 2, ',', ' ') }} <em>USD</em></strong><small>Solde de vos contrats</small></div>
            <div><span>Prochaine échéance</span><strong>{{ $nextInstallment ? $nextInstallment->due_date->format('d/m/Y') : 'Aucune' }}</strong><small>{{ $nextInstallment ? number_format((float) $nextInstallment->balance, 2, ',', ' ').' USD attendus' : 'Échéancier à jour' }}</small></div>
        </section>

        <section class="resource-table">
            <div class="resource-table__header"><div><h2>Mes parcelles et formules</h2><p>{{ $subscriptions->count() }} {{ Str::plural('souscription', $subscriptions->count()) }}</p></div><a href="{{ route('portal.subscriptions.index') }}" class="btn btn-outline btn-sm">Tout consulter</a></div>
            <div class="table-responsive"><table class="table resource-data-table portal-data-table"><thead><tr><th>Référence</th><th>Parcelle</th><th>Localisation</th><th>Formule</th><th class="text-end">Payé</th><th class="text-end">Reste</th></tr></thead><tbody>
                @forelse($subscriptions as $subscription)
                    <tr><td><a href="{{ route('portal.subscriptions.show', $subscription) }}" class="resource-reference">{{ $subscription->subscription_number }}</a></td><td><strong>{{ $subscription->plot->reference }}</strong></td><td class="resource-data-table__secondary">{{ $subscription->plot->avenue->neighborhood->name }}</td><td>{{ $subscription->paymentPlan->name }}</td><td class="record-money text-end">{{ number_format((float) $subscription->validated_paid, 2, ',', ' ') }} USD</td><td class="record-money text-end">{{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</td></tr>
                @empty<tr><td colspan="6"><div class="resource-empty"><strong>Aucune souscription</strong><span>Vos acquisitions apparaîtront ici.</span></div></td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
</x-layouts.app>
