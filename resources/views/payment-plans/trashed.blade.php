<x-layouts.app title="Corbeille formules">
    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Tarification</p>
                <h1 class="resource-heading__title">Corbeille formules</h1>
                <p class="resource-heading__description">Formules de paiement supprimées. Elles peuvent être restaurées ou retirées définitivement.</p>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('payment-plans.index') }}" class="btn btn-outline">Retour aux formules</a>
            </div>
        </header>

        <section class="resource-table">
            <div class="resource-table__header">
                <div>
                    <h2>Formules supprimées</h2>
                    <p>{{ $paymentPlans->count() }} {{ Str::plural('formule', $paymentPlans->count()) }}</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Code / Nom</th>
                            <th scope="col" class="text-end">Prix total</th>
                            <th scope="col">Mensualité</th>
                            <th scope="col">Durée</th>
                            <th scope="col">Supprimée le</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentPlans as $plan)
                            <tr>
                                <td>
                                    <strong>{{ $plan->name }}</strong>
                                    <small class="resource-cell-note font-monospace">{{ $plan->code }}</small>
                                </td>
                                <td class="record-money text-end">{{ number_format((float) $plan->total_price, 2, ',', ' ') }} USD</td>
                                <td class="resource-data-table__secondary">{{ number_format((float) $plan->monthly_amount, 2, ',', ' ') }} USD / mois</td>
                                <td class="resource-data-table__secondary">{{ $plan->duration_months }} mois</td>
                                <td><span class="status-badge status-badge--danger">{{ $plan->deleted_at->format('d/m/Y H:i') }}</span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                        @can('payment_plans.restore')
                                            <form method="POST" action="{{ route('payment-plans.restore', $plan) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2">
                                                    Restaurer
                                                </button>
                                            </form>
                                        @endcan
                                        @can('payment_plans.force_delete')
                                            <form method="POST" action="{{ route('payment-plans.force-delete', $plan) }}" class="d-inline" data-confirm="Suppression définitive et irréversible de cette formule ?">
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
                                <td colspan="6">
                                    <div class="resource-empty">
                                        <strong>La corbeille est vide</strong>
                                        <span>Aucune formule tarifaire supprimée.</span>
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
