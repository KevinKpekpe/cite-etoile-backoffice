<x-layouts.app title="Paramètres">
    @php
        $value = fn($key, $default = '') => old($key, $settings->get($key)?->value ?? $default);
        $selectedMethods = old('finance.payment_methods', json_decode($settings->get('finance.payment_methods')?->value ?? '[]', true)) ?? [];
    @endphp

    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Configuration système</p>
                <h1 class="resource-heading__title">Paramètres généraux</h1>
                <p class="resource-heading__description">Configuration de l’entreprise, des finances, de la numérotation et des règles métier.</p>
            </div>
        </header>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" class="d-flex flex-column gap-4">
            @csrf
            @method('PUT')

            {{-- Section Entreprise & Projet --}}
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h2 class="h5 font-bold mb-3 text-dark border-bottom pb-2">Identité de l’entreprise & projet</h2>
                <div class="row g-3">
                    @foreach(['company.name' => 'Nom de l’entreprise', 'project.name' => 'Nom du projet', 'company.logo' => 'URL du logo', 'company.phone' => 'Téléphone', 'company.email' => 'E-mail', 'company.address' => 'Adresse'] as $key => $label)
                        @php([$group, $field] = explode('.', $key, 2))
                        <div class="col-md-6">
                            <label for="setting-{{ str_replace('.', '-', $key) }}" class="form-label font-medium text-secondary">
                                {{ $label }}
                            </label>
                            <input id="setting-{{ str_replace('.', '-', $key) }}"
                                   name="{{ $group }}[{{ $field }}]"
                                   value="{{ $value($key) }}"
                                   class="form-control @error($key) is-invalid @enderror"
                                   placeholder="{{ $label }}">
                            @error($key)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Section Finance --}}
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h2 class="h5 font-bold mb-3 text-dark border-bottom pb-2">Configuration financière</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="setting-finance-currency" class="form-label font-medium text-secondary">Devise principale</label>
                        <input id="setting-finance-currency"
                               name="finance[currency]"
                               value="{{ $value('finance.currency', 'USD') }}"
                               class="form-control @error('finance.currency') is-invalid @enderror"
                               placeholder="USD, EUR, FCFA...">
                        @error('finance.currency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-medium text-secondary">Modes de paiement autorisés</label>
                        <div class="d-flex flex-wrap gap-3 mt-1">
                            @foreach(['cash' => 'Espèces', 'bank_transfer' => 'Virement bancaire', 'mobile_money' => 'Mobile money', 'card' => 'Carte bancaire', 'other' => 'Autre'] as $method => $label)
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="finance[payment_methods][]"
                                           value="{{ $method }}"
                                           id="method-{{ $method }}"
                                           @checked(in_array($method, $selectedMethods, true))>
                                    <label class="form-check-label text-dark" for="method-{{ $method }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section Préfixes de numérotation --}}
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h2 class="h5 font-bold mb-3 text-dark border-bottom pb-2">Préfixes de numérotation automatique</h2>
                <div class="row g-3">
                    @foreach(['customer.prefix' => 'Préfixe Client', 'payment.prefix' => 'Préfixe Paiement', 'receipt.prefix' => 'Préfixe Reçu', 'contract.prefix' => 'Préfixe Contrat'] as $key => $label)
                        @php([$group, $field] = explode('.', $key, 2))
                        <div class="col-md-3">
                            <label for="setting-{{ str_replace('.', '-', $key) }}" class="form-label font-medium text-secondary">
                                {{ $label }}
                            </label>
                            <input id="setting-{{ str_replace('.', '-', $key) }}"
                                   name="{{ $group }}[{{ $field }}]"
                                   value="{{ $value($key) }}"
                                   class="form-control text-uppercase @error($key) is-invalid @enderror"
                                   placeholder="EX: CLT">
                            @error($key)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Section Règles de paiement --}}
            <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                <h2 class="h5 font-bold mb-3 text-dark border-bottom pb-2">Règles métier & Paiements</h2>
                <div class="d-flex flex-column gap-2">
                    @foreach(['allow_partial_payment' => 'Autoriser les paiements partiels sur les échéances', 'allow_advance_payment' => 'Autoriser les paiements anticipés (avances)'] as $key => $label)
                        <div class="form-check">
                            <input type="hidden" name="subscription[{{ $key }}]" value="0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="subscription[{{ $key }}]"
                                   value="1"
                                   id="rule-{{ $key }}"
                                   @checked(filter_var($value('subscription.'.$key, 'true'), FILTER_VALIDATE_BOOL))>
                            <label class="form-check-label text-dark font-medium" for="rule-{{ $key }}">
                                {{ $label }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn btn-app-primary resource-button">
                    Enregistrer les paramètres
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
