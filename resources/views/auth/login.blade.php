<x-layouts.guest title="Connexion">
    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
        @csrf
        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email')" autocomplete="username" required autofocus />
        <x-auth-input name="password" label="Mot de passe" type="password" autocomplete="current-password" required />

        <div class="auth-actions">
            <label class="form-check">
                <input type="checkbox" name="remember" value="1">
                <span>Rester connecté</span>
            </label>
            <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
        </div>

        <button class="btn btn-primary auth-submit" type="submit">Se connecter</button>
    </form>
</x-layouts.guest>
