<x-layouts.app :title="$user->first_name.' '.$user->last_name">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">{{ $user->first_name }} {{ $user->last_name }}</h1>
            <p class="text-slate-600">{{ $user->email }} · {{ $user->phone }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('users.edit', $user) }}" class="rounded-lg border bg-white px-4 py-2">Modifier</a>
            @if(!$user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
            <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                @csrf @method('PATCH')
                <button class="rounded-lg px-4 py-2 font-semibold text-white {{ $user->status === 'active' ? 'bg-red-700' : 'bg-emerald-700' }}">
                    {{ $user->status === 'active' ? 'Suspendre' : 'Réactiver' }}
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('temporary_password'))
    <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-5">
        <p class="mb-2 font-bold text-amber-900">⚠️ Mot de passe temporaire — à communiquer maintenant à l'utilisateur</p>
        <p class="text-sm text-amber-800">Ce mot de passe n'est affiché qu'une seule fois et ne sera plus accessible par la suite.</p>
        <p class="mt-3 rounded-lg bg-white px-4 py-3 font-mono text-lg font-bold tracking-widest text-slate-950">{{ session('temporary_password') }}</p>
    </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-bold">Profil</h2>
            <dl class="grid gap-3 text-sm">
                <div><dt class="text-slate-500">Statut</dt>
                    <dd><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                        {{ $user->status === 'active' ? 'Actif' : 'Suspendu' }}
                    </span></dd>
                </div>
                <div><dt class="text-slate-500">Rôle</dt><dd>{{ $user->roles->where('name', '!=', 'customer')->first()?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Téléphone</dt><dd>{{ $user->phone ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">2FA</dt><dd>{{ $user->hasTwoFactorAuthenticationEnabled() ? 'Activé' : 'Non activé' }}</dd></div>
                <div><dt class="text-slate-500">Dernière connexion</dt><dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd></div>
                <div><dt class="text-slate-500">Compte créé le</dt><dd>{{ $user->created_at->format('d/m/Y') }}</dd></div>
            </dl>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="mb-4 font-bold">Historique des modifications</h2>
            @forelse($auditEntries as $entry)
            <div class="border-b py-3 text-sm last:border-b-0">
                <p class="font-medium text-slate-800">{{ $entry->action }}</p>
                <p class="text-slate-500">{{ $entry->created_at->format('d/m/Y H:i') }}</p>
            </div>
            @empty
            <p class="text-sm text-slate-500">Aucune modification enregistrée.</p>
            @endforelse
        </section>
    </div>
</x-layouts.app>
