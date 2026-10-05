<x-layouts.app title="{{ __('Avenues') }}">
    <div class="resource-page">
        <header class="resource-heading">
            <div><p class="app-kicker">{{ __("Gestion foncière") }}</p><h1 class="resource-heading__title">{{ __("Avenues") }}</h1><p class="resource-heading__description">{{ __("Organisez les axes de circulation et leur rattachement aux quartiers.") }}</p></div>
            <a href="{{ route('avenues.create') }}" class="btn btn-primary">{{ __("Nouvelle avenue") }}</a>
        </header>
        <section class="resource-table">
            <div class="resource-table__header"><div><h2>{{ __("Répertoire des avenues") }}</h2><p>{{ $avenues->total() }} {{ Str::plural('avenue', $avenues->total()) }}</p></div></div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>{{ __("Code") }}</th><th>{{ __("Avenue") }}</th><th>{{ __("Quartier") }}</th><th>{{ __("Statut") }}</th><th class="text-end">{{ __("Parcelles") }}</th><th class="text-end">{{ __("Action") }}</th></tr></thead>
                    <tbody>
                        @forelse($avenues as $item)
                            <tr>
                                <td><span class="resource-reference">{{ $item->code }}</span></td>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td class="resource-data-table__secondary">{{ $item->neighborhood->name }}</td>
                                <td><span class="status-badge status-badge--{{ $item->status }}">{{ __('statuses.'.$item->status) }}</span></td>
                                <td class="record-money text-end">{{ number_format($item->plots_count, 0, ',', ' ') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        @can('plots.manage')
                                            <a href="{{ route('avenues.edit', $item) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="Modifier l'avenue">
                                                {{ __("Modifier") }}
                                            </a>
                                            <form method="POST" action="{{ route('avenues.destroy', $item) }}" class="d-inline" data-confirm="Confirmer la suppression de cet élément ?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Supprimer l'avenue">
                                                    {{ __("Supprimer") }}
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="resource-empty"><strong>{{ __("Aucune avenue") }}</strong><span>{{ __("Créez une avenue après avoir enregistré un quartier.") }}</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if($avenues->hasPages())<div class="resource-pagination">{{ $avenues->links() }}</div>@endif
    </div>
</x-layouts.app>
