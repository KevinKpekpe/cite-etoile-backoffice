<x-layouts.app title="Types de frais connexes">
    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Gestion financière</p>
                <h1 class="resource-heading__title">Types de frais connexes</h1>
                <p class="resource-heading__description">Catalogue des catégories utilisées pour les frais générés et leur affichage.</p>
            </div>
            @can('payments.create')
                <a href="{{ route('ancillary-fee-types.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nouveau type</a>
            @endcan
        </header>

        <section class="resource-table">
            <div class="resource-table__header"><div><h2>Catalogue des types</h2><p>{{ $feeTypes->total() }} type(s) enregistré(s)</p></div></div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>{{ __("Type de frais") }}</th><th>Montant par défaut</th><th>Frais existants</th><th class="text-end">{{ __("Actions") }}</th></tr></thead>
                <tbody>
                    @forelse($feeTypes as $feeType)
                        <tr>
                            <td><strong>{{ $feeType->name }}</strong>@if($feeType->is_system)<small class="d-block text-muted">Type métier requis</small>@endif</td>
                            <td>
                                @if($feeType->code === 'development')
                                    @foreach(['cash' => 'Comptant', 'one_year' => '1 an', 'three_years' => '3 ans', 'five_years' => '5 ans', 'ten_years' => '10 ans'] as $option => $label)
                                        @php($pricing = $feeType->pricing_options[$option] ?? [])
                                        <small class="d-block"><strong>{{ $label }} :</strong> {{ number_format((float) ($pricing['total'] ?? 0), 2, ',', ' ') }} USD total · {{ number_format((float) ($pricing['monthly'] ?? 0), 2, ',', ' ') }} USD/mois</small>
                                    @endforeach
                                @else
                                    {{ $feeType->default_amount !== null ? number_format((float) $feeType->default_amount, 2, ',', ' ').' USD' : '—' }}
                                @endif
                            </td>
                            <td>{{ $feeType->fees_count }}</td>
                            <td class="text-end"><div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                @can('payments.create')
                                    <a href="{{ route('ancillary-fee-types.edit', $feeType) }}" class="btn btn-outline btn-sm">{{ __("Modifier") }}</a>
                                    @if(!$feeType->is_system && $feeType->fees_count === 0)
                                        <form method="POST" action="{{ route('ancillary-fee-types.destroy', $feeType) }}" data-confirm="Supprimer ce type de frais ?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">{{ __("Supprimer") }}</button>
                                        </form>
                                    @endif
                                @endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="resource-empty"><strong>{{ __("Aucun type de frais") }}</strong><span>Créez le premier type de frais connexe.</span></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if($feeTypes->hasPages())<div class="resource-pagination">{{ $feeTypes->links() }}</div>@endif
    </div>
</x-layouts.app>
