<x-layouts.app :title="$subscription->subscription_number">
    @php
        $isClosed = in_array($subscription->commercial_status, ['completed', 'cancelled', 'terminated'], true);
        $canPay = $subscription->commercial_status === 'active' && $subscription->financial_status !== 'paid' && !$isClosed;
        $commercialStatusLabels = [
            'pending' => 'En attente',
            'active' => 'En cours',
            'suspended' => 'Suspendue',
            'cancelled' => 'Annulée',
            'terminated' => 'Résiliée',
            'completed' => 'Terminée',
        ];
    @endphp

    <div class="customer-record">

        @if($subscription->financial_status === 'paid' || $subscription->commercial_status === 'completed')
            <div class="record-alert record-alert--success">
                <div><strong>Souscription soldée</strong><p>Tous les paiements ont été reçus. Aucun encaissement supplémentaire n’est possible.</p></div>
            </div>
        @elseif(in_array($subscription->commercial_status, ['cancelled', 'terminated'], true))
            <div class="record-alert record-alert--danger">
                <div><strong>Souscription {{ $subscription->commercial_status === 'cancelled' ? 'annulée' : 'résiliée' }}</strong><p>Aucun encaissement n’est possible sur ce dossier.</p></div>
            </div>
        @elseif($subscription->commercial_status === 'pending')
            <div class="record-alert record-alert--warning">
                <div><strong>En attente du premier versement</strong><p>La parcelle est réservée. La souscription sera activée dès réception de l’acompte.</p></div>
                @can('payments.create')
                    <a href="{{ route('payments.create', $subscription) }}" class="btn btn-app-primary resource-button">Encaisser l’acompte</a>
                @endcan
            </div>
        @elseif($subscription->commercial_status === 'active' && $subscription->duration_months > 0 && $nextInstallment)
            <div class="record-alert record-alert--info">
                <div>
                    <strong>Prochaine échéance : {{ $nextInstallment->due_date->translatedFormat('d F Y') }}</strong>
                    <p>{{ number_format((float) $nextInstallment->amount_due, 2, ',', ' ') }} USD attendus · solde total {{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</p>
                </div>
                @can('payments.create')
                    <a href="{{ route('payments.create', $subscription) }}" class="btn btn-app-primary resource-button">Encaisser le paiement</a>
                @endcan
            </div>
        @endif

        <header class="record-heading">
            <div class="record-heading__identity">
                <span class="record-heading__avatar" aria-hidden="true"><i class="bi bi-file-earmark-check"></i></span>
                <div>
                    <p class="app-kicker">{{ $subscription->subscription_number }}</p>
                    <h1>{{ $subscription->customer->first_name }} {{ $subscription->customer->last_name }}</h1>
                    <p>{{ $subscription->plot->reference }} · {{ $subscription->plot->avenue->neighborhood->name }}</p>
                </div>
            </div>
            <div class="resource-heading__actions">
                @can('customers.view')
                    <a href="{{ route('customers.show', $subscription->customer) }}" class="btn btn-outline-secondary resource-button">Fiche client</a>
                @endcan
                @can('installments.view')
                    <a href="{{ route('subscriptions.installments.index', $subscription) }}" class="btn btn-outline-secondary resource-button">Échéancier</a>
                @endcan
                @if($canPay)
                    @can('payments.create')
                        <a href="{{ route('payments.create', $subscription) }}" class="btn btn-app-primary resource-button">Encaisser</a>
                    @endcan
                @endif
            </div>
        </header>

        <div class="record-metrics record-metrics--bordered">
            <div>
                <span>Montant contractuel</span>
                <strong>{{ number_format((float) $subscription->contract_total, 2, ',', ' ') }} <small>USD</small></strong>
                <small>{{ $subscription->paymentPlan->name }}</small>
            </div>
            <div class="record-metric--success">
                <span>Montant encaissé</span>
                <strong>{{ number_format((float) $subscription->amount_paid, 2, ',', ' ') }} <small>USD</small></strong>
                <small>{{ $subscription->payments->count() }} paiement(s)</small>
            </div>
            <div class="{{ (float) $subscription->balance > 0 ? 'record-metric--danger' : 'record-metric--success' }}">
                <span>Solde restant</span>
                <strong>{{ number_format((float) $subscription->balance, 2, ',', ' ') }} <small>USD</small></strong>
                <small>{{ $subscription->installments->count() }} échéance(s)</small>
            </div>
        </div>

        <div class="detail-sheet">
            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Contrat</p><h2>Conditions figées</h2></div>
                <dl class="detail-grid">
                    <div><dt>Formule</dt><dd>{{ $subscription->paymentPlan->name }}</dd></div>
                    <div><dt>Type de règlement</dt><dd>{{ $subscription->duration_months > 0 ? 'Paiement échelonné' : 'Paiement comptant' }}</dd></div>
                    <div><dt>Mensualité</dt><dd>{{ $subscription->duration_months > 0 ? number_format((float) $subscription->monthly_amount, 2, ',', ' ').' USD' : 'Non applicable' }}</dd></div>
                    <div><dt>Durée</dt><dd>{{ $subscription->duration_months > 0 ? $subscription->duration_months.' mois' : 'Comptant' }}</dd></div>
                    <div><dt>Date de souscription</dt><dd>{{ $subscription->subscription_date?->format('d/m/Y') }}</dd></div>
                    <div><dt>Début de l’échéancier</dt><dd>{{ $subscription->start_date?->format('d/m/Y') }}</dd></div>
                </dl>
            </section>

            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Suivi</p><h2>Statuts du dossier</h2></div>
                <div>
                    <dl class="detail-grid">
                        <div><dt>Commercial</dt><dd><span class="status-badge status-badge--{{ $subscription->commercial_status }}">{{ $commercialStatusLabels[$subscription->commercial_status] ?? ucfirst($subscription->commercial_status) }}</span></dd></div>
                        <div><dt>Financier</dt><dd>{{ ucfirst($subscription->financial_status) }}</dd></div>
                        <div><dt>Administratif</dt><dd>{{ ucfirst($subscription->administrative_status) }}</dd></div>
                    </dl>
                    @if(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('super_admin'))
                        @can('subscriptions.update')
                            <details class="record-disclosure">
                                <summary>Modifier le statut commercial</summary>
                                <form method="POST" action="{{ route('subscriptions.status', $subscription) }}" class="record-inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="commercial_status" class="form-select">
                                        @foreach(['pending', 'active', 'suspended', 'cancelled', 'terminated', 'completed'] as $status)
                                            <option value="{{ $status }}" @selected($subscription->commercial_status === $status)>{{ $commercialStatusLabels[$status] }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-app-primary resource-button" type="submit">Mettre à jour</button>
                                </form>
                            </details>
                        @endcan
                    @endif
                </div>
            </section>

            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Document</p><h2>Dossier contrat</h2></div>
                <div>
                    @if($subscription->contract)
                        <dl class="detail-grid mb-3">
                            <div><dt>Numéro</dt><dd class="font-monospace">{{ $subscription->contract->contract_number }}</dd></div>
                            <div><dt>Statut</dt><dd>{{ ucfirst($subscription->contract->status) }}</dd></div>
                            <div><dt>Date de signature</dt><dd>{{ $subscription->contract->signed_at?->format('d/m/Y') ?? 'Non signée' }}</dd></div>
                        </dl>
                        @if($subscription->contract->document_path)
                            @can('documents.download')
                                <a href="{{ route('subscriptions.contract.download', [$subscription, $subscription->contract]) }}" class="resource-reference">Télécharger le contrat</a>
                            @endcan
                        @endif
                    @else
                        <p class="record-empty-copy">Aucun contrat n’est actuellement attaché à cette souscription.</p>
                    @endif

                    @can('subscriptions.update')
                        <form method="POST" enctype="multipart/form-data" action="{{ route('subscriptions.contract.store', $subscription) }}" class="record-upload">
                            @csrf
                            <input type="date" name="signed_at" value="{{ $subscription->contract?->signed_at?->format('Y-m-d') }}" class="form-control" aria-label="Date de signature">
                            <select name="status" class="form-select" aria-label="Statut du contrat">
                                @foreach(['draft', 'signed', 'cancelled', 'archived'] as $status)
                                    <option value="{{ $status }}" @selected($subscription->contract?->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <input type="file" name="document" accept=".pdf" class="form-control" aria-label="Document PDF">
                            <button class="btn btn-app-primary resource-button" type="submit">Enregistrer</button>
                        </form>
                    @endcan
                </div>
            </section>
        </div>

        <section class="resource-table" aria-labelledby="subscription-payments-title">
            <div class="resource-table__header">
                <div><h2 id="subscription-payments-title">Paiements et reçus</h2><p>{{ $subscription->payments->count() }} versement(s) enregistré(s)</p></div>
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>Référence</th><th>Date</th><th>Mode</th><th class="text-end">Montant</th><th class="text-end">Reçu</th></tr></thead>
                    <tbody>
                        @forelse($subscription->payments as $payment)
                            <tr>
                                <td><a href="{{ route('payments.show', $payment) }}" class="resource-reference">{{ $payment->payment_reference }}</a></td>
                                <td class="resource-data-table__secondary">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                <td class="resource-data-table__secondary">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td class="record-money text-end">{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                <td class="text-end">
                                    @if($payment->receipt)
                                        @can('receipts.download')
                                            <a href="{{ route('receipts.download', $payment->receipt) }}" class="btn btn-sm btn-outline-secondary">Télécharger</a>
                                        @endcan
                                    @else
                                        <span class="text-muted small">Non généré</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="resource-empty"><strong>Aucun paiement enregistré</strong><span>Les versements associés à cette souscription apparaîtront ici.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
