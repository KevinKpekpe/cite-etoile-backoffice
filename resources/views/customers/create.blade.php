<x-layouts.app title="Nouveau client">
    <div class="form-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Nouveau dossier</p>
                <h1 class="resource-heading__title">Créer un client</h1>
                <p class="resource-heading__description">Enregistrez l’identité du client et, si nécessaire, sa première souscription.</p>
            </div>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary resource-button">Retour aux clients</a>
        </header>

        <form method="POST" action="{{ route('customers.store') }}" class="form-page__content">
            @csrf

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">01</span>
                    <div><h2>Informations du client</h2><p>Identité, coordonnées et attribution commerciale.</p></div>
                </div>
                <div class="form-section__body"><x-customer-form :agents="$agents" /></div>
            </section>

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">02</span>
                    <div><h2>Souscription initiale</h2><p>Cette étape est facultative et peut être complétée ultérieurement.</p></div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <label class="form-field">
                            <span class="form-field__label">Parcelle disponible</span>
                            <select name="plot_id" id="plot_id" class="form-select">
                                <option value="">Aucune souscription maintenant</option>
                                @foreach($plots as $plot)
                                    <option value="{{ $plot->id }}" @selected(old('plot_id') == $plot->id)>{{ $plot->reference }} · {{ $plot->avenue->neighborhood->name }}</option>
                                @endforeach
                            </select>
                            @error('plot_id')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>

                        <label class="form-field">
                            <span class="form-field__label">Formule de paiement</span>
                            <select name="payment_plan_id" id="payment_plan_id" class="form-select" data-plan-select>
                                <option value="">Choisir une formule</option>
                                @foreach($paymentPlans as $plan)
                                    <option value="{{ $plan->id }}" data-duration="{{ $plan->duration_months }}" data-price="{{ $plan->total_price }}" data-monthly="{{ $plan->monthly_amount }}" @selected(old('payment_plan_id') == $plan->id)>
                                        {{ $plan->name }} — {{ number_format((float) $plan->total_price, 2, ',', ' ') }} USD
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_plan_id')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>

                        <div class="form-plan-summary form-grid__wide hidden" data-plan-summary><span data-plan-summary-text></span></div>

                        <x-auth-input name="subscription_date" label="Date de souscription" type="date" :value="old('subscription_date', now()->toDateString())" />
                        <x-auth-input name="start_date" label="Début de l’échéancier" type="date" :value="old('start_date', now()->toDateString())" />

                        <div class="form-subsection form-grid__wide">
                            <div class="form-subsection__header"><h3>Acompte initial</h3><p>Facultatif. Un reçu sera généré immédiatement si un montant est renseigné.</p></div>
                            <div class="form-grid">
                                <x-auth-input name="deposit" label="Montant de l’acompte (USD)" type="number" step="0.01" min="1" :value="old('deposit')" />
                                <label class="form-field">
                                    <span class="form-field__label">Mode de paiement</span>
                                    <select name="deposit_method" class="form-select">
                                        <option value="cash" @selected(old('deposit_method', 'cash') === 'cash')>Espèces</option>
                                        <option value="bank_transfer" @selected(old('deposit_method') === 'bank_transfer')>Virement bancaire</option>
                                        <option value="mobile_money" @selected(old('deposit_method') === 'mobile_money')>Mobile Money</option>
                                        <option value="card" @selected(old('deposit_method') === 'card')>Carte</option>
                                        <option value="other" @selected(old('deposit_method') === 'other')>Autre</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary resource-button">Annuler</a>
                <button class="btn btn-app-primary resource-button" type="submit">Créer le client</button>
            </div>
        </form>
    </div>
</x-layouts.app>
