<x-layouts.guest title="Vérification en deux étapes">
    <p class="text-secondary small mb-4 text-center">Entrez le code à six chiffres de votre application d'authentification ou un code de récupération.</p>
    <form method="POST" action="{{ route('two-factor.verify') }}" class="d-flex flex-column gap-3">
        @csrf
        <x-auth-input name="code" label="Code de sécurité" autocomplete="one-time-code" inputmode="numeric" required autofocus />
        <button type="submit" class="btn btn-app-primary resource-button w-100 py-2.5 mt-2 font-bold">Vérifier</button>
    </form>
</x-layouts.guest>
