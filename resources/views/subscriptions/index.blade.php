<x-layouts.app title="Souscriptions">
    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Gestion commerciale</p>
                <h1 class="resource-heading__title">Souscriptions</h1>
                <p class="resource-heading__description">Gestion des contrats de réservation et suivi des paiements.</p>
            </div>
            <div class="resource-heading__actions">
                @can('subscriptions.create')
                    <a href="{{ route('subscriptions.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Nouvelle souscription
                    </a>
                @endcan
            </div>
        </header>

        {{-- Filtres par statut --}}
        <div class="resource-filters">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('subscriptions.index', ['status' => 'all']) }}"
                   class="chart-tab {{ $statusFilter === 'all' ? 'active' : '' }}">
                    Toutes <span class="filter-count">{{ $counts['all'] }}</span>
                </a>
                <a href="{{ route('subscriptions.index', ['status' => 'overdue']) }}"
                   class="chart-tab chart-tab--danger {{ $statusFilter === 'overdue' ? 'active' : '' }}">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> En retard <span class="filter-count">{{ $counts['overdue'] }}</span>
                </a>
                <a href="{{ route('subscriptions.index', ['status' => 'active']) }}"
                   class="chart-tab {{ $statusFilter === 'active' ? 'active' : '' }}">
                    En cours <span class="filter-count">{{ $counts['active'] }}</span>
                </a>
                <a href="{{ route('subscriptions.index', ['status' => 'paid']) }}"
                   class="chart-tab {{ $statusFilter === 'paid' ? 'active' : '' }}">
                    Soldées <span class="filter-count">{{ $counts['paid'] }}</span>
                </a>
                <a href="{{ route('subscriptions.index', ['status' => 'cancelled']) }}"
                   class="chart-tab {{ $statusFilter === 'cancelled' ? 'active' : '' }}">
                    Clôturées <span class="filter-count">{{ $counts['cancelled'] }}</span>
                </a>
            </div>
        </div>

        {{-- Table des souscriptions --}}
        <section class="resource-table" aria-labelledby="subscription-table-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="subscription-table-title">Dossiers souscriptions</h2>
                    <p>{{ $subscriptions->total() }} {{ Str::plural('dossier', $subscriptions->total()) }}</p>
                </div>
                @if($statusFilter !== 'all')
                    <span class="resource-filter-indicator">Filtre actif : {{ ucfirst($statusFilter) }}</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Numéro</th>
                            <th scope="col">Client</th>
                            <th scope="col">Parcelle</th>
                            <th scope="col">Formule</th>
                            <th scope="col">Échéances / Retards</th>
                            <th scope="col">Statut</th>
                            <th scope="col" class="text-end">Solde restant</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $item)
                            @php
                                $overdueInstallments = $item->installments->where('status', 'overdue');
                                $overdueCount = $overdueInstallments->count();
                                $overdueTotal = $overdueInstallments->sum('balance');
                                $isOverdue = $overdueCount > 0;
                                $canPay = $item->financial_status !== 'paid' && !in_array($item->commercial_status, ['completed', 'cancelled', 'terminated'], true);
                            @endphp
                            <tr class="{{ $isOverdue ? 'resource-data-table__row--attention' : '' }}">
                                <td>
                                    <a class="resource-reference" href="{{ route('subscriptions.show', $item) }}">
                                        {{ $item->subscription_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($item->customer)
                                        <a href="{{ route('customers.show', $item->customer) }}" class="text-decoration-none font-bold text-dark">
                                            {{ $item->customer->first_name }} {{ $item->customer->last_name }}
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->plot)
                                        <a class="resource-reference" href="{{ route('plots.show', $item->plot) }}">
                                            {{ $item->plot->reference }}
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>{{ $item->paymentPlan->name }}</td>
                                <td>
                                    @if($isOverdue)
                                        <div>
                                            <span class="status-badge status-badge--danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $overdueCount }} retard(s)</span>
                                            <small class="d-block text-danger font-semibold mt-1">{{ number_format((float) $overdueTotal, 2, ',', ' ') }} USD impayés</small>
                                        </div>
                                    @elseif($item->financial_status === 'paid')
                                        <span class="status-badge status-badge--active"><i class="bi bi-check-circle-fill me-1"></i> Soldée</span>
                                    @else
                                        <span class="status-badge status-badge--neutral">À jour</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->commercial_status === 'active')
                                        <span class="status-badge status-badge--active">En cours</span>
                                    @elseif($item->commercial_status === 'pending')
                                        <span class="status-badge status-badge--pending">En attente</span>
                                    @elseif($item->commercial_status === 'completed')
                                        <span class="status-badge status-badge--completed">Terminée</span>
                                    @else
                                        <span class="status-badge status-badge--neutral">{{ ucfirst($item->commercial_status) }}</span>
                                    @endif
                                </td>
                                <td class="record-money text-end">
                                    {{ number_format((float) $item->balance, 2, ',', ' ') }} USD
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        <a href="{{ route('subscriptions.show', $item) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Voir les détails">
                                            Voir
                                        </a>
                                        @if($canPay)
                                            @can('payments.create')
                                                <a href="{{ route('payments.create', ['subscription' => $item->id]) }}"
                                                   class="btn btn-sm {{ $isOverdue ? 'btn-outline-danger' : 'btn-outline-success' }} py-1 px-2" title="Encaisser un versement">
                                                    Encaisser
                                                </a>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="resource-empty">
                                        <strong>Aucune souscription trouvée</strong>
                                        <span>Modifiez vos filtres ou créez une nouvelle souscription.</span>
                                        @if($statusFilter !== 'all')
                                            <a href="{{ route('subscriptions.index') }}">Afficher toutes les souscriptions</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($subscriptions->hasPages())
            <div class="resource-pagination">{{ $subscriptions->links() }}</div>
        @endif
    </div>
</x-layouts.app>
