<x-layouts.app :title="$user->first_name.' '.$user->last_name">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">{{ $user->first_name }} {{ $user->last_name }}</h1>
            <p class="text-slate-600">{{ $user->email }} · {{ $user->phone }}</p>
            @if($user->trashed())
                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                    🗑 Compte supprimé le {{ $user->deleted_at->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @if(! $user->trashed())
                <a href="{{ route('users.edit', $user) }}" class="rounded-lg border bg-white px-4 py-2">Modifier</a>
                @if(!$user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
                <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                    @csrf @method('PATCH')
                    <button class="rounded-lg px-4 py-2 font-semibold text-white {{ $user->status === 'active' ? 'bg-amber-600' : 'bg-emerald-700' }}">
                        {{ $user->status === 'active' ? 'Suspendre' : 'Réactiver' }}
                    </button>
                </form>
                @endif
                @can('users.delete')
                    @if((! $user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin')) && $user->id !== auth()->id())
                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                          onsubmit="return confirm('Supprimer {{ $user->first_name }} {{ $user->last_name }} ? Le compte sera placé en corbeille.')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-800 transition">
                            🗑 Supprimer
                        </button>
                    </form>
                    @endif
                @endcan
            @else
                {{-- Compte en corbeille --}}
                @can('users.delete')
                    @if(! $user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
                    <form method="POST" action="{{ route('users.restore', $user) }}">
                        @csrf
                        <button class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-800 transition">
                            ♻ Restaurer
                        </button>
                    </form>
                    @endif
                @endcan
                @can('users.force_delete')
                <form method="POST" action="{{ route('users.force-delete', $user) }}"
                      onsubmit="return confirm('SUPPRESSION DÉFINITIVE — irréversible. Continuer ?')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg bg-red-900 px-4 py-2 font-semibold text-white hover:bg-red-950 transition">
                        ☠ Supprimer définitivement
                    </button>
                </form>
                @endcan
            @endif
        </div>
    </div>

    <x-user-credentials-markdown />

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-bold">Profil</h2>
            <dl class="grid gap-3 text-sm">
                <div><dt class="text-slate-500">Statut</dt>
                    <dd><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $user->trashed() ? 'bg-red-100 text-red-800' : ($user->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800') }}">
                        {{ $user->trashed() ? 'Supprimé' : ($user->status === 'active' ? 'Actif' : 'Suspendu') }}
                    </span></dd>
                </div>
                <div><dt class="text-slate-500">Rôle</dt><dd>{{ $user->roles->where('name', '!=', 'customer')->first()?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Téléphone</dt><dd>{{ $user->phone ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">2FA</dt><dd>{{ $user->hasTwoFactorAuthenticationEnabled() ? 'Activé' : 'Non activé' }}</dd></div>
                <div><dt class="text-slate-500">Dernière connexion</dt><dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd></div>
                <div><dt class="text-slate-500">Compte créé le</dt><dd>{{ $user->created_at->format('d/m/Y') }}</dd></div>
                @if($user->trashed())
                <div><dt class="text-slate-500">Supprimé le</dt><dd class="text-red-700 font-semibold">{{ $user->deleted_at->format('d/m/Y H:i') }}</dd></div>
                @endif
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
