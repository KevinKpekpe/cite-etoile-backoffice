<x-layouts.guest title="{{ __('Changement de mot de passe') }}">
    <p class="auth-form-copy">{{ __('auth.change_password_description') }}</p>

    @if ($errors->any())
        <div class="auth-notice auth-notice--danger" role="alert">
            <ul>
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.store') }}" class="auth-form">
        @csrf
        <x-auth-input name="password" :label="__('auth.new_password')" type="password" autocomplete="new-password" required autofocus />
        <x-auth-input name="password_confirmation" :label="__('auth.confirm_new_password')" type="password" autocomplete="new-password" required />
        <button type="submit" class="btn btn-primary auth-submit">{{ __('auth.save_password') }}</button>
    </form>
</x-layouts.guest>
