<x-layouts.app title="Paramètres">
    @php
        $value = fn($key, $default = '') => old($key, $settings->get($key)?->value ?? $default);
        $selectedMethods = old('finance.payment_methods', json_decode($settings->get('finance.payment_methods')?->value ?? '[]', true)) ?? [];
    @endphp

    <div class="form-page">
        <header class="resource-heading"><div><p class="app-kicker">Configuration système</p><h1 class="resource-heading__title">Paramètres généraux</h1><p class="resource-heading__description">Entreprise, finances, numérotation et règles appliquées aux opérations.</p></div></header>
        <form method="POST" action="{{ route('settings.update') }}" class="form-page__content">@csrf @method('PUT')
            <section class="form-section"><div class="form-section__header"><span class="form-section__number">01</span><div><h2>Entreprise et projet</h2><p>Identité affichée sur les documents et coordonnées officielles.</p></div></div><div class="form-section__body"><div class="form-grid">
                @foreach(['company.name' => 'Nom de l’entreprise', 'project.name' => 'Nom du projet', 'company.logo' => 'URL du logo', 'company.phone' => 'Téléphone', 'company.email' => 'E-mail', 'company.address' => 'Adresse'] as $key => $label)
                    @php([$group, $field] = explode('.', $key, 2))
                    <label class="form-field"><span class="form-field__label">{{ $label }}</span><input id="setting-{{ str_replace('.', '-', $key) }}" name="{{ $group }}[{{ $field }}]" value="{{ $value($key) }}" class="form-control @error($key) is-invalid @enderror">@error($key)<span class="form-field__error">{{ $message }}</span>@enderror</label>
                @endforeach
            </div></div></section>

            <section class="form-section"><div class="form-section__header"><span class="form-section__number">02</span><div><h2>Configuration financière</h2><p>Devise de référence et moyens de paiement acceptés.</p></div></div><div class="form-section__body"><div class="form-grid">
                <label class="form-field"><span class="form-field__label">Devise principale</span><input name="finance[currency]" value="{{ $value('finance.currency', 'USD') }}" class="form-control text-uppercase @error('finance.currency') is-invalid @enderror" maxlength="3">@error('finance.currency')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                <fieldset class="form-field"><legend class="form-field__label">Modes de paiement autorisés</legend><div class="settings-options">@foreach(['cash' => 'Espèces', 'bank_transfer' => 'Virement bancaire', 'mobile_money' => 'Mobile money', 'card' => 'Carte bancaire', 'other' => 'Autre'] as $method => $label)<label class="form-check"><input type="checkbox" name="finance[payment_methods][]" value="{{ $method }}" @checked(in_array($method, $selectedMethods, true))><span>{{ $label }}</span></label>@endforeach</div>@error('finance.payment_methods')<span class="form-field__error">{{ $message }}</span>@enderror</fieldset>
            </div></div></section>

            <section class="form-section"><div class="form-section__header"><span class="form-section__number">03</span><div><h2>Numérotation automatique</h2><p>Préfixes uniques utilisés lors de la création des références.</p></div></div><div class="form-section__body"><div class="form-grid form-grid--four">
                @foreach(['customer.prefix' => 'Client', 'payment.prefix' => 'Paiement', 'receipt.prefix' => 'Reçu', 'contract.prefix' => 'Contrat'] as $key => $label)
                    @php([$group, $field] = explode('.', $key, 2))
                    <label class="form-field"><span class="form-field__label">Préfixe {{ $label }}</span><input name="{{ $group }}[{{ $field }}]" value="{{ $value($key) }}" class="form-control text-uppercase @error($key) is-invalid @enderror" maxlength="10" placeholder="Ex. CLT">@error($key)<span class="form-field__error">{{ $message }}</span>@enderror</label>
                @endforeach
            </div></div></section>

            <section class="form-section"><div class="form-section__header"><span class="form-section__number">04</span><div><h2>Règles de paiement</h2><p>Comportements autorisés lors de l’enregistrement des encaissements.</p></div></div><div class="form-section__body settings-rules">
                @foreach(['allow_partial_payment' => ['Paiements partiels', 'Autoriser un versement inférieur au solde de l’échéance.'], 'allow_advance_payment' => ['Paiements anticipés', 'Autoriser les avances sur les échéances futures.']] as $key => [$label, $description])
                    <input type="hidden" name="subscription[{{ $key }}]" value="0"><label class="form-toggle"><input class="form-check-input" type="checkbox" name="subscription[{{ $key }}]" value="1" @checked(filter_var($value('subscription.'.$key, 'true'), FILTER_VALIDATE_BOOL))><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span></label>
                @endforeach
            </div></section>
            <div class="form-actions"><button type="submit" class="btn btn-primary">Enregistrer les paramètres</button></div>
        </form>
    </div>
</x-layouts.app>
