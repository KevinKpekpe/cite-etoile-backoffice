<x-layouts.guest title="{{ __('Mot de passe oublié') }}">
    <p class="auth-form-copy">{{ __('auth.forgot_password_description') }}</p>

    @if($errors->has('email'))
        <div class="auth-notice auth-notice--warning" role="alert">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <span>{{ $errors->first('email') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf
        <x-auth-input name="email" :label="__('auth.email_address')" type="email" :value="old('email')" autocomplete="email" required autofocus />
        <button type="submit" class="btn btn-primary auth-submit">{{ __('auth.send_link') }}</button>
        <a href="{{ route('login') }}" class="btn btn-outline auth-submit">{{ __('auth.back_to_login') }}</a>
    </form>
</x-layouts.guest>
