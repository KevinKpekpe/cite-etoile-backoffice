<x-layouts.app title="Utilisateurs">
    @php
        $hasFilters = filled($filters['search'] ?? null) || filled($filters['role'] ?? null) || filled($filters['status'] ?? null);
    @endphp

    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Gestion des accès</p>
                <h1 class="resource-heading__title">Utilisateurs</h1>
                <p class="resource-heading__description">Gestion des comptes utilisateurs, agents et administrateurs de la plateforme.</p>
            </div>
            <div class="resource-heading__actions">
                @can('users.delete')
                    <a href="{{ route('users.trashed') }}" class="btn btn-outline-secondary resource-button">Corbeille</a>
                @endcan
                @can('users.manage')
                    <a href="{{ route('users.create') }}" class="btn btn-app-primary resource-button">Nouvel utilisateur</a>
                @endcan
            </div>
        </header>

        <form method="GET" action="{{ route('users.index') }}" class="resource-filters">
            <div class="resource-filters__search">
                <label for="user-search" class="form-label">Rechercher</label>
                <input id="user-search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Nom ou adresse e-mail">
            </div>
            <div>
                <label for="user-role" class="form-label">Rôle</label>
                <select id="user-role" name="role" class="form-select">
                    <option value="">Tous les rôles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>{{ ucfirst(str_replace('_', ' ', $role->name)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="user-status" class="form-label">Statut</label>
                <select id="user-status" name="status" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Actif</option>
                    <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspendu</option>
                </select>
            </div>
            <div class="resource-filters__actions">
                @if($hasFilters)
                    <a href="{{ route('users.index') }}" class="btn btn-link resource-filter-reset">Réinitialiser</a>
                @endif
                <button class="btn btn-app-primary resource-button" type="submit">Appliquer</button>
            </div>
        </form>

        <section class="resource-table" aria-labelledby="users-table-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="users-table-title">Liste des utilisateurs</h2>
                    <p>{{ $users->total() }} {{ Str::plural('utilisateur', $users->total()) }}</p>
                </div>
                @if($hasFilters)
                    <span class="resource-filter-indicator">Filtres actifs</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Utilisateur</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Rôle</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Dernière connexion</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $initials = mb_strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1));
                                $roleName = $user->roles->where('name', '!=', 'customer')->first()?->name ?? '—';
                            @endphp
                            <tr>
                                <td>
                                    <div class="resource-identity">
                                        <span class="resource-identity__avatar" aria-hidden="true">{{ $initials }}</span>
                                        <span><strong><a href="{{ route('users.show', $user) }}" class="text-dark text-decoration-none">{{ $user->first_name }} {{ $user->last_name }}</a></strong></span>
                                    </div>
                                </td>
                                <td class="resource-data-table__secondary">{{ $user->email }}</td>
                                <td>
                                    <span class="status-badge status-badge--neutral">
                                        {{ ucfirst(str_replace('_', ' ', $roleName)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-badge--{{ $user->status === 'active' ? 'active' : 'suspended' }}">
                                        {{ $user->status === 'active' ? 'Actif' : 'Suspendu' }}
                                    </span>
                                </td>
                                <td class="resource-data-table__secondary">
                                    {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Voir l'utilisateur">
                                            Voir
                                        </a>
                                        @can('users.manage')
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="Modifier l'utilisateur">
                                                Modifier
                                            </a>
                                        @endcan
                                        @can('users.delete')
                                            @if($user->id !== auth()->id())
                                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" data-confirm="Placer cet utilisateur en corbeille ?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Supprimer l'utilisateur">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="resource-empty">
                                        <strong>Aucun utilisateur trouvé</strong>
                                        <span>{{ $hasFilters ? 'Modifiez ou réinitialisez les critères de recherche.' : 'Les comptes utilisateurs apparaîtront ici après leur création.' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($users->hasPages())
            <div class="resource-pagination">{{ $users->links() }}</div>
        @endif
    </div>
</x-layouts.app>
