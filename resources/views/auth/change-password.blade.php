<x-layouts.guest title="Changement de mot de passe">
    <div class="mb-4 text-center">
        <h2 class="h5 font-bold text-dark">Première connexion</h2>
        <p class="text-secondary small mt-1">
            Pour des raisons de sécurité, vous devez personnaliser votre mot de passe avant de continuer.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 p-3 text-sm">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.store') }}" class="d-flex flex-column gap-3">
        @csrf
        <x-auth-input name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" required autofocus />
        <x-auth-input name="password_confirmation" label="Confirmer le nouveau mot de passe" type="password" autocomplete="new-password" required />
        <button type="submit" class="btn btn-app-primary resource-button w-100 py-2.5 mt-2 font-bold">
            Enregistrer mon nouveau mot de passe
        </button>
    </form>
</x-layouts.guest>
