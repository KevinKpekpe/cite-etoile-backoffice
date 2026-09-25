<x-layouts.guest title="Changement de mot de passe">
    <p class="auth-form-copy">Pour votre sécurité, personnalisez votre mot de passe avant de continuer.</p>

    @if ($errors->any())
        <div class="auth-notice auth-notice--danger" role="alert">
            <ul>
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.store') }}" class="auth-form">
        @csrf
        <x-auth-input name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" required autofocus />
        <x-auth-input name="password_confirmation" label="Confirmer le nouveau mot de passe" type="password" autocomplete="new-password" required />
        <button type="submit" class="btn btn-primary auth-submit">Enregistrer le mot de passe</button>
    </form>
</x-layouts.guest>
