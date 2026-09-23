<x-layouts.app title="Parcelles">
    @php
        $commercialLabels = ['available' => 'Disponible', 'reserved' => 'Réservée', 'subscribed' => 'Souscrite', 'blocked' => 'Bloquée', 'unavailable' => 'Indisponible'];
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    @endphp
    <div class="resource-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Gestion foncière</p><h1 class="resource-heading__title">Parcelles</h1><p class="resource-heading__description">Inventaire centralisé du patrimoine foncier et de sa disponibilité commerciale.</p></div>
            <div class="resource-heading__actions">@can('plots.manage')<a href="{{ route('plots.trashed') }}" class="btn btn-outline-secondary resource-button">Corbeille</a><a href="{{ route('plots.create') }}" class="btn btn-app-primary resource-button">Nouvelle parcelle</a>@endcan</div>
        </header>

        <form method="GET" action="{{ route('plots.index') }}" class="resource-filters resource-filters--wide">
            <div class="resource-filters__search"><label for="plot-search" class="form-label">Rechercher</label><input id="plot-search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Référence ou numéro"></div>
            <div><label for="plot-neighborhood" class="form-label">Quartier</label><select id="plot-neighborhood" name="neighborhood_id" class="form-select"><option value="">Tous les quartiers</option>@foreach($neighborhoods as $item)<option value="{{ $item->id }}" @selected((string) ($filters['neighborhood_id'] ?? '') === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div><label for="plot-avenue" class="form-label">Avenue</label><select id="plot-avenue" name="avenue_id" class="form-select"><option value="">Toutes les avenues</option>@foreach($avenues as $item)<option value="{{ $item->id }}" @selected((string) ($filters['avenue_id'] ?? '') === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div><label for="plot-status" class="form-label">Statut</label><select id="plot-status" name="commercial_status" class="form-select"><option value="">Tous les statuts</option>@foreach($commercialLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['commercial_status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="resource-filters__actions">@if($hasFilters)<a href="{{ route('plots.index') }}" class="btn btn-link resource-filter-reset">Réinitialiser</a>@endif<button class="btn btn-app-primary resource-button" type="submit">Appliquer</button></div>
        </form>

        <section class="resource-table">
            <div class="resource-table__header"><div><h2>Inventaire des parcelles</h2><p>{{ $plots->total() }} {{ Str::plural('parcelle', $plots->total()) }}</p></div>@if($hasFilters)<span class="resource-filter-indicator">Filtres actifs</span>@endif</div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>Référence</th><th>Numéro</th><th>Localisation</th><th>Superficie</th><th class="text-end">Prix de base</th><th>Statut commercial</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @forelse($plots as $plot)
                            <tr>
                                <td><a href="{{ route('plots.show', $plot) }}" class="resource-reference">{{ $plot->reference }}</a></td>
                                <td><strong>{{ $plot->plot_number }}</strong></td>
                                <td><span>{{ $plot->avenue->neighborhood->name }}</span><small class="resource-cell-note">{{ $plot->avenue->name }}</small></td>
                                <td class="record-money">{{ $plot->surface_area ? number_format((float) $plot->surface_area, 2, ',', ' ').' m²' : 'Non renseignée' }}</td>
                                <td class="record-money text-end">{{ $plot->base_price ? number_format((float) $plot->base_price, 2, ',', ' ').' USD' : '—' }}</td>
                                <td><span class="status-badge status-badge--{{ $plot->commercial_status }}">{{ $commercialLabels[$plot->commercial_status] ?? ucfirst($plot->commercial_status) }}</span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        <a href="{{ route('plots.show', $plot) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Voir la parcelle">
                                            Voir
                                        </a>
                                        @can('plots.manage')
                                            <a href="{{ route('plots.edit', $plot) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="Modifier la parcelle">
                                                Modifier
                                            </a>
                                            <form method="POST" action="{{ route('plots.destroy', $plot) }}" class="d-inline" data-confirm="Confirmer la suppression de cet élément ?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Supprimer la parcelle">
                                                    Supprimer
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="resource-empty"><strong>Aucune parcelle trouvée</strong><span>{{ $hasFilters ? 'Modifiez ou réinitialisez les critères de recherche.' : 'Créez la première parcelle du patrimoine foncier.' }}</span>@if($hasFilters)<a href="{{ route('plots.index') }}">Afficher toutes les parcelles</a>@endif</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if($plots->hasPages())<div class="resource-pagination">{{ $plots->links() }}</div>@endif
    </div>
</x-layouts.app>
