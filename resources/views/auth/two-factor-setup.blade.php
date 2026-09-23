<x-layouts.guest title="Sécurité du compte">
    @if (session('recovery_codes'))
        <p class="small font-bold mb-2">Conservez ces codes de récupération dans un lieu sûr :</p>
        <ul class="mb-4 grid grid-cols-2 gap-2 rounded-3 bg-light p-3 font-monospace small">
            @foreach (session('recovery_codes') as $code)<li>{{ $code }}</li>@endforeach
        </ul>
    @elseif (auth()->user()->hasTwoFactorAuthenticationEnabled())
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 p-3 text-sm">
            La double authentification est active.
        </div>
        <form method="POST" action="{{ route('two-factor.disable') }}" class="d-flex flex-column gap-3">
            @csrf
            @method('DELETE')
            <x-auth-input name="password" label="Mot de passe actuel" type="password" autocomplete="current-password" required />
            <button type="submit" class="btn btn-outline-danger w-100 py-2.5 font-bold">Désactiver la 2FA</button>
        </form>
    @else
        <p class="text-secondary small mb-2 text-center">Ajoutez cette clé dans votre application d’authentification :</p>
        <p class="mb-4 break-all rounded-3 bg-light p-3 text-center font-monospace font-bold text-dark fs-6">{{ $secret }}</p>
        <form method="POST" action="{{ route('two-factor.enable') }}" class="d-flex flex-column gap-3">
            @csrf
            <x-auth-input name="code" label="Code de confirmation" autocomplete="one-time-code" inputmode="numeric" required />
            <button type="submit" class="btn btn-app-primary resource-button w-100 py-2.5 mt-2 font-bold">Activer la 2FA</button>
        </form>
    @endif
</x-layouts.guest>
