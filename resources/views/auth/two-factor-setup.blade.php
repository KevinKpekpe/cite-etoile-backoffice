<x-layouts.guest title="Sécurité du compte">
    @if (session('recovery_codes'))
        <p class="mb-3 text-sm font-semibold">Conservez ces codes de récupération dans un lieu sûr :</p>
        <ul class="mb-5 grid grid-cols-2 gap-2 rounded-lg bg-slate-100 p-4 font-mono text-xs">
            @foreach (session('recovery_codes') as $code)<li>{{ $code }}</li>@endforeach
        </ul>
    @elseif (auth()->user()->hasTwoFactorAuthenticationEnabled())
        <p class="mb-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">La double authentification est active.</p>
        <form method="POST" action="{{ route('two-factor.disable') }}" class="flex flex-col gap-4">
            @csrf @method('DELETE')
            <x-auth-input name="password" label="Mot de passe actuel" type="password" autocomplete="current-password" required />
            <button class="rounded-lg bg-red-700 px-4 py-3 font-semibold text-white">Désactiver la 2FA</button>
        </form>
    @else
        <p class="mb-3 text-sm text-slate-600">Ajoutez cette clé dans votre application d’authentification :</p>
        <p class="mb-5 break-all rounded-lg bg-slate-100 p-4 text-center font-mono text-sm font-bold">{{ $secret }}</p>
        <form method="POST" action="{{ route('two-factor.enable') }}" class="flex flex-col gap-5">
            @csrf
            <x-auth-input name="code" label="Code de confirmation" autocomplete="one-time-code" inputmode="numeric" required />
            <button class="rounded-lg bg-slate-950 px-4 py-3 font-semibold text-white">Activer la 2FA</button>
        </form>
    @endif
</x-layouts.guest>
