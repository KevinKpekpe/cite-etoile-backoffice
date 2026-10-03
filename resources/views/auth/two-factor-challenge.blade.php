<x-layouts.guest title="Vérification en deux étapes">
    <p class="auth-form-copy">{{ __('auth.two_factor_description') }}</p>
    <form method="POST" action="{{ route('two-factor.verify') }}" class="auth-form">
        @csrf
        <x-auth-input name="code" :label="__('auth.security_code')" autocomplete="one-time-code" inputmode="numeric" required autofocus />
        <button type="submit" class="btn btn-primary auth-submit">{{ __('auth.verify') }}</button>
    </form>
</x-layouts.guest>
