<x-layouts.app :title="__('portal.account_settings')">
    <div class="form-page portal-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">{{ __('portal.customer_area') }}</p>
                <h1 class="resource-heading__title">{{ __('portal.settings_title') }}</h1>
                <p class="resource-heading__description">{{ __('portal.settings_description') }}</p>
            </div>
        </header>

        <form method="POST" action="{{ route('portal.settings.update') }}" class="form-page__content">
            @csrf
            @method('PUT')

            <!-- Section 01: Langue & Affichage -->
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">01</span>
                    <div>
                        <h2>{{ __('portal.language_display') }}</h2>
                        <p>{{ __('portal.customize_portal') }}</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid form-grid--two">
                        <label class="form-field">
                            <span class="form-field__label">{{ __('portal.portal_language') }}</span>
                            <select name="locale" class="form-select @error('locale') is-invalid @enderror">
                                <option value="fr" @selected($currentLocale === 'fr')>🇫🇷 Français</option>
                                <option value="en" @selected($currentLocale === 'en')>🇬🇧 English</option>
                            </select>
                            <span class="form-field__help">{{ __('portal.select_language') }}</span>
                            @error('locale')<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </div>
            </section>

            <!-- Section 02: Notifications & Communication -->
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">02</span>
                    <div>
                        <h2>{{ __('portal.notifications_title') }}</h2>
                        <p>{{ __('portal.choose_notifications') }}</p>
                    </div>
                </div>
                <div class="form-section__body">
                    <div class="d-flex flex-column gap-3">
                        <label class="d-flex align-items-start gap-3 p-3 rounded bg-body-tertiary border cursor-pointer">
                            <input type="checkbox" name="email_reminders" value="1" @checked($notifications['email_reminders'] ?? true) class="form-check-input mt-1">
                            <div>
                                <strong class="d-block mb-1">{{ __('portal.payment_reminders') }}</strong>
                                <span class="text-secondary small">{{ __('portal.payment_reminders_description') }}</span>
                            </div>
                        </label>

                        <label class="d-flex align-items-start gap-3 p-3 rounded bg-body-tertiary border cursor-pointer">
                            <input type="checkbox" name="email_receipts" value="1" @checked($notifications['email_receipts'] ?? true) class="form-check-input mt-1">
                            <div>
                                <strong class="d-block mb-1">{{ __('portal.payment_confirmation_receipts') }}</strong>
                                <span class="text-secondary small">{{ __('portal.payment_confirmation_description') }}</span>
                            </div>
                        </label>

                        <label class="d-flex align-items-start gap-3 p-3 rounded bg-body-tertiary border cursor-pointer">
                            <input type="checkbox" name="email_updates" value="1" @checked($notifications['email_updates'] ?? true) class="form-check-input mt-1">
                            <div>
                                <strong class="d-block mb-1">{{ __('portal.contract_status_updates') }}</strong>
                                <span class="text-secondary small">{{ __('portal.contract_status_description') }}</span>
                            </div>
                        </label>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">{{ __('portal.save_preferences') }}</button>
            </div>
        </form>

        <!-- Section 03: Statut des fonctionnalités du portail -->
        <section class="form-section mt-4">
            <div class="form-section__header">
                <span class="form-section__number">03</span>
                <div>
                    <h2>{{ __('portal.portal_features') }}</h2>
                    <p>{{ __('portal.allowed_features_status') }}</p>
                </div>
            </div>
            <div class="form-section__body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-body-tertiary h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold">{{ __('portal.portal_access') }}</span>
                                <span class="badge {{ $portalEnabled ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $portalEnabled ? __('ui.active') : __('ui.disabled') }}
                                </span>
                            </div>
                            <small class="text-secondary d-block">{{ __('portal.portal_access_active') }}</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-body-tertiary h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold">{{ __('portal.document_download') }}</span>
                                <span class="badge {{ $allowDocumentDownload ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                    {{ $allowDocumentDownload ? __('portal.authorized') : __('portal.restricted') }}
                                </span>
                            </div>
                            <small class="text-secondary d-block">{{ __('portal.document_download_description') }}</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-body-tertiary h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold">{{ __('portal.online_payment') }}</span>
                                <span class="badge {{ $allowOnlinePayment ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $allowOnlinePayment ? __('portal.available') : __('portal.not_enabled') }}
                                </span>
                            </div>
                            <small class="text-secondary d-block">{{ __('portal.online_payment_description') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 04: Sécurité du compte -->
        <section class="form-section profile-security-section mt-4">
            <div class="form-section__header">
                <span class="form-section__number">04</span>
                <div>
                    <h2>{{ __('portal.security_profile') }}</h2>
                    <p>{{ __('portal.security_profile_description') }}</p>
                </div>
            </div>
            <div class="form-section__body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border rounded">
                    <div>
                        <strong class="d-block">{{ __('portal.personal_information_and_password') }}</strong>
                        <span class="text-secondary small">{{ __('portal.profile_security_description') }}</span>
                    </div>
                    <a href="{{ route('portal.profile.edit') }}" class="btn btn-outline">{{ __('portal.open_my_profile') }}</a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
