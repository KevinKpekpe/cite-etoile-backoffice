<x-layouts.app title="Formules">
    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Tarification & Financement</p>
                <h1 class="resource-heading__title">Formules d’acquisition</h1>
                <p class="resource-heading__description">Catalogue des formules de paiement. Les contrats existants conservent leurs conditions initiales.</p>
            </div>
            <div class="resource-heading__actions">
                @can('payment_plans.manage')
                    <a href="{{ route('payment-plans.trashed') }}" class="btn btn-outline-secondary resource-button">Corbeille</a>
                    <a href="{{ route('payment-plans.create') }}" class="btn btn-app-primary resource-button">Nouvelle formule</a>
                @endcan
            </div>
        </header>

        <section class="resource-table" aria-labelledby="payment-plans-table-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="payment-plans-table-title">Répertoire des formules</h2>
                    <p>{{ $paymentPlans->count() }} {{ Str::plural('formule', $paymentPlans->count()) }}</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Formule</th>
                            <th scope="col" class="text-end">Prix total</th>
                            <th scope="col">Modalités de paiement</th>
                            <th scope="col">Période de validité</th>
                            <th scope="col">Statut</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentPlans as $plan)
                            <tr>
                                <td><span class="resource-reference">{{ $plan->code }}</span></td>
                                <td><strong>{{ $plan->name }}</strong></td>
                                <td class="record-money text-end" data-total-price="{{ $plan->total_price }}">{{ number_format((float) $plan->total_price, 2, ',', ' ') }} USD</td>
                                <td class="resource-data-table__secondary">
                                    @if($plan->frequency === 'monthly')
                                        {{ number_format((float) $plan->monthly_amount, 2, ',', ' ') }} USD × {{ $plan->duration_months }} mois
                                    @else
                                        Paiement au comptant (1 fois)
                                    @endif
                                </td>
                                <td class="resource-data-table__secondary">
                                    {{ $plan->valid_from?->format('d/m/Y') ?? 'sans début' }} — {{ $plan->valid_until?->format('d/m/Y') ?? 'sans fin' }}
                                </td>
                                <td>
                                    <span class="status-badge status-badge--{{ $plan->active ? 'active' : 'suspended' }}">
                                        {{ $plan->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        @can('payment_plans.manage')
                                            <a href="{{ route('payment-plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="Modifier la formule">
                                                Modifier
                                            </a>
                                            <form method="POST" action="{{ route('payment-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir placer cette formule en corbeille ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Supprimer la formule">
                                                    Supprimer
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="resource-empty">
                                        <strong>Aucune formule enregistrée</strong>
                                        <span>Créez la première formule tarifaire pour permettre les souscriptions.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
