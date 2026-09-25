<x-layouts.app title="Encaissements">
    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Gestion financière</p>
                <h1 class="resource-heading__title">Encaissements</h1>
                <p class="resource-heading__description">Sélectionnez ou recherchez un dossier pour enregistrer un versement et émettre les reçus.</p>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('subscriptions.index', ['status' => 'overdue']) }}" class="btn btn-outline-danger resource-button">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Retards ({{ $overdueCount }})
                </a>
            </div>
        </header>

        <form method="GET" action="{{ route('payments.index') }}" class="resource-filters">
            <div class="resource-filters__search">
                <label for="payment-search" class="form-label">Rechercher un dossier</label>
                <input id="payment-search" name="search" value="{{ $search }}" class="form-control" placeholder="Nom client, téléphone, n° souscription, contrat ou parcelle...">
            </div>
            <div class="resource-filters__actions">
                @if(filled($search))
                    <a href="{{ route('payments.index') }}" class="btn btn-link resource-filter-reset">Réinitialiser</a>
                @endif
                <button class="btn btn-app-primary resource-button" type="submit">Rechercher</button>
            </div>
        </form>

        {{-- Table des dossiers souscriptions à encaisser --}}
        <section class="resource-table" aria-labelledby="payment-table-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="payment-table-title">Dossiers éligibles aux encaissements</h2>
                    <p>{{ $subscriptions->total() }} {{ Str::plural('dossier', $subscriptions->total()) }}</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Souscription</th>
                            <th scope="col">Client</th>
                            <th scope="col">Parcelle</th>
                            <th scope="col">Échéance / Retard</th>
                            <th scope="col" class="text-end">Contractuel</th>
                            <th scope="col" class="text-end">Total Payé</th>
                            <th scope="col" class="text-end">Solde restant</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $subscription)
                            @php
                                $subOverdue = $subscription->installments->where('status', 'overdue');
                                $subOverdueCount = $subOverdue->count();
                                $subOverdueTotal = $subOverdue->sum('balance');
                                $canPay = $subscription->financial_status !== 'paid' && !in_array($subscription->commercial_status, ['completed', 'cancelled', 'terminated'], true);
                            @endphp
                            <tr class="{{ $subOverdueCount > 0 ? 'resource-data-table__row--attention' : '' }}">
                                <td>
                                    <a class="resource-reference" href="{{ route('subscriptions.show', $subscription) }}">
                                        {{ $subscription->subscription_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($subscription->customer)
                                        <a href="{{ route('customers.show', $subscription->customer) }}" class="text-decoration-none font-bold text-dark">
                                            {{ $subscription->customer->first_name }} {{ $subscription->customer->last_name }}
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($subscription->plot)
                                        <a class="resource-reference" href="{{ route('plots.show', $subscription->plot) }}">
                                            {{ $subscription->plot->reference }}
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($subOverdueCount > 0)
                                        <div>
                                            <span class="status-badge status-badge--danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $subOverdueCount }} retard(s)</span>
                                            <small class="d-block text-danger font-semibold mt-1">{{ number_format((float) $subOverdueTotal, 2, ',', ' ') }} USD</small>
                                        </div>
                                    @elseif($subscription->financial_status === 'paid')
                                        <span class="status-badge status-badge--active"><i class="bi bi-check-circle-fill me-1"></i> Soldée</span>
                                    @else
                                        <span class="status-badge status-badge--neutral">À jour</span>
                                    @endif
                                </td>
                                <td class="record-money text-end">{{ number_format((float) $subscription->contract_total, 2, ',', ' ') }} USD</td>
                                <td class="record-money text-end text-success font-bold">{{ number_format((float) $subscription->amount_paid, 2, ',', ' ') }} USD</td>
                                <td class="record-money text-end font-bold">{{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        <a href="{{ route('subscriptions.show', $subscription) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Voir les détails">
                                            Voir
                                        </a>
                                        @if($canPay)
                                            @can('payments.create')
                                                <a href="{{ route('payments.create', $subscription) }}"
                                                   class="btn btn-sm {{ $subOverdueCount > 0 ? 'btn-outline-danger' : 'btn-outline-success' }} py-1 px-2" title="Encaisser">
                                                    Encaisser
                                                </a>
                                            @endcan
                                        @else
                                            <span class="text-muted small">Clôturé</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="resource-empty">
                                        <strong>Aucun dossier trouvé</strong>
                                        <span>Recherchez un autre dossier souscripteur.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($subscriptions->hasPages())
            <div class="resource-pagination">{{ $subscriptions->links() }}</div>
        @endif

        {{-- Section Historique des versements & Reçus PDF --}}
        <section class="resource-table mt-4" aria-labelledby="recent-payments-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="recent-payments-title"><i class="bi bi-receipt me-2 text-primary"></i> Historique des récents versements & Reçus</h2>
                    <p>Téléchargez les reçus / factures PDF certifiés des encaissements effectués.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Réf. Paiement</th>
                            <th scope="col">Client</th>
                            <th scope="col">Parcelle</th>
                            <th scope="col">Date & Heure</th>
                            <th scope="col">Mode</th>
                            <th scope="col" class="text-end">Montant</th>
                            <th scope="col" class="text-end">Reçu / Facture PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr>
                                <td>
                                    <a href="{{ route('payments.show', $payment) }}" class="resource-reference">
                                        {{ $payment->payment_reference }}
                                    </a>
                                </td>
                                <td>
                                    @if($payment->customer)
                                        <a href="{{ route('customers.show', $payment->customer) }}" class="text-decoration-none font-bold text-dark">
                                            {{ $payment->customer->first_name }} {{ $payment->customer->last_name }}
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->subscription?->plot)
                                        <a href="{{ route('plots.show', $payment->subscription->plot) }}" class="resource-reference">
                                            {{ $payment->subscription->plot->reference }}
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                <td><span class="status-badge status-badge--neutral">{{ ucfirst($payment->payment_method) }}</span></td>
                                <td class="record-money text-end font-bold">{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                <td class="text-end">
                                    @if($payment->receipt)
                                        @can('receipts.download')
                                            <a href="{{ route('receipts.download', $payment->receipt) }}" class="btn btn-sm btn-outline-dark py-1 px-2" title="Télécharger le reçu">
                                                <i class="bi bi-download me-1"></i> Reçu {{ $payment->receipt->receipt_number }}
                                            </a>
                                        @endcan
                                    @else
                                        <span class="text-muted small">Aucun reçu</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="resource-empty">
                                        <strong>Aucun versement récent</strong>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
