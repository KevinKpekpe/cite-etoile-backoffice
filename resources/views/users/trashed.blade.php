<x-layouts.app title="Corbeille — Utilisateurs supprimés">
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold">🗑 Corbeille</h1>
        <p class="text-slate-600">Utilisateurs supprimés (soft delete). Ils peuvent être restaurés ou supprimés définitivement.</p>
    </div>
    <a href="{{ route('users.index') }}" class="rounded-lg border bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
        ← Retour aux utilisateurs
    </a>
</div>

@if(session('status'))
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
    </div>
@endif

<div class="overflow-hidden rounded-xl bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-red-50 text-red-800">
                <tr>
                    <th class="p-4">Nom</th>
                    <th class="p-4">E-mail</th>
                    <th class="p-4">Rôle</th>
                    <th class="p-4">Supprimé le</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($users as $user)
                <tr class="hover:bg-red-50/50">
                    <td class="p-4 font-medium">
                        <a href="{{ route('users.show', $user) }}" class="text-slate-700 hover:underline">
                            {{ $user->first_name }} {{ $user->last_name }}
                        </a>
                    </td>
                    <td class="p-4 text-slate-600">{{ $user->email }}</td>
                    <td class="p-4">{{ $user->roles->where('name', '!=', 'customer')->first()?->name ?? '—' }}</td>
                    <td class="p-4 text-red-700">{{ $user->deleted_at->format('d/m/Y H:i') }}</td>
                    <td class="p-4">
                        <div class="flex gap-2">
                            {{-- Restaurer --}}
                            @if(! $user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
                            <form method="POST" action="{{ route('users.restore', $user) }}">
                                @csrf
                                <button class="rounded-lg bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-200 transition">
                                    ♻ Restaurer
                                </button>
                            </form>
                            @endif

                            {{-- Supprimer définitivement (super_admin uniquement) --}}
                            @can('users.force_delete')
                            <form method="POST" action="{{ route('users.force-delete', $user) }}"
                                  onsubmit="return confirm('SUPPRESSION DÉFINITIVE de {{ $user->first_name }} {{ $user->last_name }}. Irréversible !')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 hover:bg-red-200 transition">
                                    ☠ Définitif
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-slate-500">
                        ✅ La corbeille est vide.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-5">{{ $users->links() }}</div>
</x-layouts.app>
