<x-layouts.guest title="Changement obligatoire de mot de passe">
    <div class="mb-4">
        <h2 class="text-xl font-bold text-slate-900">Première connexion</h2>
        <p class="mt-1 text-sm text-slate-600">
            Pour des raisons de sécurité, vous devez personnaliser votre mot de passe avant de continuer.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.store') }}" class="flex flex-col gap-5">
        @csrf

        <x-auth-input name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" required autofocus />
        <x-auth-input name="password_confirmation" label="Confirmer le nouveau mot de passe" type="password" autocomplete="new-password" required />

        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-3 font-semibold text-white hover:bg-amber-700 transition-colors">
            Enregistrer mon nouveau mot de passe
        </button>
    </form>
</x-layouts.guest>
