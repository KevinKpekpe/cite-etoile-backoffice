<x-layouts.app :title="$payment->payment_reference">
    <div class="customer-record">
        @if($nextInstallment && $payment->subscription->commercial_status === 'active')
            <div class="record-alert record-alert--info">
                <div>
                    <strong>Prochaine échéance le {{ $nextInstallment->due_date->translatedFormat('d F Y') }}</strong>
                    <p>{{ number_format((float) $nextInstallment->amount_due, 2, ',', ' ') }} USD attendus · solde restant {{ number_format((float) $payment->subscription->balance, 2, ',', ' ') }} USD</p>
                </div>
                @can('payments.create')
                    <a href="{{ route('payments.create', $payment->subscription) }}" class="btn btn-app-primary resource-button">{{ __("Nouvel encaissement") }}</a>
                @endcan
            </div>
        @elseif(!$payment->ancillaryFee && ($payment->subscription->financial_status === 'paid' || $payment->subscription->commercial_status === 'completed'))
            <div class="record-alert record-alert--success"><div><strong>Souscription soldée</strong><p>{{ __("Tous les paiements attendus ont été reçus.") }}</p></div></div>
        @endif

        <header class="record-heading">
            <div class="record-heading__identity">
                <span class="record-heading__avatar" aria-hidden="true"><i class="bi bi-credit-card"></i></span>
                <div>
                    <p class="app-kicker">{{ $payment->payment_reference }}</p>
                    <h1>{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</h1>
                    <p>{{ $payment->customer->first_name }} {{ $payment->customer->last_name }} · {{ $payment->subscription->plot->reference }}@if($payment->ancillaryFee) · {{ $payment->ancillaryFee->label() }}@endif</p>
                </div>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('subscriptions.show', $payment->subscription) }}" class="btn btn-outline-secondary resource-button">Fiche souscription</a>
                @if($payment->receipt)
                    <a href="{{ route('receipts.show', $payment->receipt) }}" class="btn btn-app-primary resource-button">{{ __("Voir le reçu") }}</a>
                @endif
            </div>
        </header>

        <div class="detail-sheet">
            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Transaction</p><h2>{{ __("Détail du paiement") }}</h2></div>
                <dl class="detail-grid">
                    <div><dt>{{ __("Statut") }}</dt><dd><span class="status-badge status-badge--{{ $payment->status }}">{{ __('statuses.'.$payment->status) }}</span></dd></div>
                    <div><dt>{{ __("Date et heure") }}</dt><dd>{{ $payment->payment_date->format('d/m/Y H:i:s') }}</dd></div>
                    <div><dt>Mode de règlement</dt><dd>{{ __('payment_methods.'.$payment->payment_method) }}</dd></div>
                    <div><dt>Référence externe</dt><dd>{{ $payment->transaction_reference ?: 'Non renseignée' }}</dd></div>
                    <div><dt>{{ $payment->ancillaryFee ? 'Frais connexe' : 'Formule' }}</dt><dd>{{ $payment->ancillaryFee?->label() ?? $payment->subscription->paymentPlan->name }}</dd></div>
                    <div><dt>{{ __("Parcelle") }}</dt><dd>{{ $payment->subscription->plot->reference }} · {{ $payment->subscription->plot->avenue->neighborhood->name }}</dd></div>
                    <div><dt>Cumul encaissé</dt><dd class="record-money">{{ number_format((float) ($payment->ancillaryFee?->amount_paid ?? $payment->subscription->amount_paid), 2, ',', ' ') }} USD</dd></div>
                    <div><dt>{{ __("Solde restant") }}</dt><dd class="record-money">{{ number_format((float) ($payment->ancillaryFee?->balance ?? $payment->subscription->balance), 2, ',', ' ') }} USD</dd></div>
                    <div><dt>Observations</dt><dd>{{ $payment->notes ?: 'Aucune observation' }}</dd></div>
                </dl>
            </section>

            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Ventilation</p><h2>{{ $payment->ancillaryFee ? 'Frais réglé' : 'Affectation aux échéances' }}</h2></div>
                <div class="record-list">
                    @if($payment->ancillaryFee)
                        <div class="record-list__item"><span><strong>{{ $payment->ancillaryFee->label() }} · échéance {{ $payment->ancillaryFee->installment_number }}</strong><small>{{ $payment->ancillaryFee->due_date->format('d/m/Y') }}</small></span><span class="record-money">{{ number_format((float) $payment->amount, 2, ',', ' ') }} USD</span></div>
                    @else
                    @forelse($payment->allocations as $allocation)
                        <div class="record-list__item">
                            <span><strong>Échéance {{ $allocation->installment->installment_number }}</strong><small>{{ $allocation->installment->due_date->format('d/m/Y') }}</small></span>
                            <span class="record-money">{{ number_format((float) $allocation->amount, 2, ',', ' ') }} USD</span>
                        </div>
                    @empty
                        <p class="record-empty-copy">Paiement comptant sans échéance mensuelle.</p>
                    @endforelse
                    @endif
                </div>
            </section>

            @can('payments.cancel')
                @if($payment->status === 'validated')
                    <section class="detail-section">
                        <div class="detail-section__heading"><p class="app-kicker">Correction</p><h2>{{ __("Extourner le paiement") }}</h2></div>
                        <form method="POST" action="{{ route('payments.reverse', $payment) }}" class="reversal-form" data-confirm="Confirmer l’extourne de ce paiement ?">
                            @csrf
                            @method('PATCH')
                            <label class="form-field">
                                <span class="form-field__label">Motif de l’extourne<span class="text-danger ms-1 fw-bold">*</span></span>
                                <textarea name="reason" required minlength="10" rows="3" class="form-control" placeholder="Décrivez précisément la raison de l’extourne"></textarea>
                            </label>
                            <button class="btn btn-outline-danger resource-button" type="submit">{{ __("Extourner le paiement") }}</button>
                        </form>
                    </section>
                @endif
            @endcan
        </div>
    </div>
</x-layouts.app>
