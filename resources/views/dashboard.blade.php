<x-layouts.app title="Tableau de bord">
    @php
        $periodLabels = ['day' => 'Aujourd’hui', 'week' => '7 jours', 'month' => 'Ce mois', 'year' => 'Cette année'];
        $totalPlots = array_sum($plots);
        $activeSubscriptions = $subscriptions['active'] ?? 0;
        $contractual = (float) $finances['contractual'];
        $collected = (float) $finances['collected'];
        $collectionRate = $contractual > 0 ? min(100, ($collected / $contractual) * 100) : 0;
        $chartValues = $payment_chart->pluck('total')->map(fn ($value) => (float) $value)->values();
        $chartMaximum = max(1, (float) $chartValues->max());
        $chartCount = max(1, $chartValues->count());
        $chartPoints = $chartValues->map(function ($value, $index) use ($chartMaximum, $chartCount) {
            $x = $chartCount === 1 ? 500 : ($index / ($chartCount - 1)) * 1000;
            $y = 220 - (($value / $chartMaximum) * 180);

            return round($x, 2).','.round($y, 2);
        })->implode(' ');
        $chartAreaPoints = $chartPoints !== '' ? '0,220 '.$chartPoints.' 1000,220' : '';
        $maxPlanTotal = max(1, (int) $plan_distribution->max('total'));
    @endphp

    <div class="dashboard-page">
        <header class="dashboard-heading">
            <div><p class="app-kicker">Vue d’ensemble</p><h1 class="dashboard-heading__title">Tableau de bord</h1></div>
            <form method="GET" action="{{ route('dashboard') }}" class="dashboard-period">
                <label for="dashboard-period" class="visually-hidden">Période analysée</label>
                <select id="dashboard-period" name="period" class="form-select dashboard-period__select" data-auto-submit>
                    @foreach($periodLabels as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </header>

        <section class="dashboard-kpi-grid" aria-label="Indicateurs principaux">
            <article class="dashboard-kpi dashboard-kpi--teal">
                <span class="dashboard-kpi__icon"><i class="bi bi-people"></i></span>
                <div><span class="dashboard-kpi__label">Clients</span><strong>{{ number_format($clients['total'], 0, ',', ' ') }}</strong><small>{{ number_format($clients['new_month'], 0, ',', ' ') }} nouveau(x) ce mois</small></div>
                <span class="dashboard-kpi__trend">{{ number_format($clients['active'], 0, ',', ' ') }} actifs</span>
            </article>
            <article class="dashboard-kpi dashboard-kpi--blue">
                <span class="dashboard-kpi__icon"><i class="bi bi-file-earmark-check"></i></span>
                <div><span class="dashboard-kpi__label">Souscriptions actives</span><strong>{{ number_format($activeSubscriptions, 0, ',', ' ') }}</strong><small>Dossiers en cours</small></div>
                <span class="dashboard-kpi__spark" aria-hidden="true">@foreach([40, 55, 48, 70, 60, 76, 68, 86] as $height)<i style="height: {{ $height }}%"></i>@endforeach</span>
            </article>
            <article class="dashboard-kpi dashboard-kpi--yellow">
                <span class="dashboard-kpi__icon"><i class="bi bi-map"></i></span>
                <div><span class="dashboard-kpi__label">Parcelles disponibles</span><strong>{{ number_format($plots['available'] ?? 0, 0, ',', ' ') }}</strong><small>sur {{ number_format($totalPlots, 0, ',', ' ') }} parcelles</small></div>
                <span class="dashboard-kpi__spark" aria-hidden="true">@foreach([86, 78, 70, 64, 58, 50, 45, 38] as $height)<i style="height: {{ $height }}%"></i>@endforeach</span>
            </article>
            <article class="dashboard-kpi dashboard-kpi--green">
                <span class="dashboard-kpi__icon"><i class="bi bi-cash-stack"></i></span>
                <div><span class="dashboard-kpi__label">Valeur contractuelle</span><strong>{{ number_format($contractual, 0, ',', ' ') }} <em>USD</em></strong><small>Portefeuille souscrit</small></div>
                <span class="dashboard-kpi__progress"><i style="width: 100%"></i></span>
            </article>
            <article class="dashboard-kpi dashboard-kpi--red">
                <span class="dashboard-kpi__icon"><i class="bi bi-graph-up-arrow"></i></span>
                <div><span class="dashboard-kpi__label">Total encaissé</span><strong>{{ number_format($collected, 0, ',', ' ') }} <em>USD</em></strong><small>{{ number_format($finances['month'], 0, ',', ' ') }} USD ce mois</small></div>
                <span class="dashboard-kpi__progress"><i style="width: {{ $collectionRate }}%"></i></span>
            </article>
            <article class="dashboard-kpi dashboard-kpi--purple">
                <span class="dashboard-kpi__icon"><i class="bi bi-exclamation-circle"></i></span>
                <div><span class="dashboard-kpi__label">Échéances en retard</span><strong>{{ number_format($installments['overdue'], 0, ',', ' ') }}</strong><small>{{ number_format($clients['overdue'], 0, ',', ' ') }} client(s) concerné(s)</small></div>
                <span class="dashboard-kpi__progress"><i style="width: {{ min(100, $installments['overdue'] * 10) }}%"></i></span>
            </article>
        </section>

        <div class="dashboard-primary-grid">
            <section class="dashboard-card dashboard-card--chart">
                <div class="dashboard-card__chart-header">
                    <div><h2>Activité des encaissements</h2><strong>{{ number_format($finances['month'], 0, ',', ' ') }} <small>USD</small></strong><p>Total encaissé ce mois</p></div>
                    <span class="status-badge status-badge--active">{{ $periodLabels[$period] }}</span>
                </div>
                @if($payment_chart->isNotEmpty())
                    <div class="dashboard-line-chart">
                        <svg viewBox="0 0 1000 240" preserveAspectRatio="none" role="img" aria-label="Évolution des encaissements">
                            <defs><linearGradient id="dashboard-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#1abb9c" stop-opacity=".18"/><stop offset="100%" stop-color="#1abb9c" stop-opacity="0"/></linearGradient></defs>
                            <g class="dashboard-line-chart__grid"><line x1="0" y1="40" x2="1000" y2="40"/><line x1="0" y1="100" x2="1000" y2="100"/><line x1="0" y1="160" x2="1000" y2="160"/><line x1="0" y1="220" x2="1000" y2="220"/></g>
                            <polygon points="{{ $chartAreaPoints }}" fill="url(#dashboard-area)"/>
                            <polyline points="{{ $chartPoints }}" class="dashboard-line-chart__line"/>
                        </svg>
                        <div class="dashboard-line-chart__labels">@foreach($payment_chart as $point)<span>{{ $point->label }}</span>@endforeach</div>
                    </div>
                    <div class="dashboard-card__footer"><span><i></i> Encaissements validés</span></div>
                @else
                    <div class="dashboard-empty"><strong>Aucun encaissement</strong><span>Aucun paiement validé sur cette période.</span></div>
                @endif
            </section>

            <section class="dashboard-card">
                <div class="dashboard-card__header"><h2>Activité récente</h2><span>{{ $recent_activity->count() }}</span></div>
                <div class="dashboard-activity">
                    @forelse($recent_activity->take(6) as $activity)
                        <div class="dashboard-activity__item">
                            <span class="dashboard-activity__avatar"><i class="bi bi-clock-history"></i></span>
                            <div><p>{{ ucfirst(str_replace(['.', '_'], ' ', $activity->action)) }}</p><small>{{ $activity->created_at->diffForHumans() }}</small></div>
                        </div>
                    @empty
                        <div class="dashboard-empty"><strong>Aucune activité récente</strong><span>Les opérations enregistrées apparaîtront ici.</span></div>
                    @endforelse
                </div>
            </section>
        </div>

        @can('reports.view')
            <div class="dashboard-secondary-grid">
                <section class="dashboard-card">
                    <div class="dashboard-card__header"><div><h2>Échéances en retard</h2><p>Dossiers nécessitant une action</p></div><a href="{{ route('subscriptions.index', ['status' => 'overdue']) }}">Voir tout</a></div>
                    <div class="dashboard-list">
                        @forelse($overdue_installments->take(5) as $item)
                            <a href="{{ route('subscriptions.installments.index', $item->subscription) }}" class="dashboard-list__item">
                                <span><strong>{{ $item->subscription->customer->first_name }} {{ $item->subscription->customer->last_name }}</strong><small>{{ $item->subscription->subscription_number }} · {{ $item->due_date->format('d/m/Y') }}</small></span>
                                <span class="dashboard-list__amount dashboard-list__amount--danger">{{ number_format((float) $item->balance, 2, ',', ' ') }} USD</span>
                            </a>
                        @empty
                            <div class="dashboard-empty"><strong>Aucun retard</strong><span>Toutes les échéances sont à jour.</span></div>
                        @endforelse
                    </div>
                </section>
                <section class="dashboard-card">
                    <div class="dashboard-card__header"><div><h2>Formules choisies</h2><p>Répartition des souscriptions</p></div></div>
                    <div class="plan-list">
                        @forelse($plan_distribution as $item)
                            <div class="plan-list__item"><div class="plan-list__header"><span>{{ $item->name }}</span><strong>{{ number_format($item->total, 0, ',', ' ') }}</strong></div><div class="plan-list__track"><span style="width: {{ ((int) $item->total / $maxPlanTotal) * 100 }}%"></span></div></div>
                        @empty
                            <div class="dashboard-empty"><strong>Aucune souscription</strong><span>La répartition apparaîtra ici.</span></div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endcan
    </div>
</x-layouts.app>
