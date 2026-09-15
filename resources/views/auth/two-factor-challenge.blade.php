<x-layouts.guest title="Vérification en deux étapes">
    <p class="mb-5 text-sm text-slate-600">Entrez le code à six chiffres de votre application ou un code de récupération.</p>
    <form method="POST" action="{{ route('two-factor.verify') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-input name="code" label="Code de sécurité" autocomplete="one-time-code" inputmode="numeric" required autofocus />
        <button class="rounded-lg bg-slate-950 px-4 py-3 font-semibold text-white">Vérifier</button>
    </form>
</x-layouts.guest>
