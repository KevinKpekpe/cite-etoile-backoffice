<x-layouts.app title="Tableau de bord">
    @php
        $periodLabels = [
            'day' => 'Aujourd’hui',
            'week' => '7 derniers jours',
            'month' => 'Ce mois',
            'year' => 'Cette année',
        ];
        $maxPayment = max(1, (float) $payment_chart->max('total'));
        $maxPlanTotal = max(1, (int) $plan_distribution->max('total'));
        $totalPlots = array_sum($plots);
        $availablePlots = $plots['available'] ?? 0;
        $contractual = (float) $finances['contractual'];
        $collected = (float) $finances['collected'];
        $collectionRate = $contractual > 0 ? min(100, ($collected / $contractual) * 100) : 0;
    @endphp

    <div class="dashboard-page">
        <div class="dashboard-heading">
            <div>
                <p class="app-kicker">Vue d’ensemble</p>
                <h1 class="dashboard-heading__title">Pilotage de l’activité</h1>
                <p class="dashboard-heading__description">Situation commerciale, foncière et financière consolidée.</p>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="dashboard-period">
                <label for="dashboard-period" class="dashboard-period__label">Période analysée</label>
                <select id="dashboard-period" name="period" class="form-select dashboard-period__select" data-auto-submit>
                    @foreach($periodLabels as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <section class="row g-3 g-xl-4" aria-label="Indicateurs principaux">
            <div class="col-sm-6 col-xl-3">
                <article class="dashboard-stat dashboard-stat--primary">
                    <div class="dashboard-stat__header"><span>Portefeuille clients</span><span class="dashboard-stat__index">01</span></div>
                    <p class="dashboard-stat__value">{{ number_format($clients['total'], 0, ',', ' ') }}</p>
                    <p class="dashboard-stat__caption"><strong>{{ number_format($clients['active'], 0, ',', ' ') }}</strong> clients actifs</p>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="dashboard-stat dashboard-stat--land">
                    <div class="dashboard-stat__header"><span>Parcelles</span><span class="dashboard-stat__index">02</span></div>
                    <p class="dashboard-stat__value">{{ number_format($totalPlots, 0, ',', ' ') }}</p>
                    <p class="dashboard-stat__caption"><strong>{{ number_format($availablePlots, 0, ',', ' ') }}</strong> disponibles à la vente</p>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="dashboard-stat dashboard-stat--finance">
                    <div class="dashboard-stat__header"><span>Valeur contractuelle</span><span class="dashboard-stat__index">03</span></div>
                    <p class="dashboard-stat__value dashboard-stat__value--money">{{ number_format($contractual, 2, ',', ' ') }} <small>USD</small></p>
                    <p class="dashboard-stat__caption">Portefeuille souscrit</p>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="dashboard-stat dashboard-stat--collected">
                    <div class="dashboard-stat__header"><span>Total encaissé</span><span class="dashboard-stat__index">04</span></div>
                    <p class="dashboard-stat__value dashboard-stat__value--money">{{ number_format($collected, 2, ',', ' ') }} <small>USD</small></p>
                    <div class="dashboard-stat__progress" role="progressbar" aria-label="Taux d’encaissement" aria-valuenow="{{ round($collectionRate) }}" aria-valuemin="0" aria-valuemax="100">
                        <span style="width: {{ $collectionRate }}%"></span>
                    </div>
                    <p class="dashboard-stat__caption">{{ number_format($finances['remaining'], 2, ',', ' ') }} USD restant</p>
                </article>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-xl-8">
                <section class="dashboard-panel dashboard-panel--chart">
                    <div class="dashboard-panel__header">
                        <div><p class="dashboard-panel__eyebrow">Flux financiers</p><h2>Encaissements</h2></div>
                        <div class="dashboard-panel__metric"><strong>{{ number_format($finances['month'], 2, ',', ' ') }} USD</strong><span>encaissé ce mois</span></div>
                    </div>

                    <div class="payment-chart" aria-label="Graphique des encaissements pour la période sélectionnée">
                        @forelse($payment_chart as $point)
                            <div class="payment-chart__column">
                                <span class="payment-chart__amount">{{ number_format((float) $point->total, 0, ',', ' ') }}</span>
                                <div class="payment-chart__track">
                                    <span class="payment-chart__bar" style="height: {{ max(3, ((float) $point->total / $maxPayment) * 100) }}%"></span>
                                </div>
                                <span class="payment-chart__label">{{ $point->label }}</span>
                            </div>
                        @empty
                            <div class="dashboard-empty"><strong>Aucun encaissement</strong><span>Aucun paiement validé sur cette période.</span></div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-xl-4">
                <section class="dashboard-panel h-100">
                    <div class="dashboard-panel__header">
                        <div><p class="dashboard-panel__eyebrow">Répartition</p><h2>Formules choisies</h2></div>
                    </div>
                    <div class="plan-list">
                        @forelse($plan_distribution as $item)
                            <div class="plan-list__item">
                                <div class="plan-list__header"><span>{{ $item->name }}</span><strong>{{ number_format($item->total, 0, ',', ' ') }}</strong></div>
                                <div class="plan-list__track"><span style="width: {{ ((int) $item->total / $maxPlanTotal) * 100 }}%"></span></div>
                            </div>
                        @empty
                            <div class="dashboard-empty"><strong>Aucune souscription</strong><span>La répartition apparaîtra dès la première souscription.</span></div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        <section class="dashboard-summary" aria-label="Indicateurs opérationnels">
            <div class="dashboard-summary__item"><span>Nouveaux clients</span><strong>{{ number_format($clients['new_month'], 0, ',', ' ') }}</strong><small>ce mois</small></div>
            <div class="dashboard-summary__item"><span>Clients soldés</span><strong>{{ number_format($clients['settled'], 0, ',', ' ') }}</strong><small>dossiers finalisés</small></div>
            <div class="dashboard-summary__item"><span>Échéances partielles</span><strong>{{ number_format($installments['partial'], 0, ',', ' ') }}</strong><small>à compléter</small></div>
            <div class="dashboard-summary__item dashboard-summary__item--danger"><span>Échéances en retard</span><strong>{{ number_format($installments['overdue'], 0, ',', ' ') }}</strong><small>à régulariser</small></div>
        </section>

        @can('reports.view')
            <div class="row g-4">
                <div class="col-lg-6">
                    <section class="dashboard-panel h-100">
                        <div class="dashboard-panel__header dashboard-panel__header--bordered">
                            <div><p class="dashboard-panel__eyebrow dashboard-panel__eyebrow--danger">Attention requise</p><h2>Échéances en retard</h2></div>
                            <span class="dashboard-count dashboard-count--danger">{{ $overdue_installments->count() }}</span>
                        </div>
                        <div class="dashboard-list">
                            @forelse($overdue_installments as $item)
                                <a href="{{ route('subscriptions.installments.index', $item->subscription) }}" class="dashboard-list__item">
                                    <span><strong>{{ $item->subscription->customer->first_name }} {{ $item->subscription->customer->last_name }}</strong><small>{{ $item->subscription->subscription_number }}</small></span>
                                    <span class="dashboard-list__amount dashboard-list__amount--danger">{{ number_format((float) $item->balance, 2, ',', ' ') }} USD</span>
                                </a>
                            @empty
                                <div class="dashboard-empty"><strong>Aucun retard</strong><span>Toutes les échéances suivies sont à jour.</span></div>
                            @endforelse
                        </div>
                    </section>
                </div>
                <div class="col-lg-6">
                    <section class="dashboard-panel h-100">
                        <div class="dashboard-panel__header dashboard-panel__header--bordered">
                            <div><p class="dashboard-panel__eyebrow">Prochains jours</p><h2>Échéances à venir</h2></div>
                            <span class="dashboard-count">{{ $due_soon->count() }}</span>
                        </div>
                        <div class="dashboard-list">
                            @forelse($due_soon as $item)
                                <a href="{{ route('subscriptions.installments.index', $item->subscription) }}" class="dashboard-list__item">
                                    <span><strong>{{ $item->subscription->customer->first_name }} {{ $item->subscription->customer->last_name }}</strong><small>Prévue le {{ $item->due_date->format('d/m/Y') }}</small></span>
                                    <span class="dashboard-list__amount">{{ number_format((float) $item->balance, 2, ',', ' ') }} USD</span>
                                </a>
                            @empty
                                <div class="dashboard-empty"><strong>Aucune échéance proche</strong><span>Aucun règlement attendu dans les sept prochains jours.</span></div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        @endcan
    </div>
</x-layouts.app>
