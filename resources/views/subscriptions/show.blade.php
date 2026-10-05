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
                <div><strong>{{ __("Souscription soldée") }}</strong><p>{{ __("Tous les paiements ont été reçus. Aucun encaissement supplémentaire n’est possible.") }}</p></div>
            </div>
        @elseif(in_array($subscription->commercial_status, ['cancelled', 'terminated'], true))
            <div class="record-alert record-alert--danger">
                <div><strong>Souscription {{ $subscription->commercial_status === 'cancelled' ? 'annulée' : 'résiliée' }}</strong><p>{{ __("Aucun encaissement n’est possible sur ce dossier.") }}</p></div>
            </div>
        @elseif($subscription->commercial_status === 'pending')
            <div class="record-alert record-alert--warning">
                <div><strong>{{ __("En attente du premier versement") }}</strong><p>{{ __("La parcelle est réservée. La souscription sera activée dès réception de l’acompte.") }}</p></div>
                @can('payments.create')
                    <a href="{{ route('payments.create', $subscription) }}" class="btn btn-app-primary resource-button">{{ __("Encaisser l’acompte") }}</a>
                @endcan
            </div>
        @elseif($subscription->commercial_status === 'active' && $subscription->duration_months > 0 && $nextInstallment)
            <div class="record-alert record-alert--info">
                <div>
                    <strong>Prochaine échéance : {{ $nextInstallment->due_date->translatedFormat('d F Y') }}</strong>
                    <p>{{ number_format((float) $nextInstallment->amount_due, 2, ',', ' ') }} USD attendus · solde total {{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</p>
                </div>
                @can('payments.create')
                    <a href="{{ route('payments.create', $subscription) }}" class="btn btn-app-primary resource-button">{{ __("Encaisser le paiement") }}</a>
                @endcan
            </div>
        @endif

        <header class="record-heading">
            <div class="record-heading__identity">
                <span class="record-heading__avatar" aria-hidden="true"><i class="bi bi-file-earmark-check"></i></span>
                <div>
                    <p class="app-kicker">{{ $subscription->subscription_number }}</p>
                    <h1>{{ $subscription->customer ? ($subscription->customer->first_name . ' ' . $subscription->customer->last_name) : 'Client non renseigné' }}</h1>
                    <p>{{ $subscription->plot?->reference }} · {{ $subscription->plot?->avenue?->neighborhood?->name }}</p>
                </div>
            </div>
            <div class="resource-heading__actions">
                @if($subscription->customer)
                    @can('customers.view')
                        <a href="{{ route('customers.show', $subscription->customer) }}" class="btn btn-outline-secondary resource-button">{{ __("Fiche client") }}</a>
                    @endcan
                @endif
                @can('installments.view')
                    <a href="{{ route('subscriptions.installments.index', $subscription) }}" class="btn btn-outline-secondary resource-button">{{ __("Échéancier") }}</a>
                @endcan
                @if($canPay)
                    @can('payments.create')
                        <a href="{{ route('payments.create', $subscription) }}" class="btn btn-app-primary resource-button">{{ __("Encaisser") }}</a>
                    @endcan
                @endif
            </div>
        </header>

        <div class="record-metrics record-metrics--bordered">
            <div>
                <span>{{ __("Montant contractuel") }}</span>
                <strong>{{ number_format((float) $subscription->contract_total, 2, ',', ' ') }} <small>USD</small></strong>
                <small>{{ $subscription->paymentPlan->name }}</small>
            </div>
            <div class="record-metric--success">
                <span>{{ __("Montant encaissé") }}</span>
                <strong>{{ number_format((float) $subscription->amount_paid, 2, ',', ' ') }} <small>USD</small></strong>
                <small>{{ $subscription->payments->count() }} paiement(s)</small>
            </div>
            <div class="{{ (float) $subscription->balance > 0 ? 'record-metric--danger' : 'record-metric--success' }}">
                <span>{{ __("Solde restant") }}</span>
                <strong>{{ number_format((float) $subscription->balance, 2, ',', ' ') }} <small>USD</small></strong>
                <small>{{ $subscription->installments->count() }} échéance(s)</small>
            </div>
        </div>

        <div class="detail-sheet">
            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">{{ __("Contrat") }}</p><h2>{{ __("Conditions figées") }}</h2></div>
                <dl class="detail-grid">
                    <div><dt>{{ __("Formule") }}</dt><dd>{{ $subscription->paymentPlan->name }}</dd></div>
                    <div><dt>{{ __("Type de règlement") }}</dt><dd>{{ $subscription->duration_months > 0 ? 'Paiement échelonné' : 'Paiement comptant' }}</dd></div>
                    <div><dt>Mensualité</dt><dd>{{ $subscription->duration_months > 0 ? number_format((float) $subscription->monthly_amount, 2, ',', ' ').' USD' : 'Non applicable' }}</dd></div>
                    <div><dt>{{ __("Durée") }}</dt><dd>{{ $subscription->duration_months > 0 ? $subscription->duration_months.' mois' : 'Comptant' }}</dd></div>
                    <div><dt>{{ __("Date de souscription") }}</dt><dd>{{ $subscription->subscription_date?->format('d/m/Y') }}</dd></div>
                    <div><dt>{{ __("Début de l’échéancier") }}</dt><dd>{{ $subscription->start_date?->format('d/m/Y') }}</dd></div>
                </dl>
            </section>

            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">{{ __("Suivi") }}</p><h2>{{ __("Statuts du dossier") }}</h2></div>
                <div>
                    <dl class="detail-grid">
                        <div><dt>{{ __("Commercial") }}</dt><dd><span class="status-badge status-badge--{{ $subscription->commercial_status }}">{{ __('statuses.'.$subscription->commercial_status) }}</span></dd></div>
                        <div><dt>{{ __("Financier") }}</dt><dd>{{ __('statuses.'.$subscription->financial_status) }}</dd></div>
                        <div><dt>{{ __("Administratif") }}</dt><dd>{{ __('statuses.'.$subscription->administrative_status) }}</dd></div>
                    </dl>
                    @if(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('super_admin'))
                        @can('subscriptions.update')
                            <details class="record-disclosure">
                                <summary>{{ __("Modifier le statut commercial") }}</summary>
                                <form method="POST" action="{{ route('subscriptions.status', $subscription) }}" class="record-inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="commercial_status" class="form-select">
                                        @foreach(['pending', 'active', 'suspended', 'cancelled', 'terminated', 'completed'] as $status)
                                            <option value="{{ $status }}" @selected($subscription->commercial_status === $status)>{{ $commercialStatusLabels[$status] }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-app-primary resource-button" type="submit">{{ __("Mettre à jour") }}</button>
                                </form>
                            </details>
                        @endcan
                    @endif
                </div>
            </section>

            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">{{ __("Document") }}</p><h2>{{ __("Dossier contrat") }}</h2></div>
                <div>
                    @if($subscription->contract)
                        <dl class="detail-grid mb-3">
                            <div><dt>{{ __("Numéro") }}</dt><dd class="font-monospace">{{ $subscription->contract->contract_number }}</dd></div>
                            <div><dt>{{ __("Statut") }}</dt><dd>{{ __('statuses.'.$subscription->contract->status) }}</dd></div>
                            <div><dt>{{ __("Date de signature") }}</dt><dd>{{ $subscription->contract->signed_at?->format('d/m/Y') ?? 'Non signée' }}</dd></div>
                        </dl>
                        @if($subscription->contract->document_path)
                            @can('documents.download')
                                <a href="{{ route('subscriptions.contract.download', [$subscription, $subscription->contract]) }}" class="resource-reference">{{ __("Télécharger le contrat") }}</a>
                            @endcan
                        @endif
                    @else
                        <p class="record-empty-copy">{{ __("Aucun contrat n’est actuellement attaché à cette souscription.") }}</p>
                    @endif

                    @can('subscriptions.update')
                        <form method="POST" enctype="multipart/form-data" action="{{ route('subscriptions.contract.store', $subscription) }}" class="record-upload">
                            @csrf
                            <input type="date" name="signed_at" value="{{ $subscription->contract?->signed_at?->format('Y-m-d') }}" class="form-control" aria-:label="__('Date de signature')">
                            <select name="status" class="form-select" aria-label="Statut du contrat">
                                @foreach(['draft', 'signed', 'cancelled', 'archived'] as $status)
                                    <option value="{{ $status }}" @selected($subscription->contract?->status === $status)>{{ __('statuses.'.$status) }}</option>
                                @endforeach
                            </select>
                            <input type="file" name="document" accept=".pdf" class="form-control" aria-label="Document PDF">
                            <button class="btn btn-app-primary resource-button" type="submit">{{ __("Enregistrer") }}</button>
                        </form>
                    @endcan
                </div>
            </section>
        </div>

        <section class="resource-table" aria-labelledby="ancillary-fees-title">
            <div class="resource-table__header">
                <div><h2 id="ancillary-fees-title">{{ __("Frais connexes") }}</h2><p>{{ $subscription->ancillaryFees->count() }} frais ou échéance(s) enregistrés</p></div>
                @if(!$subscription->ancillaryFees->contains('fee_type', 'survey') && !in_array($subscription->commercial_status, ['cancelled', 'terminated'], true))
                    @can('subscriptions.update')
                        <form method="POST" action="{{ route('subscriptions.bornage.realize', $subscription) }}">
                            @csrf
                            <button class="btn btn-outline-secondary resource-button" type="submit">{{ __("Marquer le bornage réalisé") }}</button>
                        </form>
                    @endcan
                @endif
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>{{ __("Frais") }}</th><th>{{ __("Échéance") }}</th><th class="text-end">{{ __("Montant") }}</th><th class="text-end">{{ __("Payé") }}</th><th class="text-end">{{ __("Solde") }}</th><th>{{ __("Statut") }}</th><th class="text-end">{{ __("Action / reçu") }}</th></tr></thead>
                    <tbody>
                        @forelse($subscription->ancillaryFees as $fee)
                            <tr>
                                <td><strong>{{ $fee->label() }}</strong>@if($fee->fee_type === 'development' && $subscription->development_payment_mode === 'monthly')<small class="d-block text-muted">Mensualité {{ $fee->installment_number }} sur 36</small>@endif</td>
                                <td class="resource-data-table__secondary">{{ $fee->due_date->format('d/m/Y') }}</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->amount_due, 2, ',', ' ') }} USD</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->amount_paid, 2, ',', ' ') }} USD</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->balance, 2, ',', ' ') }} USD</td>
                                <td><span class="status-badge status-badge--{{ $fee->status === 'paid' ? 'active' : ($fee->status === 'overdue' ? 'danger' : 'neutral') }}">{{ __('statuses.'.$fee->status) }}</span></td>
                                <td class="text-end">
                                    @if((float) $fee->balance > 0)
                                        @can('payments.create')<a href="{{ route('payments.ancillary.create', $fee) }}" class="btn btn-sm btn-outline-secondary">{{ __("Encaisser") }}</a>@endcan
                                    @endif
                                    @foreach($fee->payments->where('status', 'validated') as $feePayment)
                                        @if($feePayment->receipt)
                                            @can('receipts.download')<a href="{{ route('receipts.download', $feePayment->receipt) }}" class="resource-reference ms-2">{{ $feePayment->receipt->receipt_number }}</a>@endcan
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="resource-empty"><strong>{{ __("Aucun frais connexe généré") }}</strong><span>{{ __("Les frais cadastraux et d’aménagement apparaîtront à la signature du contrat. Le bornage sera ajouté quand sa réalisation sera enregistrée.") }}</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="resource-table" aria-labelledby="subscription-payments-title">
            <div class="resource-table__header">
                <div><h2 id="subscription-payments-title">{{ __("Paiements et reçus") }}</h2><p>{{ $subscription->payments->count() }} versement(s) enregistré(s)</p></div>
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>{{ __("Référence") }}</th><th>{{ __("Date") }}</th><th>{{ __("Mode") }}</th><th class="text-end">{{ __("Montant") }}</th><th class="text-end">{{ __("Reçu") }}</th></tr></thead>
                    <tbody>
                        @forelse($subscription->payments as $payment)
                            <tr>
                                <td><a href="{{ route('payments.show', $payment) }}" class="resource-reference">{{ $payment->payment_reference }}</a>@if($payment->ancillaryFee)<small class="d-block text-muted">{{ $payment->ancillaryFee->label() }}</small>@endif</td>
                                <td class="resource-data-table__secondary">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                <td class="resource-data-table__secondary">{{ __('payment_methods.'.$payment->payment_method) }}</td>
                                <td class="record-money text-end">{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                <td class="text-end">
                                    @if($payment->receipt)
                                        @can('receipts.download')
                                            <a href="{{ route('receipts.download', $payment->receipt) }}" class="btn btn-sm btn-outline-secondary">{{ __("Télécharger") }}</a>
                                        @endcan
                                    @else
                                        <span class="text-muted small">{{ __("Non généré") }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="resource-empty"><strong>{{ __("Aucun paiement enregistré") }}</strong><span>{{ __("Les versements associés à cette souscription apparaîtront ici.") }}</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
