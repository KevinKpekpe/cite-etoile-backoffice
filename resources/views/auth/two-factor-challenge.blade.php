<x-layouts.guest title="Vérification en deux étapes">
    <p class="auth-form-copy">Entrez le code à six chiffres de votre application d’authentification ou un code de récupération.</p>
    <form method="POST" action="{{ route('two-factor.verify') }}" class="auth-form">
        @csrf
        <x-auth-input name="code" label="Code de sécurité" autocomplete="one-time-code" inputmode="numeric" required autofocus />
        <button type="submit" class="btn btn-primary auth-submit">Vérifier</button>
    </form>
</x-layouts.guest>
