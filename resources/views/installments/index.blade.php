<x-layouts.app title="Échéancier">
    @php
        $totalExpected = (float) $subscription->installments->sum('amount_due');
        $totalPaid = (float) $subscription->installments->sum('amount_paid');
        $totalBalance = (float) $subscription->installments->sum('balance');
        $statusLabels = [
            'upcoming' => 'À venir',
            'due' => 'À payer',
            'partially_paid' => 'Partiellement payée',
            'paid' => 'Payée',
            'overdue' => 'En retard',
        ];
    @endphp

    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">{{ $subscription->subscription_number }}</p>
                <h1 class="resource-heading__title">Échéancier</h1>
                <p class="resource-heading__description">{{ $subscription->customer->first_name }} {{ $subscription->customer->last_name }} · parcelle {{ $subscription->plot->reference }}</p>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('subscriptions.show', $subscription) }}" class="btn btn-outline-secondary resource-button">Fiche souscription</a>
                @can('installments.manage')
                    @if($subscription->installments->isEmpty() && $subscription->duration_months > 0)
                        <form method="POST" action="{{ route('subscriptions.installments.generate', $subscription) }}">
                            @csrf
                            <button class="btn btn-app-primary resource-button" type="submit">Générer l’échéancier</button>
                        </form>
                    @endif
                @endcan
            </div>
        </header>

        @if($subscription->duration_months === 0)
            <div class="resource-empty resource-empty--standalone">
                <strong>Paiement comptant</strong>
                <span>Cette souscription ne comporte aucune échéance mensuelle.</span>
            </div>
        @else
            <div class="record-metrics record-metrics--bordered">
                <div><span>Total attendu</span><strong>{{ number_format($totalExpected, 2, ',', ' ') }} <small>USD</small></strong><small>{{ $subscription->installments->count() }} échéance(s)</small></div>
                <div class="record-metric--success"><span>Total payé</span><strong>{{ number_format($totalPaid, 2, ',', ' ') }} <small>USD</small></strong><small>Montant affecté</small></div>
                <div class="{{ $totalBalance > 0 ? 'record-metric--danger' : 'record-metric--success' }}"><span>Reste à payer</span><strong>{{ number_format($totalBalance, 2, ',', ' ') }} <small>USD</small></strong><small>Solde de l’échéancier</small></div>
            </div>

            <section class="resource-table" aria-labelledby="installments-table-title">
                <div class="resource-table__header">
                    <div><h2 id="installments-table-title">Calendrier des échéances</h2><p>Suivi chronologique des montants attendus et encaissés</p></div>
                </div>
                <div class="table-responsive">
                    <table class="table resource-data-table align-middle mb-0">
                        <thead><tr><th>N°</th><th>Date prévue</th><th class="text-end">Montant dû</th><th class="text-end">Montant payé</th><th class="text-end">Solde</th><th>Statut</th></tr></thead>
                        <tbody>
                            @forelse($subscription->installments as $installment)
                                <tr class="{{ $installment->status === 'overdue' ? 'resource-data-table__row--attention' : '' }}">
                                    <td><span class="resource-reference">{{ str_pad((string) $installment->installment_number, 2, '0', STR_PAD_LEFT) }}</span></td>
                                    <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                                    <td class="record-money text-end">{{ number_format((float) $installment->amount_due, 2, ',', ' ') }} USD</td>
                                    <td class="record-money text-end text-success">{{ number_format((float) $installment->amount_paid, 2, ',', ' ') }} USD</td>
                                    <td class="record-money text-end">{{ number_format((float) $installment->balance, 2, ',', ' ') }} USD</td>
                                    <td><span class="status-badge status-badge--{{ $installment->status === 'overdue' ? 'danger' : $installment->status }}">{{ $statusLabels[$installment->status] ?? ucfirst($installment->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="resource-empty"><strong>Échéancier non généré</strong><span>Utilisez l’action de génération pour créer le calendrier contractuel.</span></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
