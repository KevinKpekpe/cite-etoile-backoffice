<x-layouts.guest title="{{ __('Connexion') }}">
    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
        @csrf
        <x-auth-input name="email" :label="__('auth.email_address')" type="email" :value="old('email')" autocomplete="username" required autofocus />
        <x-auth-input name="password" :label="__('auth.user_password')" type="password" autocomplete="current-password" required />

        <div class="auth-actions">
            <label class="form-check">
                <input type="checkbox" name="remember" value="1">
                <span>{{ __('auth.remember_me') }}</span>
            </label>
            <a href="{{ route('password.request') }}">{{ __('auth.forgot_password') }}</a>
        </div>

        <button class="btn btn-primary auth-submit" type="submit">{{ __('auth.sign_in') }}</button>
    </form>
</x-layouts.guest>
