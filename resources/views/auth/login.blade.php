<x-layouts.guest title="Connexion">
    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email')" autocomplete="username" required autofocus />
        <x-auth-input name="password" label="Mot de passe" type="password" autocomplete="current-password" required />
        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Rester connecté</label>
        <button class="rounded-lg bg-slate-950 px-4 py-3 font-semibold text-white hover:bg-slate-800">Se connecter</button>
        <a href="{{ route('password.request') }}" class="text-center text-sm text-amber-700 hover:underline">Mot de passe oublié ?</a>
    </form>
</x-layouts.guest>
