<x-layouts.guest title="Sécurité du compte">
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
        <p class="auth-form-copy">{{ __('auth.add_authenticator_key') }}</p>
        <code class="auth-secret">{{ $secret }}</code>
        <form method="POST" action="{{ route('two-factor.enable') }}" class="auth-form">
            @csrf
            <x-auth-input name="code" :label="__('auth.confirmation_code')" autocomplete="one-time-code" inputmode="numeric" required />
            <button type="submit" class="btn btn-primary auth-submit">{{ __('auth.enable_two_factor') }}</button>
        </form>
    @endif
</x-layouts.guest>
