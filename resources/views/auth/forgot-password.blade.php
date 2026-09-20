<x-layouts.guest title="Mot de passe oublié">
    {{-- Message unique : succès, erreur, ou instruction --}}
    @if(session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-800">
            ✅ {{ session('status') }}
        </div>
    @elseif($errors->has('email'))
        <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            ⚠️ {{ $errors->first('email') }}
        </div>
    @else
        <p class="mb-5 text-sm text-slate-600">Saisissez votre adresse e-mail pour recevoir un lien sécurisé.</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf
        {{-- On passe "without-error" pour éviter que x-auth-input affiche aussi l'erreur --}}
        <div class="flex flex-col gap-2">
            <label for="email-reset" class="text-sm font-medium text-slate-700">Adresse e-mail</label>
            <input
                id="email-reset"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                autofocus
                class="rounded-lg border border-slate-300 px-3 py-2.5 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200 @error('email') border-red-400 @enderror"
            >
        </div>
        <button class="rounded-lg bg-slate-950 px-4 py-3 font-semibold text-white hover:bg-slate-800 transition">
            Envoyer le lien de réinitialisation
        </button>
        <a href="{{ route('login') }}" class="text-center text-sm text-amber-700">← Retour à la connexion</a>
    </form>
</x-layouts.guest>
