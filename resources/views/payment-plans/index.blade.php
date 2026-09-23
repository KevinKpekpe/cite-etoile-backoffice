<x-layouts.app title="Formules">
    <div class="resource-page pricing-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Tarification & Financement</p>
                <h1 class="resource-heading__title">Formules d’acquisition</h1>
                <p class="resource-heading__description">Catalogue des conditions de paiement proposées aux souscripteurs.</p>
            </div>
            <div class="resource-heading__actions">
                @can('payment_plans.manage')
                    <a href="{{ route('payment-plans.trashed') }}" class="btn btn-outline">Corbeille</a>
                    <a href="{{ route('payment-plans.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        Nouvelle formule
                    </a>
                @endcan
            </div>
        </header>

        <div class="pricing-grid">
            @forelse($paymentPlans as $plan)
                <article class="pricing-tier {{ $plan->active ? 'popular' : '' }}">
                    <div class="pricing-tier__topline">
                        <div class="name">{{ $plan->name }}</div>
                        <span class="status status-{{ $plan->active ? 'green' : 'red' }}">{{ $plan->active ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <div class="pricing-tier__price">
                        <span class="price">{{ number_format((float) $plan->total_price, 0, ',', ' ') }}</span>
                        <small>USD au total</small>
                    </div>

                    <div class="desc">{{ $plan->description ?: 'Formule de financement pour l’acquisition d’une parcelle.' }}</div>

                    <ul>
                        <li><i class="bi bi-check-lg" aria-hidden="true"></i> Référence {{ $plan->code }}</li>
                        @if($plan->frequency === 'monthly')
                            <li><i class="bi bi-check-lg" aria-hidden="true"></i> {{ number_format((float) $plan->monthly_amount, 2, ',', ' ') }} USD par mois</li>
                            <li><i class="bi bi-check-lg" aria-hidden="true"></i> Durée de {{ $plan->duration_months }} mois</li>
                        @else
                            <li><i class="bi bi-check-lg" aria-hidden="true"></i> Paiement unique au comptant</li>
                        @endif
                        <li><i class="bi bi-check-lg" aria-hidden="true"></i> Du {{ $plan->valid_from?->format('d/m/Y') ?? 'sans date de début' }}</li>
                        <li><i class="bi bi-check-lg" aria-hidden="true"></i> Au {{ $plan->valid_until?->format('d/m/Y') ?? 'sans date de fin' }}</li>
                    </ul>

                    @can('payment_plans.manage')
                        <div class="pricing-tier__actions">
                            <a href="{{ route('payment-plans.edit', $plan) }}" class="btn {{ $plan->active ? 'btn-primary' : 'btn-outline' }}">Modifier</a>
                            <form method="POST" action="{{ route('payment-plans.destroy', $plan) }}" data-confirm="Placer cette formule en corbeille ?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Supprimer la formule">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                    <span class="visually-hidden">Supprimer</span>
                                </button>
                            </form>
                        </div>
                    @endcan
                </article>
            @empty
                <div class="resource-empty resource-empty--standalone pricing-grid__empty">
                    <strong>Aucune formule enregistrée</strong>
                    <span>Créez la première formule tarifaire pour permettre les souscriptions.</span>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
