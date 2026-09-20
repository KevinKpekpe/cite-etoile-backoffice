<x-layouts.app title="Utilisateurs">
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div><h1 class="text-3xl font-bold">Utilisateurs</h1><p class="text-slate-600">Agents et administrateurs de la plateforme.</p></div>
    <div class="flex gap-2">
        @can('users.delete')
            <a href="{{ route('users.trashed') }}" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 transition">🗑 Corbeille</a>
        @endcan
        @can('users.manage')<a href="{{ route('users.create') }}" class="rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white">Nouvel utilisateur</a>@endcan
    </div>
</div>
<form class="mb-6 grid gap-3 rounded-xl bg-white p-4 shadow-sm md:grid-cols-[1fr_200px_180px_auto]">
    <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom ou e-mail" class="rounded-lg border px-3 py-2">
    <select name="role" class="rounded-lg border px-3 py-2">
        <option value="">Tous les rôles</option>
        @foreach($roles as $role)
            <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>{{ ucfirst(str_replace('_', ' ', $role->name)) }}</option>
        @endforeach
    </select>
    <select name="status" class="rounded-lg border px-3 py-2">
        <option value="">Tous les statuts</option>
        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Actif</option>
        <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspendu</option>
    </select>
    <button class="rounded-lg bg-slate-950 px-4 py-2 text-white">Rechercher</button>
</form>
<div class="overflow-hidden rounded-xl bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead class="bg-slate-100"><tr>
    <th class="p-4">Nom</th>
    <th class="p-4">E-mail</th>
    <th class="p-4">Rôle</th>
    <th class="p-4">Statut</th>
    <th class="p-4">Dernière connexion</th>
</tr></thead>
<tbody class="divide-y">
@forelse($users as $user)
<tr>
    <td class="p-4 font-medium"><a class="text-amber-700" href="{{ route('users.show', $user) }}">{{ $user->first_name }} {{ $user->last_name }}</a></td>
    <td class="p-4">{{ $user->email }}</td>
    <td class="p-4">{{ $user->roles->where('name', '!=', 'customer')->first()?->name ?? '—' }}</td>
    <td class="p-4">
        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
            {{ $user->status === 'active' ? 'Actif' : 'Suspendu' }}
        </span>
    </td>
    <td class="p-4 text-slate-500">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
</tr>
@empty
<tr><td colspan="5" class="p-8 text-center text-slate-500">Aucun utilisateur trouvé.</td></tr>
@endforelse
</tbody></table></div></div>
<div class="mt-5">{{ $users->links() }}</div>
</x-layouts.app>
