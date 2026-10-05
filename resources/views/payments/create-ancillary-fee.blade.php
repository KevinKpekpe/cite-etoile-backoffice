<x-layouts.app title="Encaisser un frais connexe">
    <div class="form-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">{{ $ancillaryFee->subscription->subscription_number }}</p>
                <h1 class="resource-heading__title">Encaisser : {{ $ancillaryFee->label() }}</h1>
                <p class="resource-heading__description">{{ $ancillaryFee->subscription->customer->first_name }} {{ $ancillaryFee->subscription->customer->last_name }} · {{ $ancillaryFee->subscription->plot->reference }} · Échéance {{ $ancillaryFee->due_date->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('subscriptions.show', $ancillaryFee->subscription) }}" class="btn btn-outline-secondary resource-button">{{ __("Retour à la souscription") }}</a>
        </header>

        <div class="record-metrics record-metrics--bordered">
            <div><span>{{ __("Montant du frais") }}</span><strong>{{ number_format((float) $ancillaryFee->amount_due, 2, ',', ' ') }} <small>{{ $currency }}</small></strong></div>
            <div class="record-metric--success"><span>{{ __("Déjà encaissé") }}</span><strong>{{ number_format((float) $ancillaryFee->amount_paid, 2, ',', ' ') }} <small>{{ $currency }}</small></strong></div>
            <div class="record-metric--danger"><span>{{ __("Solde à régler") }}</span><strong>{{ number_format((float) $ancillaryFee->balance, 2, ',', ' ') }} <small>{{ $currency }}</small></strong></div>
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ route('payments.ancillary.store', $ancillaryFee) }}" class="form-page__content">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
            <input type="hidden" name="currency" value="{{ $currency }}">
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">01</span><div><h2>{{ __("Informations du paiement") }}</h2><p>{{ __("Ce versement sera affecté uniquement à ce frais connexe.") }}</p></div></div>
                <div class="form-section__body form-grid">
                    <label class="form-field"><span class="form-field__label">Montant ({{ $currency }})<span class="text-danger ms-1 fw-bold">*</span></span><input class="form-control" type="number" name="amount" step="0.01" min="0.01" max="{{ $ancillaryFee->balance }}" value="{{ old('amount', $ancillaryFee->balance) }}" required>@error('amount')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                    <label class="form-field"><span class="form-field__label">{{ __("Date du paiement") }}<span class="text-danger ms-1 fw-bold">*</span></span><input class="form-control" type="datetime-local" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d\\TH:i')) }}" required>@error('payment_date')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                    <label class="form-field"><span class="form-field__label">{{ __("Mode de règlement") }}<span class="text-danger ms-1 fw-bold">*</span></span><select name="payment_method" class="form-select" required>@foreach($paymentMethods as $method)<option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>@endforeach</select>@error('payment_method')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                    <label class="form-field"><span class="form-field__label">{{ __("Référence de transaction") }}</span><input class="form-control" name="transaction_reference" value="{{ old('transaction_reference') }}" maxlength="150">@error('transaction_reference')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                    <label class="form-field form-grid__wide"><span class="form-field__label">{{ __("Justificatif de paiement") }}</span><input class="form-control" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png,.webp">@error('proof')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                    <label class="form-field form-grid__wide"><span class="form-field__label">{{ __("Observations") }}</span><textarea class="form-control" name="notes" rows="3" maxlength="3000">{{ old('notes') }}</textarea>@error('notes')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                </div>
            </section>
            <div class="form-actions"><a href="{{ route('subscriptions.show', $ancillaryFee->subscription) }}" class="btn btn-outline-secondary resource-button">{{ __("Annuler") }}</a><button class="btn btn-app-primary resource-button" type="submit">{{ __("Enregistrer le paiement et générer le reçu") }}</button></div>
        </form>
    </div>
</x-layouts.app>
