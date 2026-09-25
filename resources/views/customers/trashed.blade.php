<x-layouts.app title="Corbeille clients">
    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Archivage</p>
                <h1 class="resource-heading__title">Corbeille clients</h1>
                <p class="resource-heading__description">Restaurez les dossiers supprimés ou retirez-les définitivement selon vos permissions.</p>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary resource-button">Retour aux clients</a>
            </div>
        </header>

        <section class="resource-table">
            <div class="resource-table__header">
                <div>
                    <h2>Dossiers clients supprimés</h2>
                    <p>{{ $customers->total() }} {{ Str::plural('dossier', $customers->total()) }}</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Numéro</th>
                            <th scope="col">Client</th>
                            <th scope="col">Coordonnées</th>
                            <th scope="col">Responsable</th>
                            <th scope="col">Suppression</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td><a href="{{ route('customers.show', $customer) }}" class="resource-reference">{{ $customer->customer_number }}</a></td>
                                <td><strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong></td>
                                <td class="resource-data-table__secondary">{{ $customer->phone }}<br><small>{{ $customer->email ?: 'Sans e-mail' }}</small></td>
                                <td class="resource-data-table__secondary">{{ $customer->assignedAgent ? $customer->assignedAgent->first_name.' '.$customer->assignedAgent->last_name : 'Non attribué' }}</td>
                                <td><span class="status-badge status-badge--danger">{{ $customer->deleted_at->format('d/m/Y H:i') }}</span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        @can('customers.restore')
                                            <form method="POST" action="{{ route('customers.restore', $customer) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success py-1 px-2" type="submit">
                                                    Restaurer
                                                </button>
                                            </form>
                                        @endcan
                                        @can('customers.force_delete')
                                            <form method="POST" action="{{ route('customers.force-delete', $customer) }}" class="d-inline" data-confirm="Confirmer la suppression de cet élément ?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger py-1 px-2" type="submit">
                                                    Supprimer
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="resource-empty">
                                        <strong>La corbeille est vide</strong>
                                        <span>Aucun dossier client supprimé.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if($customers->hasPages())
            <div class="resource-pagination">{{ $customers->links() }}</div>
        @endif
    </div>
</x-layouts.app>
