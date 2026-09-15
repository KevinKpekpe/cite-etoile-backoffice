<x-layouts.guest title="Mot de passe oublié">
    <p class="mb-5 text-sm text-slate-600">Saisissez votre adresse e-mail pour recevoir un lien sécurisé.</p>
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email')" autocomplete="email" required autofocus />
        <button class="rounded-lg bg-slate-950 px-4 py-3 font-semibold text-white">Envoyer le lien</button>
        <a href="{{ route('login') }}" class="text-center text-sm text-amber-700">Retour à la connexion</a>
    </form>
</x-layouts.guest>
