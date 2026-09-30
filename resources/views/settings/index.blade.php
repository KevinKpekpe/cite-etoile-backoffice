<x-layouts.app title="Paramètres">
    @php
        $value = fn($key, $default = '') => old($key, $settings->get($key)?->value ?? $default);
        $selectedMethods = old('finance.payment_methods', json_decode($settings->get('finance.payment_methods')?->value ?? '[]', true)) ?? [];
    @endphp

    <div class="form-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Configuration système</p>
                <h1 class="resource-heading__title">Paramètres généraux</h1>
                <p class="resource-heading__description">Entreprise, finances, numérotation, langue et portail client.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('settings.update') }}" class="form-page__content">
            @csrf
            @method('PUT')

            {{-- Section 01 : Entreprise & projet --}}
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">01</span>
                    <div>
                        <h2>Entreprise et projet</h2>
                        <p>Identité affichée sur les documents et coordonnées officielles.</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        @foreach(['company.name' => 'Nom de l\'entreprise', 'project.name' => 'Nom du projet', 'company.logo' => 'URL du logo', 'company.phone' => 'Téléphone', 'company.email' => 'E-mail', 'company.address' => 'Adresse'] as $key => $label)
                            @php([$group, $field] = explode('.', $key, 2))
                            <label class="form-field">
                                <span class="form-field__label">{{ $label }}</span>
                                <input id="setting-{{ str_replace('.', '-', $key) }}" name="{{ $group }}[{{ $field }}]" value="{{ $value($key) }}" class="form-control @error($key) is-invalid @enderror">
                                @error($key)<span class="form-field__error">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Section 02 : Finance --}}
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">02</span>
                    <div>
                        <h2>Configuration financière</h2>
                        <p>Devise de référence et moyens de paiement acceptés.</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <label class="form-field">
                            <span class="form-field__label">Devise principale</span>
                            <input name="finance[currency]" value="{{ $value('finance.currency', 'USD') }}" class="form-control text-uppercase @error('finance.currency') is-invalid @enderror" maxlength="3">
                            @error('finance.currency')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                        <fieldset class="form-field">
                            <legend class="form-field__label">Modes de paiement autorisés</legend>
                            <div class="settings-options">
                                @foreach(['cash' => 'Espèces', 'bank_transfer' => 'Virement bancaire', 'mobile_money' => 'Mobile money', 'card' => 'Carte bancaire', 'other' => 'Autre'] as $method => $label)
                                    <label class="form-check">
                                        <input type="checkbox" name="finance[payment_methods][]" value="{{ $method }}" @checked(in_array($method, $selectedMethods, true))>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('finance.payment_methods')<span class="form-field__error">{{ $message }}</span>@enderror
                        </fieldset>
                    </div>
                </div>
            </section>

            {{-- Section 03 : Numérotation --}}
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">03</span>
                    <div>
                        <h2>Numérotation automatique</h2>
                        <p>Préfixes uniques utilisés lors de la création des références.</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid form-grid--four">
                        @foreach(['customer.prefix' => 'Client', 'payment.prefix' => 'Paiement', 'receipt.prefix' => 'Reçu', 'contract.prefix' => 'Contrat'] as $key => $label)
                            @php([$group, $field] = explode('.', $key, 2))
                            <label class="form-field">
                                <span class="form-field__label">Préfixe {{ $label }}</span>
                                <input name="{{ $group }}[{{ $field }}]" value="{{ $value($key) }}" class="form-control text-uppercase @error($key) is-invalid @enderror" maxlength="10" placeholder="Ex. CLT">
                                @error($key)<span class="form-field__error">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Section 04 : Règles de paiement --}}
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">04</span>
                    <div>
                        <h2>Règles de paiement</h2>
                        <p>Comportements autorisés lors de l'enregistrement des encaissements.</p>
                    </div>
                </div>
                <div class="form-section__body settings-rules">
                    @foreach(['allow_partial_payment' => ['Paiements partiels', 'Autoriser un versement inférieur au solde de l\'échéance.'], 'allow_advance_payment' => ['Paiements anticipés', 'Autoriser les avances sur les échéances futures.']] as $key => [$label, $description])
                        <input type="hidden" name="subscription[{{ $key }}]" value="0">
                        <label class="form-toggle">
                            <input class="form-check-input" type="checkbox" name="subscription[{{ $key }}]" value="1" @checked(filter_var($value('subscription.'.$key, 'true'), FILTER_VALIDATE_BOOL))>
                            <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- Section 05 : Langue de la plateforme --}}
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">05</span>
                    <div>
                        <h2>Langue de la plateforme</h2>
                        <p>Langue utilisée pour l'interface du backoffice et des e-mails générés.</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <fieldset class="form-field">
                            <legend class="form-field__label">Langue d'affichage</legend>
                            <div class="settings-options">
                                @foreach(['fr' => 'Français', 'en' => 'English'] as $code => $label)
                                    <label class="form-check">
                                        <input type="radio" name="platform[locale]" value="{{ $code }}" @checked($value('platform.locale', 'fr') === $code)>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('platform.locale')<span class="form-field__error">{{ $message }}</span>@enderror
                        </fieldset>
                    </div>
                </div>
            </section>

            {{-- Section 06 : Portail client --}}
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">06</span>
                    <div>
                        <h2>Portail client</h2>
                        <p>Textes et fonctionnalités visibles par les clients sur leur espace en ligne.</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <label class="form-field">
                            <span class="form-field__label">Titre du portail</span>
                            <input name="portal[site_title]" value="{{ $value('portal.site_title', 'Espace Client') }}" class="form-control @error('portal.site_title') is-invalid @enderror" maxlength="200">
                            @error('portal.site_title')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                        <label class="form-field">
                            <span class="form-field__label">E-mail d'assistance</span>
                            <input type="email" name="portal[support_email]" value="{{ $value('portal.support_email') }}" class="form-control @error('portal.support_email') is-invalid @enderror" maxlength="190" placeholder="support@exemple.cd">
                            @error('portal.support_email')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                        <label class="form-field">
                            <span class="form-field__label">Téléphone d'assistance</span>
                            <input type="tel" name="portal[support_phone]" value="{{ $value('portal.support_phone') }}" class="form-control @error('portal.support_phone') is-invalid @enderror" maxlength="50" placeholder="+243 ...">
                            @error('portal.support_phone')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                        <label class="form-field form-field--full">
                            <span class="form-field__label">Message d'accueil</span>
                            <textarea name="portal[welcome_message]" rows="3" class="form-control @error('portal.welcome_message') is-invalid @enderror" maxlength="1000" placeholder="Message affiché sur le tableau de bord client.">{{ $value('portal.welcome_message') }}</textarea>
                            @error('portal.welcome_message')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <div class="settings-rules" style="margin-top: 1.25rem;">
                        <input type="hidden" name="portal[allow_profile_edit]" value="0">
                        <label class="form-toggle">
                            <input class="form-check-input" type="checkbox" name="portal[allow_profile_edit]" value="1" @checked(filter_var($value('portal.allow_profile_edit', 'true'), FILTER_VALIDATE_BOOL))>
                            <span><strong>Modification du profil</strong><small>Permettre aux clients de modifier leur adresse et numéro de téléphone depuis le portail.</small></span>
                        </label>
                        <input type="hidden" name="portal[show_payment_history]" value="0">
                        <label class="form-toggle">
                            <input class="form-check-input" type="checkbox" name="portal[show_payment_history]" value="1" @checked(filter_var($value('portal.show_payment_history', 'true'), FILTER_VALIDATE_BOOL))>
                            <span><strong>Historique des paiements</strong><small>Afficher la liste complète des paiements et reçus sur le portail client.</small></span>
                        </label>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Enregistrer les paramètres</button>
            </div>
        </form>
    </div>
</x-layouts.app>
