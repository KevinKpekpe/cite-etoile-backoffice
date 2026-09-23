<x-layouts.guest title="Nouveau mot de passe">
    <form method="POST" action="{{ route('password.update') }}" class="d-flex flex-column gap-3">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email', $request->email)" autocomplete="email" required />
        <x-auth-input name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" required />
        <x-auth-input name="password_confirmation" label="Confirmer le mot de passe" type="password" autocomplete="new-password" required />
        <button type="submit" class="btn btn-app-primary resource-button w-100 py-2.5 mt-2 font-bold">Réinitialiser le mot de passe</button>
    </form>
</x-layouts.guest>
