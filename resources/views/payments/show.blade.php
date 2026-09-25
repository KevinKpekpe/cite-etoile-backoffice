<x-layouts.app :title="$payment->payment_reference">
    <div class="customer-record">
        @if($nextInstallment && $payment->subscription->commercial_status === 'active')
            <div class="record-alert record-alert--info">
                <div>
                    <strong>Prochaine échéance le {{ $nextInstallment->due_date->translatedFormat('d F Y') }}</strong>
                    <p>{{ number_format((float) $nextInstallment->amount_due, 2, ',', ' ') }} USD attendus · solde restant {{ number_format((float) $payment->subscription->balance, 2, ',', ' ') }} USD</p>
                </div>
                @can('payments.create')
                    <a href="{{ route('payments.create', $payment->subscription) }}" class="btn btn-app-primary resource-button">Nouvel encaissement</a>
                @endcan
            </div>
        @elseif($payment->subscription->financial_status === 'paid' || $payment->subscription->commercial_status === 'completed')
            <div class="record-alert record-alert--success"><div><strong>Souscription soldée</strong><p>Tous les paiements attendus ont été reçus.</p></div></div>
        @endif

        <header class="record-heading">
            <div class="record-heading__identity">
                <span class="record-heading__avatar" aria-hidden="true"><i class="bi bi-credit-card"></i></span>
                <div>
                    <p class="app-kicker">{{ $payment->payment_reference }}</p>
                    <h1>{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</h1>
                    <p>{{ $payment->customer->first_name }} {{ $payment->customer->last_name }} · {{ $payment->subscription->plot->reference }}</p>
                </div>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('subscriptions.show', $payment->subscription) }}" class="btn btn-outline-secondary resource-button">Fiche souscription</a>
                @if($payment->receipt)
                    <a href="{{ route('receipts.show', $payment->receipt) }}" class="btn btn-app-primary resource-button">Voir le reçu</a>
                @endif
            </div>
        </header>

        <div class="detail-sheet">
            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Transaction</p><h2>Détail du paiement</h2></div>
                <dl class="detail-grid">
                    <div><dt>Statut</dt><dd><span class="status-badge status-badge--{{ $payment->status }}">{{ ucfirst($payment->status) }}</span></dd></div>
                    <div><dt>Date et heure</dt><dd>{{ $payment->payment_date->format('d/m/Y H:i:s') }}</dd></div>
                    <div><dt>Mode de règlement</dt><dd>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</dd></div>
                    <div><dt>Référence externe</dt><dd>{{ $payment->transaction_reference ?: 'Non renseignée' }}</dd></div>
                    <div><dt>Formule</dt><dd>{{ $payment->subscription->paymentPlan->name }}</dd></div>
                    <div><dt>Parcelle</dt><dd>{{ $payment->subscription->plot->reference }} · {{ $payment->subscription->plot->avenue->neighborhood->name }}</dd></div>
                    <div><dt>Cumul encaissé</dt><dd class="record-money">{{ number_format((float) $payment->subscription->amount_paid, 2, ',', ' ') }} USD</dd></div>
                    <div><dt>Solde restant</dt><dd class="record-money">{{ number_format((float) $payment->subscription->balance, 2, ',', ' ') }} USD</dd></div>
                    <div><dt>Observations</dt><dd>{{ $payment->notes ?: 'Aucune observation' }}</dd></div>
                </dl>
            </section>

            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Ventilation</p><h2>Affectation aux échéances</h2></div>
                <div class="record-list">
                    @forelse($payment->allocations as $allocation)
                        <div class="record-list__item">
                            <span><strong>Échéance {{ $allocation->installment->installment_number }}</strong><small>{{ $allocation->installment->due_date->format('d/m/Y') }}</small></span>
                            <span class="record-money">{{ number_format((float) $allocation->amount, 2, ',', ' ') }} USD</span>
                        </div>
                    @empty
                        <p class="record-empty-copy">Paiement comptant sans échéance mensuelle.</p>
                    @endforelse
                </div>
            </section>

            @can('payments.cancel')
                @if($payment->status === 'validated')
                    <section class="detail-section">
                        <div class="detail-section__heading"><p class="app-kicker">Correction</p><h2>Extourner le paiement</h2></div>
                        <form method="POST" action="{{ route('payments.reverse', $payment) }}" class="reversal-form" data-confirm="Confirmer l’extourne de ce paiement ?">
                            @csrf
                            @method('PATCH')
                            <label class="form-field">
                                <span class="form-field__label">Motif de l’extourne<span class="text-danger ms-1 fw-bold">*</span></span>
                                <textarea name="reason" required minlength="10" rows="3" class="form-control" placeholder="Décrivez précisément la raison de l’extourne"></textarea>
                            </label>
                            <button class="btn btn-outline-danger resource-button" type="submit">Extourner le paiement</button>
                        </form>
                    </section>
                @endif
            @endcan
        </div>
    </div>
</x-layouts.app>
