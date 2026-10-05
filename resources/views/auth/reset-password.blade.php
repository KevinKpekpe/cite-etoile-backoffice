<x-layouts.guest title="{{ __('Nouveau mot de passe') }}">
    <form method="POST" action="{{ route('password.update') }}" class="auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-auth-input name="email" :label="__('auth.email_address')" type="email" :value="old('email', $request->email)" autocomplete="email" required />
        <x-auth-input name="password" :label="__('auth.new_password')" type="password" autocomplete="new-password" required />
        <x-auth-input name="password_confirmation" :label="__('auth.confirm_password')" type="password" autocomplete="new-password" required />
        <button type="submit" class="btn btn-primary auth-submit">{{ __('auth.reset_password') }}</button>
    </form>
</x-layouts.guest>
