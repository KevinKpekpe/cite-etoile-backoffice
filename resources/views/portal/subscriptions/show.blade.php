<x-layouts.app :title="$subscription->subscription_number">
    <div class="customer-record portal-page">
        <header class="record-heading">
            <div><p class="app-kicker">Dossier contractuel</p><h1>{{ $subscription->subscription_number }}</h1><p>{{ $subscription->plot->reference }} · {{ $subscription->plot->avenue->neighborhood->name }}</p></div>
            <div class="resource-heading__actions"><a href="{{ route('portal.subscriptions.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left" aria-hidden="true"></i>Mes souscriptions</a><span class="status-badge status-badge--{{ $subscription->commercial_status === 'active' ? 'active' : 'neutral' }}">{{ str($subscription->commercial_status)->replace('_', ' ')->title() }}</span></div>
        </header>

        <section class="detail-sheet"><div class="detail-sheet__section"><div class="detail-sheet__header"><h2>Informations du contrat</h2><span>Référence {{ $subscription->subscription_number }}</span></div><dl class="detail-grid">
            <div><dt>Parcelle</dt><dd>{{ $subscription->plot->reference }}</dd></div><div><dt>Localisation</dt><dd>{{ $subscription->plot->avenue->name }}, {{ $subscription->plot->avenue->neighborhood->name }}</dd></div><div><dt>Formule</dt><dd>{{ $subscription->paymentPlan->name }}</dd></div><div><dt>Total contractuel</dt><dd class="record-money">{{ number_format((float) $subscription->contract_total, 2, ',', ' ') }} USD</dd></div><div><dt>Montant payé</dt><dd class="record-money text-success">{{ number_format((float) $subscription->amount_paid, 2, ',', ' ') }} USD</dd></div><div><dt>Reste à payer</dt><dd class="record-money">{{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</dd></div>
        </dl></div></section>

        <section class="resource-table"><div class="resource-table__header"><div><h2>Échéancier</h2><p>{{ $subscription->installments->count() }} {{ Str::plural('échéance', $subscription->installments->count()) }}</p></div></div><div class="table-responsive"><table class="table resource-data-table portal-data-table"><thead><tr><th>N°</th><th>Date</th><th class="text-end">Montant attendu</th><th class="text-end">Payé</th><th class="text-end">Solde</th><th>Statut</th></tr></thead><tbody>
            @forelse($subscription->installments as $installment)<tr class="{{ $installment->status === 'overdue' ? 'resource-data-table__row--attention' : '' }}"><td>{{ $installment->installment_number }}</td><td>{{ $installment->due_date->format('d/m/Y') }}</td><td class="record-money text-end">{{ number_format((float) $installment->amount_due, 2, ',', ' ') }} USD</td><td class="record-money text-end">{{ number_format((float) $installment->amount_paid, 2, ',', ' ') }} USD</td><td class="record-money text-end">{{ number_format((float) $installment->balance, 2, ',', ' ') }} USD</td><td><span class="status-badge status-badge--{{ in_array($installment->status, ['paid', 'settled']) ? 'active' : ($installment->status === 'overdue' ? 'danger' : 'neutral') }}">{{ str($installment->status)->replace('_', ' ')->title() }}</span></td></tr>
            @empty<tr><td colspan="6"><div class="resource-empty"><strong>Aucune échéance</strong><span>Le calendrier n’a pas encore été généré.</span></div></td></tr>@endforelse
        </tbody></table></div></section>
    </div>
</x-layouts.app>
