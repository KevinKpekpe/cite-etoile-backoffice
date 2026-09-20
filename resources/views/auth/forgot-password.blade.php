<x-layouts.guest title="Mot de passe oublié">
    <p class="mb-5 text-sm text-slate-600">Saisissez votre adresse e-mail pour recevoir un lien sécurisé.</p>

    {{-- Message de succès --}}
    @if(session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Erreurs --}}
    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf
        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email')" autocomplete="email" required autofocus />
        <button class="rounded-lg bg-slate-950 px-4 py-3 font-semibold text-white hover:bg-slate-800 transition">
            Envoyer le lien
        </button>
        <a href="{{ route('login') }}" class="text-center text-sm text-amber-700">Retour à la connexion</a>
    </form>
</x-layouts.guest>
