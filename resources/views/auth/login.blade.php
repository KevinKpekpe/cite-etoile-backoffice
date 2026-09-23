<x-layouts.guest title="Connexion">
    <form method="POST" action="{{ route('login.store') }}" class="d-flex flex-column gap-3">
        @csrf
        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email')" autocomplete="username" required autofocus />
        <x-auth-input name="password" label="Mot de passe" type="password" autocomplete="current-password" required />
        <div class="d-flex justify-content-between align-items-center mt-1">
            <div class="form-check mb-0">
                <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember-me">
                <label class="form-check-label text-sm text-secondary" for="remember-me">Rester connecté</label>
            </div>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-amber-700 text-decoration-none">Mot de passe oublié ?</a>
        </div>
        <button class="btn btn-app-primary resource-button w-100 py-2.5 mt-2 font-bold" type="submit">Se connecter</button>
    </form>
</x-layouts.guest>
