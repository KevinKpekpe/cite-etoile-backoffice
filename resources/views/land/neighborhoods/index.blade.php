<x-layouts.app title="Quartiers">
    @php($statusLabels = ['planned' => 'Planifié', 'active' => 'Actif', 'commercializable' => 'Commercialisable', 'completed' => 'Achevé', 'suspended' => 'Suspendu'])
    <div class="resource-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Gestion foncière</p><h1 class="resource-heading__title">Quartiers</h1><p class="resource-heading__description">Structurez les zones du projet et suivez leur niveau de développement.</p></div>
            <a href="{{ route('neighborhoods.create') }}" class="btn btn-app-primary resource-button">Nouveau quartier</a>
        </header>

        <section class="resource-table">
            <div class="resource-table__header"><div><h2>Répertoire des quartiers</h2><p>{{ $neighborhoods->total() }} {{ Str::plural('quartier', $neighborhoods->total()) }}</p></div></div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>Code</th><th>Quartier</th><th>Statut</th><th class="text-end">Avenues</th><th class="text-end">Parcelles</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @forelse($neighborhoods as $item)
                            <tr>
                                <td><span class="resource-reference">{{ $item->code }}</span></td>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td><span class="status-badge status-badge--{{ $item->status }}">{{ $statusLabels[$item->status] ?? ucfirst($item->status) }}</span></td>
                                <td class="record-money text-end">{{ number_format($item->avenues_count, 0, ',', ' ') }}</td>
                                <td class="record-money text-end">{{ number_format($item->plots_count, 0, ',', ' ') }}</td>
                                <td class="text-end"><a href="{{ route('neighborhoods.edit', $item) }}" class="resource-row-action">Modifier</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="resource-empty"><strong>Aucun quartier</strong><span>Créez le premier quartier pour structurer le lotissement.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if($neighborhoods->hasPages())<div class="resource-pagination">{{ $neighborhoods->links() }}</div>@endif
    </div>
</x-layouts.app>
