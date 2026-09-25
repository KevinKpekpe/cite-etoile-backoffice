<x-layouts.app :title="$paymentPlan->exists ? 'Modifier la formule' : 'Nouvelle formule'">
    <div class="form-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Tarification & financement</p>
                <h1 class="resource-heading__title">{{ $paymentPlan->exists ? 'Modifier la formule' : 'Créer une formule' }}</h1>
                <p class="resource-heading__description">Définissez les conditions commerciales proposées lors d’une nouvelle souscription.</p>
            </div>
            <a href="{{ route('payment-plans.index') }}" class="btn btn-outline">Retour aux formules</a>
        </header>

        <form method="POST" action="{{ $paymentPlan->exists ? route('payment-plans.update', $paymentPlan) : route('payment-plans.store') }}" class="form-page__content">
            @csrf
            @if($paymentPlan->exists)
                @method('PUT')
            @endif

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">01</span>
                    <div>
                        <h2>Identification</h2>
                        <p>Nom interne, code de référence et description de la formule.</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        @if($paymentPlan->exists)
                            <x-auth-input name="code" label="Code" :value="old('code', $paymentPlan->code)" readonly />
                        @endif
                        <x-auth-input name="name" label="Nom de la formule" :value="old('name', $paymentPlan->name)" required />

                        <label class="form-field form-grid__wide">
                            <span class="form-field__label">Description</span>
                            <textarea name="description" rows="4" class="form-control" placeholder="Précisez les conditions ou le public concerné">{{ old('description', $paymentPlan->description) }}</textarea>
                            @error('description')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">02</span>
                    <div>
                        <h2>Conditions financières</h2>
                        <p>Montant contractuel, fréquence et durée du règlement.</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid form-grid--three">
                        <x-auth-input name="total_price" label="Prix total (USD)" type="number" min="0" step="0.01" :value="old('total_price', $paymentPlan->total_price)" required />
                        <x-auth-input name="monthly_amount" label="Mensualité (USD)" type="number" min="0" step="0.01" :value="old('monthly_amount', $paymentPlan->monthly_amount)" />
                        <x-auth-input name="duration_months" label="Durée (mois)" type="number" min="0" :value="old('duration_months', $paymentPlan->duration_months ?? 0)" required />

                        <label class="form-field">
                            <span class="form-field__label">Fréquence<span class="text-danger ms-1 fw-bold">*</span></span>
                            <select name="frequency" class="form-select" required>
                                <option value="once" @selected(old('frequency', $paymentPlan->frequency) === 'once')>Paiement unique</option>
                                <option value="monthly" @selected(old('frequency', $paymentPlan->frequency) === 'monthly')>Paiement mensuel</option>
                            </select>
                            @error('frequency')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">03</span>
                    <div>
                        <h2>Validité et disponibilité</h2>
                        <p>Déterminez la période pendant laquelle la formule peut être proposée.</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <x-auth-input name="valid_from" label="Valide à partir du" type="date" :value="old('valid_from', $paymentPlan->valid_from?->format('Y-m-d'))" />
                        <x-auth-input name="valid_until" label="Valide jusqu’au" type="date" :value="old('valid_until', $paymentPlan->valid_until?->format('Y-m-d'))" />

                        <label class="form-toggle form-grid__wide">
                            <input class="form-check-input" type="checkbox" name="active" value="1" @checked(old('active', $paymentPlan->active ?? true))>
                            <span>
                                <strong>Formule active</strong>
                                <small>La formule sera disponible lors de la création de nouvelles souscriptions.</small>
                            </span>
                        </label>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <a href="{{ route('payment-plans.index') }}" class="btn btn-outline">Annuler</a>
                <button class="btn btn-primary" type="submit">
                    {{ $paymentPlan->exists ? 'Enregistrer les modifications' : 'Créer la formule' }}
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
