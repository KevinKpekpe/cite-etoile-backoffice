<x-layouts.app title="Nouvelle souscription">
    <div class="form-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Gestion commerciale</p>
                <h1 class="resource-heading__title">Nouvelle souscription</h1>
                <p class="resource-heading__description">Associez un client, une parcelle et une formule d’acquisition.</p>
            </div>
            <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-secondary resource-button">Retour aux souscriptions</a>
        </header>

        <form method="POST" action="{{ route('subscriptions.store') }}" class="form-page__content">
            @csrf

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">01</span>
                    <div><h2>Dossier client</h2><p>Sélectionnez le souscripteur concerné par l’opération.</p></div>
                </div>
                <div class="form-section__body">
                    @if($preselectedCustomer)
                        <input type="hidden" name="customer_id" value="{{ $preselectedCustomer->id }}">
                        <div class="form-selection">
                            <span class="form-selection__label">Client présélectionné</span>
                            <strong>{{ $preselectedCustomer->customer_number }} · {{ $preselectedCustomer->first_name }} {{ $preselectedCustomer->last_name }}</strong>
                        </div>
                    @else
                        <label class="form-field">
                            <span class="form-field__label">Client<span class="text-danger ms-1 fw-bold">*</span></span>
                            <select name="customer_id" class="form-select" required>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                        {{ $customer->customer_number }} · {{ $customer->first_name }} {{ $customer->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                    @endif
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">02</span>
                    <div><h2>Parcelle et conditions</h2><p>Définissez le bien réservé et les conditions financières contractuelles.</p></div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        @if($preselectedPlot)
                            <input type="hidden" name="plot_id" value="{{ $preselectedPlot->id }}">
                            <div class="form-selection">
                                <span class="form-selection__label">Parcelle présélectionnée</span>
                                <strong>{{ $preselectedPlot->reference }} · {{ $preselectedPlot->avenue->neighborhood->name }}</strong>
                            </div>
                        @else
                            <label class="form-field">
                                <span class="form-field__label">Parcelle disponible<span class="text-danger ms-1 fw-bold">*</span></span>
                                <select name="plot_id" class="form-select" required>
                                    @foreach($plots as $plot)
                                        <option value="{{ $plot->id }}" @selected(old('plot_id') == $plot->id)>
                                            {{ $plot->reference }} · {{ $plot->avenue->neighborhood->name }}{{ $plot->commercial_status === 'reserved' ? ' · Réservée' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('plot_id')<span class="form-field__error">{{ $message }}</span>@enderror
                            </label>
                        @endif

                        <label class="form-field">
                            <span class="form-field__label">Formule d’acquisition<span class="text-danger ms-1 fw-bold">*</span></span>
                            <select name="payment_plan_id" id="payment_plan_id" class="form-select" data-plan-select required>
                                @foreach($paymentPlans as $plan)
                                    <option value="{{ $plan->id }}" data-duration="{{ $plan->duration_months }}" data-price="{{ $plan->total_price }}" data-monthly="{{ $plan->monthly_amount }}" @selected(old('payment_plan_id') == $plan->id)>
                                        {{ $plan->name }} · {{ number_format((float) $plan->total_price, 2, ',', ' ') }} USD
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_plan_id')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>

                        <div class="form-plan-summary form-grid__wide hidden" data-plan-summary><span data-plan-summary-text></span></div>
                        <x-auth-input name="subscription_date" label="Date de souscription" type="date" :value="old('subscription_date', now()->toDateString())" required />
                        <x-auth-input name="start_date" label="Début de l’échéancier" type="date" :value="old('start_date', now()->toDateString())" required />
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">03</span>
                    <div><h2>Premier versement</h2><p>Facultatif. La souscription sera activée immédiatement si un acompte est enregistré.</p></div>
                </div>
                <div class="form-section__body">
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
                            @error('deposit_method')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-secondary resource-button">Annuler</a>
                <button class="btn btn-app-primary resource-button" type="submit">Créer la souscription</button>
            </div>
        </form>
    </div>
</x-layouts.app>
