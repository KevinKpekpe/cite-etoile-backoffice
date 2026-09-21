<x-layouts.app title="Avenues">
    <div class="resource-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Gestion foncière</p><h1 class="resource-heading__title">Avenues</h1><p class="resource-heading__description">Organisez les axes de circulation et leur rattachement aux quartiers.</p></div>
            <a href="{{ route('avenues.create') }}" class="btn btn-app-primary resource-button">Nouvelle avenue</a>
        </header>
        <section class="resource-table">
            <div class="resource-table__header"><div><h2>Répertoire des avenues</h2><p>{{ $avenues->total() }} {{ Str::plural('avenue', $avenues->total()) }}</p></div></div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>Code</th><th>Avenue</th><th>Quartier</th><th>Statut</th><th class="text-end">Parcelles</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @forelse($avenues as $item)
                            <tr><td><span class="resource-reference">{{ $item->code }}</span></td><td><strong>{{ $item->name }}</strong></td><td class="resource-data-table__secondary">{{ $item->neighborhood->name }}</td><td><span class="status-badge status-badge--{{ $item->status }}">{{ ['planned' => 'Planifiée', 'active' => 'Active', 'suspended' => 'Suspendue'][$item->status] ?? ucfirst($item->status) }}</span></td><td class="record-money text-end">{{ number_format($item->plots_count, 0, ',', ' ') }}</td><td class="text-end"><a href="{{ route('avenues.edit', $item) }}" class="resource-row-action">Modifier</a></td></tr>
                        @empty
                            <tr><td colspan="6"><div class="resource-empty"><strong>Aucune avenue</strong><span>Créez une avenue après avoir enregistré un quartier.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if($avenues->hasPages())<div class="resource-pagination">{{ $avenues->links() }}</div>@endif
    </div>
</x-layouts.app>
