<x-layouts.app title="Nouveau paiement">
    @php
        $overdueInstallments = $subscription->installments->where('status', 'overdue')->sortBy('due_date');
        $overdueTotal = (float) $overdueInstallments->sum('balance');
        $partialInstallments = $subscription->installments->where('status', 'partially_paid')->sortBy('due_date');
        $partialTotal = (float) $partialInstallments->sum('balance');
        $dueInstallments = $subscription->installments->where('status', 'due')->sortBy('due_date');
        $dueTotal = (float) $dueInstallments->sum('balance');
        $suggestedAmount = $overdueTotal > 0
            ? $overdueTotal + $partialTotal + $dueTotal
            : ($partialTotal > 0 ? $partialTotal : ($nextInstallment ? (float) $nextInstallment->balance : null));
    @endphp

    <div class="form-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">{{ $subscription->subscription_number }}</p>
                <h1 class="resource-heading__title">Enregistrer un paiement</h1>
                <p class="resource-heading__description">{{ $subscription->customer->first_name }} {{ $subscription->customer->last_name }} · {{ $subscription->plot->reference }} · {{ $subscription->plot->avenue->neighborhood->name }}</p>
            </div>
            <a href="{{ route('subscriptions.show', $subscription) }}" class="btn btn-outline-secondary resource-button">Retour à la souscription</a>
        </header>

        <div class="record-metrics record-metrics--bordered">
            <div><span>Montant contractuel</span><strong>{{ number_format((float) $subscription->contract_total, 2, ',', ' ') }} <small>{{ $currency }}</small></strong><small>{{ $subscription->paymentPlan->name }}</small></div>
            <div class="record-metric--success"><span>Déjà encaissé</span><strong>{{ number_format((float) $subscription->amount_paid, 2, ',', ' ') }} <small>{{ $currency }}</small></strong><small>Paiements validés</small></div>
            <div class="record-metric--danger"><span>Solde restant</span><strong>{{ number_format((float) $subscription->balance, 2, ',', ' ') }} <small>{{ $currency }}</small></strong><small>Avant ce versement</small></div>
        </div>

        <section class="detail-sheet">
            <div class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Échéancier</p><h2>Situation actuelle</h2></div>
                <div class="installment-summary">
                    @if($overdueInstallments->isNotEmpty())
                        <div class="installment-summary__group installment-summary__group--danger">
                            <div class="installment-summary__heading"><strong>En retard</strong><span>{{ number_format($overdueTotal, 2, ',', ' ') }} {{ $currency }}</span></div>
                            @foreach($overdueInstallments as $installment)
                                <div class="installment-summary__row"><span>Échéance {{ $installment->installment_number }} · {{ $installment->due_date->format('d/m/Y') }}</span><strong>{{ number_format((float) $installment->balance, 2, ',', ' ') }} {{ $currency }}</strong></div>
                            @endforeach
                        </div>
                    @endif
                    @if($partialInstallments->isNotEmpty())
                        <div class="installment-summary__group installment-summary__group--warning">
                            <div class="installment-summary__heading"><strong>Partiellement payées</strong><span>{{ number_format($partialTotal, 2, ',', ' ') }} {{ $currency }}</span></div>
                            @foreach($partialInstallments as $installment)
                                <div class="installment-summary__row"><span>Échéance {{ $installment->installment_number }} · {{ $installment->due_date->format('d/m/Y') }}</span><strong>{{ number_format((float) $installment->balance, 2, ',', ' ') }} {{ $currency }}</strong></div>
                            @endforeach
                        </div>
                    @endif
                    @if($dueInstallments->isNotEmpty())
                        <div class="installment-summary__group installment-summary__group--info">
                            <div class="installment-summary__heading"><strong>À payer ce mois</strong><span>{{ number_format($dueTotal, 2, ',', ' ') }} {{ $currency }}</span></div>
                            @foreach($dueInstallments as $installment)
                                <div class="installment-summary__row"><span>Échéance {{ $installment->installment_number }} · {{ $installment->due_date->format('d/m/Y') }}</span><strong>{{ number_format((float) $installment->balance, 2, ',', ' ') }} {{ $currency }}</strong></div>
                            @endforeach
                        </div>
                    @endif
                    @if($overdueInstallments->isEmpty() && $partialInstallments->isEmpty() && $dueInstallments->isEmpty())
                        <div class="installment-summary__empty">
                            {{ $nextInstallment ? 'Prochaine échéance le '.$nextInstallment->due_date->format('d/m/Y').' : '.number_format((float) $nextInstallment->balance, 2, ',', ' ').' '.$currency : 'Toutes les échéances sont à jour.' }}
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <form method="POST" enctype="multipart/form-data" action="{{ route('payments.store') }}" class="form-page__content" id="payment-form">
            @csrf
            <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
            <input type="hidden" name="currency" value="{{ $currency }}">

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">01</span>
                    <div><h2>Détails du versement</h2><p>Date, montant et canal utilisé pour l’encaissement.</p></div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <x-auth-input name="payment_date" label="Date du paiement" type="datetime-local" :value="old('payment_date', now()->format('Y-m-d\TH:i'))" required />
                        <label class="form-field">
                            <span class="form-field__label">Montant ({{ $currency }})<span class="text-danger ms-1 fw-bold">*</span></span>
                            <div class="payment-amount-field">
                                <input type="number" name="amount" id="amount-input" step="0.01" min="0.01" value="{{ old('amount', $suggestedAmount ? number_format($suggestedAmount, 2, '.', '') : '') }}" class="form-control" required>
                                @if($suggestedAmount)
                                    <button type="button" data-suggested-amount="{{ number_format($suggestedAmount, 2, '.', '') }}">Montant suggéré</button>
                                @endif
                            </div>
                            @error('amount')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                        <label class="form-field">
                            <span class="form-field__label">Mode de règlement<span class="text-danger ms-1 fw-bold">*</span></span>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ __('payment_methods.'.$method) === 'payment_methods.'.$method ? ucfirst(str_replace('_', ' ', $method)) : __('payment_methods.'.$method) }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                        <x-auth-input name="transaction_reference" label="Référence externe" :value="old('transaction_reference')" placeholder="N° virement ou transaction mobile" />
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">02</span>
                    <div><h2>Justificatif et observations</h2><p>Ajoutez, si nécessaire, la preuve associée à l’opération.</p></div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <label class="form-field form-grid__wide">
                            <span class="form-field__label">Preuve de paiement</span>
                            <input type="file" name="proof" id="proof" accept=".pdf,.jpg,.jpeg,.png,.webp" class="form-control">
                            @error('proof')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                        <label class="form-field form-grid__wide">
                            <span class="form-field__label">Commentaire</span>
                            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Observations sur l’encaissement">{{ old('notes') }}</textarea>
                            @error('notes')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <a href="{{ route('subscriptions.show', $subscription) }}" class="btn btn-outline-secondary resource-button">Annuler</a>
                <button type="submit" class="btn btn-app-primary resource-button">Valider le paiement</button>
            </div>
        </form>
    </div>
</x-layouts.app>
