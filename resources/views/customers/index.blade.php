<x-layouts.app title="Clients">
    @php
        $statusLabels = [
            'prospect' => 'Prospect',
            'active' => 'Actif',
            'settled' => 'Soldé',
            'suspended' => 'Suspendu',
            'archived' => 'Archivé',
        ];
        $hasFilters = filled($filters['search'] ?? null) || filled($filters['status'] ?? null);
    @endphp

    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Gestion commerciale</p>
                <h1 class="resource-heading__title">Portefeuille clients</h1>
                <p class="resource-heading__description">Recherchez les dossiers souscripteurs et suivez leur situation de paiement.</p>
            </div>
            <div class="resource-heading__actions">
                @can('customers.delete')
                    <a href="{{ route('customers.trashed') }}" class="btn btn-outline-secondary resource-button">Corbeille</a>
                @endcan
                @can('customers.create')
                    <a href="{{ route('customers.create') }}" class="btn btn-app-primary resource-button">Nouveau client</a>
                @endcan
            </div>
        </header>

        <form method="GET" action="{{ route('customers.index') }}" class="resource-filters">
            <div class="resource-filters__search">
                <label for="customer-search" class="form-label">Rechercher</label>
                <input id="customer-search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Nom, téléphone ou numéro client">
            </div>
            <div>
                <label for="customer-status" class="form-label">Situation</label>
                <select id="customer-status" name="status" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>En retard de paiement</option>
                    @foreach($statusLabels as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected(($filters['status'] ?? '') === $statusKey)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="resource-filters__actions">
                @if($hasFilters)
                    <a href="{{ route('customers.index') }}" class="btn btn-link resource-filter-reset">Réinitialiser</a>
                @endif
                <button class="btn btn-app-primary resource-button" type="submit">Appliquer</button>
            </div>
        </form>

        <section class="resource-table" aria-labelledby="customer-table-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="customer-table-title">Dossiers clients</h2>
                    <p>{{ $customers->total() }} {{ Str::plural('dossier', $customers->total()) }}</p>
                </div>
                @if($hasFilters)
                    <span class="resource-filter-indicator">Filtres actifs</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Numéro</th>
                            <th scope="col">Client</th>
                            <th scope="col">Téléphone</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Situation</th>
                            <th scope="col">Responsable</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            @php
                                $overdueCount = $customer->subscriptions->pluck('installments')->flatten()->where('status', 'overdue')->count();
                                $initials = mb_strtoupper(mb_substr($customer->first_name, 0, 1).mb_substr($customer->last_name, 0, 1));
                            @endphp
                            <tr class="{{ $overdueCount > 0 ? 'resource-data-table__row--attention' : '' }}">
                                <td>
                                    <a class="resource-reference" href="{{ route('customers.show', $customer) }}">{{ $customer->customer_number }}</a>
                                </td>
                                <td>
                                    <div class="resource-identity">
                                        <span class="resource-identity__avatar" aria-hidden="true">{{ $initials }}</span>
                                        <span><strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong><small>{{ $customer->email ?: 'Aucun e-mail renseigné' }}</small></span>
                                    </div>
                                </td>
                                <td class="resource-data-table__secondary">{{ $customer->phone }}</td>
                                <td><span class="status-badge status-badge--{{ $customer->status }}">{{ $statusLabels[$customer->status] ?? ucfirst($customer->status) }}</span></td>
                                <td>
                                    @if($overdueCount > 0)
                                        <span class="status-badge status-badge--danger">{{ $overdueCount }} {{ Str::plural('impayé', $overdueCount) }}</span>
                                    @else
                                        <span class="status-badge status-badge--neutral">À jour</span>
                                    @endif
                                </td>
                                <td class="resource-data-table__secondary">
                                    {{ $customer->assignedAgent ? $customer->assignedAgent->first_name.' '.$customer->assignedAgent->last_name : 'Non attribué' }}
                                </td>
                                <td class="text-end"><a href="{{ route('customers.show', $customer) }}" class="resource-row-action">Voir le dossier</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="resource-empty">
                                        <strong>Aucun client trouvé</strong>
                                        <span>{{ $hasFilters ? 'Modifiez ou réinitialisez les critères de recherche.' : 'Les dossiers clients apparaîtront ici après leur création.' }}</span>
                                        @if($hasFilters)
                                            <a href="{{ route('customers.index') }}">Afficher tous les clients</a>
                                        @endif
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
