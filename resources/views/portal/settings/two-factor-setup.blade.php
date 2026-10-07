<x-layouts.app :title="__('portal.two_factor_security')">
    <div class="form-page portal-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">{{ __('portal.customer_area') }}</p>
                <h1 class="resource-heading__title">{{ __('portal.two_factor_security') }}</h1>
                <p class="resource-heading__description">{{ __('auth.portal_two_factor_setup_description') }}</p>
            </div>
        </header>

        <div class="form-page__content">
            @if (session('recovery_codes'))
                {{-- Recovery codes shown once after enabling --}}
                <section class="form-section">
                    <div class="form-section__header">
                        <span class="form-section__number">✓</span>
                        <div>
                            <h2>{{ __('portal.recovery_codes_title') }}</h2>
                            <p>{{ __('portal.recovery_codes_description') }}</p>
                        </div>
                    </div>
                    <div class="form-section__body">
                        <ul class="auth-recovery-codes">
                            @foreach (session('recovery_codes') as $code)
                                <li>{{ $code }}</li>
                            @endforeach
                        </ul>
                        <div class="form-actions mt-3">
                            <a href="{{ route('portal.settings.index') }}" class="btn btn-primary">
                                {{ __('ui.back') }}
                            </a>
                        </div>
                    </div>
                </section>

            @elseif (auth()->user()->hasTwoFactorAuthenticationEnabled())
                {{-- Already enabled: show disable form --}}
                <section class="form-section">
                    <div class="form-section__header">
                        <span class="form-section__number">
                            <i class="bi bi-shield-check text-success"></i>
                        </span>
                        <div>
                            <h2>{{ __('portal.two_factor_active') }}</h2>
                            <p>{{ __('portal.two_factor_security_description') }}</p>
                        </div>
                    </div>
                    <div class="form-section__body">
                        <div class="auth-notice auth-notice--success mb-4" role="status">
                            <i class="bi bi-shield-check me-2"></i>
                            {{ __('portal.two_factor_active') }}
                        </div>
                        <form method="POST" action="{{ route('portal.two-factor.disable') }}" class="auth-form">
                            @csrf
                            @method('DELETE')
                            <x-auth-input name="password" :label="__('auth.current_password')" type="password" autocomplete="current-password" required />
                            <div class="form-actions">
                                <button type="submit" class="btn btn-danger">{{ __('portal.disable_two_factor') }}</button>
                                <a href="{{ route('portal.settings.index') }}" class="btn btn-outline">{{ __('ui.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </section>

            @else
                {{-- Setup: show QR code and form --}}
                <section class="form-section">
                    <div class="form-section__header">
                        <span class="form-section__number">01</span>
                        <div>
                            <h2>{{ __('auth.scan_qr_code') }}</h2>
                            <p>{{ __('portal.two_factor_scan_instructions') }}</p>
                        </div>
                    </div>
                    <div class="form-section__body">
                        <div class="d-flex flex-column align-items-start gap-3">
                            <div id="portal-qr-code" class="border rounded p-2 bg-white"></div>
                            <p class="text-secondary small mb-0">
                                <strong>{{ __('portal.two_factor_manual_key') }}</strong>
                                <code class="ms-2 auth-secret auth-secret--inline">{{ $secret }}</code>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section__header">
                        <span class="form-section__number">02</span>
                        <div>
                            <h2>{{ __('portal.two_factor_confirm_code') }}</h2>
                            <p>{{ __('auth.two_factor_description') }}</p>
                        </div>
                    </div>
                    <div class="form-section__body">
                        <form method="POST" action="{{ route('portal.two-factor.enable') }}" class="auth-form">
                            @csrf
                            <x-auth-input name="code" :label="__('portal.confirmation_code')" autocomplete="one-time-code" inputmode="numeric" required />
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">{{ __('portal.enable_two_factor') }}</button>
                                <a href="{{ route('portal.settings.index') }}" class="btn btn-outline">{{ __('ui.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts.app>

@unless(auth()->user()->hasTwoFactorAuthenticationEnabled() || session('recovery_codes'))
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js" integrity="sha256-k0BNsOJSfEQSdBn3wFtMQ1bA6LL2x5lUV5Ae0FgZWkk=" crossorigin="anonymous"></script>
<script>
    QRCode.toCanvas(
        document.getElementById('portal-qr-code'),
        @json($otpauthUri),
        { width: 200, margin: 2, color: { dark: '#000', light: '#fff' } },
        function (err) { if (err) console.error(err); }
    );
</script>
@endunless
