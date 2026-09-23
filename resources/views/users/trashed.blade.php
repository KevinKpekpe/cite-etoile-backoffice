<x-layouts.app title="Corbeille utilisateurs">
    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Administration</p>
                <h1 class="resource-heading__title">Corbeille utilisateurs</h1>
                <p class="resource-heading__description">Restaurez les comptes utilisateurs supprimés ou supprimez-les définitivement.</p>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary resource-button">Retour aux utilisateurs</a>
            </div>
        </header>

        <section class="resource-table">
            <div class="resource-table__header">
                <div>
                    <h2>Comptes supprimés</h2>
                    <p>{{ $users->total() }} {{ Str::plural('utilisateur', $users->total()) }}</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Nom & Prénom</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Rôle</th>
                            <th scope="col">Supprimé le</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <a href="{{ route('users.show', $user) }}" class="resource-reference">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                    </a>
                                </td>
                                <td class="resource-data-table__secondary">{{ $user->email }}</td>
                                <td><span class="status-badge status-badge--neutral">{{ $user->roles->where('name', '!=', 'customer')->first()?->name ?? '—' }}</span></td>
                                <td><span class="status-badge status-badge--danger">{{ $user->deleted_at->format('d/m/Y H:i') }}</span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        @if(! $user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
                                            <form method="POST" action="{{ route('users.restore', $user) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2">
                                                    Restaurer
                                                </button>
                                            </form>
                                        @endif
                                        @can('users.force_delete')
                                            <form method="POST" action="{{ route('users.force-delete', $user) }}" class="d-inline" onsubmit="return confirm('SUPPRESSION DÉFINITIVE de {{ $user->first_name }} {{ $user->last_name }}. Action irréversible !');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2">
                                                    Supprimer
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="resource-empty">
                                        <strong>La corbeille est vide</strong>
                                        <span>Aucun compte utilisateur supprimé.</span>
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
