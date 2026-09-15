<x-layouts.guest title="Nouveau mot de passe">
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email', $request->email)" autocomplete="email" required />
        <x-auth-input name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" required />
        <x-auth-input name="password_confirmation" label="Confirmer le mot de passe" type="password" autocomplete="new-password" required />
        <button class="rounded-lg bg-slate-950 px-4 py-3 font-semibold text-white">Réinitialiser</button>
    </form>
</x-layouts.guest>
