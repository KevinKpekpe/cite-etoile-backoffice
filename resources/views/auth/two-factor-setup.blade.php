<x-layouts.guest title="{{ __('auth.two_factor_setup_title') }}">
    @if (session('recovery_codes'))
        <p class="auth-form-copy">{{ __('auth.store_recovery_codes') }}</p>
        <ul class="auth-recovery-codes">
            @foreach (session('recovery_codes') as $code)<li>{{ $code }}</li>@endforeach
        </ul>
    @elseif (auth()->user()->hasTwoFactorAuthenticationEnabled())
        <div class="auth-notice auth-notice--success" role="status">{{ __('auth.two_factor_active') }}</div>
        <form method="POST" action="{{ route('two-factor.disable') }}" class="auth-form">
            @csrf
            @method('DELETE')
            <x-auth-input name="password" :label="__('auth.current_password')" type="password" autocomplete="current-password" required />
            <button type="submit" class="btn btn-danger auth-submit">{{ __('auth.disable_two_factor') }}</button>
        </form>
    @else
        <p class="auth-form-copy">{{ __('auth.two_factor_setup_description') }}</p>

        {{-- QR Code --}}
        <div class="auth-qr-wrapper">
            <div id="auth-qr-code"></div>
        </div>

        <p class="auth-form-copy auth-form-copy--small">
            {{ __('auth.or_enter_key_manually') }}
        </p>
        <code class="auth-secret">{{ $secret }}</code>

        <form method="POST" action="{{ route('two-factor.enable') }}" class="auth-form">
            @csrf
            <x-auth-input name="code" :label="__('auth.confirmation_code')" autocomplete="one-time-code" inputmode="numeric" required />
            <button type="submit" class="btn btn-primary auth-submit">{{ __('auth.enable_two_factor') }}</button>
        </form>
    @endif
</x-layouts.guest>

@unless(auth()->user()->hasTwoFactorAuthenticationEnabled() || session('recovery_codes'))
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js" integrity="sha256-k0BNsOJSfEQSdBn3wFtMQ1bA6LL2x5lUV5Ae0FgZWkk=" crossorigin="anonymous"></script>
<script>
    QRCode.toCanvas(
        document.getElementById('auth-qr-code'),
        @json($otpauthUri),
        { width: 200, margin: 2, color: { dark: '#000', light: '#fff' } },
        function (err) { if (err) console.error(err); }
    );
</script>
@endunless
