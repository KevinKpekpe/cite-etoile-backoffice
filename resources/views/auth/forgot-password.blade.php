<x-layouts.guest title="Mot de passe oublié">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 p-3 text-sm">
            ✅ {{ session('status') }}
        </div>
    @elseif($errors->has('email'))
        <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 p-3 text-sm">
            ⚠️ {{ $errors->first('email') }}
        </div>
    @else
        <p class="text-secondary small mb-4 text-center">Saisissez votre adresse e-mail pour recevoir un lien de réinitialisation sécurisé.</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="d-flex flex-column gap-3">
        @csrf
        <div class="form-field">
            <label for="email-reset" class="form-field__label">Adresse e-mail <span class="text-danger font-bold">*</span></label>
            <input
                id="email-reset"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                autofocus
                class="form-control @error('email') is-invalid @enderror"
                placeholder="votre.email@exemple.com"
            >
        </div>
        <button type="submit" class="btn btn-app-primary resource-button w-100 py-2.5 mt-2 font-bold">
            Envoyer le lien de réinitialisation
        </button>
        <div class="text-center mt-2">
            <a href="{{ route('login') }}" class="text-sm font-medium text-amber-700 text-decoration-none">← Retour à la connexion</a>
        </div>
    </form>
</x-layouts.guest>
