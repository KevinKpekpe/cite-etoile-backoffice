<x-layouts.guest title="Mot de passe oublié">
    <p class="auth-form-copy">Saisissez votre adresse e-mail pour recevoir un lien de réinitialisation sécurisé.</p>

    @if($errors->has('email'))
        <div class="auth-notice auth-notice--warning" role="alert">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <span>{{ $errors->first('email') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf
        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email')" autocomplete="email" required autofocus />
        <button type="submit" class="btn btn-primary auth-submit">Envoyer le lien</button>
        <a href="{{ route('login') }}" class="btn btn-outline auth-submit">Retour à la connexion</a>
    </form>
</x-layouts.guest>
