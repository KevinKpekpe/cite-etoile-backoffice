<x-layouts.app title="Tableau de bord">
    @php
        $periodLabels = ['day' => 'Aujourd\'hui', 'week' => '7 jours', 'month' => 'Ce mois', 'year' => 'Cette année'];
        $totalPlots = array_sum($plots);
        $activeSubscriptions = $subscriptions['active'] ?? 0;
        $contractual = (float) $finances['contractual'];
        $collected = (float) $finances['collected'];
        $collectionRate = $contractual > 0 ? min(100, ($collected / $contractual) * 100) : 0;
        $chartValues = $payment_chart->pluck('current')->map(fn ($value) => (float) $value)->values();
        $previousChartValues = $payment_chart->pluck('previous')->map(fn ($value) => (float) $value)->values();
        $chartMaximum = max(1, (float) $chartValues->concat($previousChartValues)->max());
        $chartPeriodTotal = (float) $chartValues->sum();
        $hasChartData = $chartValues->contains(fn ($value) => $value > 0) || $previousChartValues->contains(fn ($value) => $value > 0);
        $chartCount = max(1, $chartValues->count());

        // SVG coordinate computation (viewBox: 0 0 1000 200, plot Y range: 16 to 184)
        $chartCoordinates = function ($values) use ($chartMaximum, $chartCount) {
            return $values->map(function ($value, $index) use ($chartMaximum, $chartCount) {
                $x = ($index / max(1, $chartCount - 1)) * 1000;
                $y = 184 - (($value / $chartMaximum) * 168);
                return [(float) round($x, 2), (float) round($y, 2)];
            })->all();
        };

        $buildSmoothPath = function (array $coordinates): string {
            if ($coordinates === []) {
                return '';
            }
            $count = count($coordinates);
            if ($count === 1) {
                return 'M '.round($coordinates[0][0], 2).' '.round($coordinates[0][1], 2);
            }
            $path = 'M '.round($coordinates[0][0], 2).' '.round($coordinates[0][1], 2);
            for ($i = 0; $i < $count - 1; $i++) {
                $p0 = $coordinates[max(0, $i - 1)];
                $p1 = $coordinates[$i];
                $p2 = $coordinates[$i + 1];
                $p3 = $coordinates[min($count - 1, $i + 2)];

                $dx = ($p2[0] - $p1[0]);
                $dt1 = max(1, $p2[0] - $p0[0]);
                $dt2 = max(1, $p3[0] - $p1[0]);

                $m1 = ($p2[1] - $p0[1]) / $dt1;
                $m2 = ($p3[1] - $p1[1]) / $dt2;

                $cp1x = $p1[0] + $dx / 3;
                $cp1y = $p1[1] + ($m1 * $dx) / 3;
                $cp2x = $p2[0] - $dx / 3;
                $cp2y = $p2[1] - ($m2 * $dx) / 3;

                $minY = min($p1[1], $p2[1]);
                $maxY = max($p1[1], $p2[1]);
                $cp1y = max($minY, min($maxY, $cp1y));
                $cp2y = max($minY, min($maxY, $cp2y));

                $path .= ' C '.round($cp1x, 2).' '.round($cp1y, 2)
                    .' '.round($cp2x, 2).' '.round($cp2y, 2)
                    .' '.round($p2[0], 2).' '.round($p2[1], 2);
            }
            return $path;
        };

        $currentCoordinates = $chartCoordinates($chartValues);
        $previousCoordinates = $chartCoordinates($previousChartValues);
        $currentChartPath = $buildSmoothPath($currentCoordinates);
        $previousChartPath = $buildSmoothPath($previousCoordinates);
        $currentChartAreaPath = count($currentCoordinates) > 1 && $currentChartPath !== ''
            ? $currentChartPath.' L 1000 184 L 0 184 Z'
            : '';

        $chartLabelStep = match (true) { $chartCount > 16 => 5, $chartCount > 8 => 3, $chartCount > 5 => 2, default => 1 };
        $maxPlanTotal = max(1, (int) $plan_distribution->max('total'));

        // Y-axis labels: 4 levels (0%, 33%, 67%, 100% of max)
        $yLevels = [0, 0.33, 0.67, 1.0];
        $yAxisLabels = collect($yLevels)->map(function ($ratio) use ($chartMaximum) {
            $value = $chartMaximum * $ratio;
            $label = $value >= 1000 ? number_format($value / 1000, 1).'k' : number_format($value, 0);
            $y = 220 - ($ratio * 200);
            $bottomPct = round(((240 - $y) / 240) * 100, 2);
            return ['pct' => $bottomPct, 'label' => $label, 'y' => round($y, 2)];
        });
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

            {{-- KPI : Clients --}}
            <article class="dashboard-kpi dashboard-kpi--teal">
                <div class="dashboard-kpi__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <div class="dashboard-kpi__body">
                    <div class="dashboard-kpi__label">Clients</div>
                    <div class="dashboard-kpi__value-row">
                        <span class="dashboard-kpi__value">{{ number_format($clients['total'], 0, ',', ' ') }}</span>
                        <span class="dashboard-kpi__badge dashboard-kpi__badge--up">{{ number_format($clients['active'], 0, ',', ' ') }} actifs</span>
                    </div>
                    <div class="dashboard-kpi__subtext">{{ number_format($clients['new_month'], 0, ',', ' ') }} nouveau(x) ce mois</div>
                </div>
                <div class="dashboard-kpi__spark" aria-hidden="true">
                    @foreach([35, 50, 42, 60, 55, 68, 62, 75, 70, 85] as $h)
                        <div class="dashboard-kpi__bar" style="height:{{ $h }}%"></div>
                    @endforeach
                </div>
            </article>

            {{-- KPI : Souscriptions --}}
            <article class="dashboard-kpi dashboard-kpi--blue">
                <div class="dashboard-kpi__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6M9 13h4"/></svg>
                </div>
                <div class="dashboard-kpi__body">
                    <div class="dashboard-kpi__label">Souscriptions actives</div>
                    <div class="dashboard-kpi__value-row">
                        <span class="dashboard-kpi__value">{{ number_format($activeSubscriptions, 0, ',', ' ') }}</span>
                    </div>
                    <div class="dashboard-kpi__subtext">Dossiers en cours</div>
                </div>
                <div class="dashboard-kpi__spark" aria-hidden="true">
                    @foreach([40, 55, 48, 70, 60, 76, 68, 86, 80, 90] as $h)
                        <div class="dashboard-kpi__bar" style="height:{{ $h }}%"></div>
                    @endforeach
                </div>
            </article>

            {{-- KPI : Parcelles --}}
            <article class="dashboard-kpi dashboard-kpi--yellow">
                <div class="dashboard-kpi__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <div class="dashboard-kpi__body">
                    <div class="dashboard-kpi__label">Parcelles disponibles</div>
                    <div class="dashboard-kpi__value-row">
                        <span class="dashboard-kpi__value">{{ number_format($plots['available'] ?? 0, 0, ',', ' ') }}</span>
                        <span class="dashboard-kpi__badge dashboard-kpi__badge--down">{{ number_format($totalPlots, 0, ',', ' ') }} total</span>
                    </div>
                    <div class="dashboard-kpi__subtext">sur {{ number_format($totalPlots, 0, ',', ' ') }} parcelles</div>
                </div>
                <div class="dashboard-kpi__spark" aria-hidden="true">
                    @foreach([86, 78, 72, 64, 60, 52, 48, 40, 36, 30] as $h)
                        <div class="dashboard-kpi__bar" style="height:{{ $h }}%"></div>
                    @endforeach
                </div>
            </article>

            {{-- KPI : Valeur contractuelle --}}
            <article class="dashboard-kpi dashboard-kpi--green">
                <div class="dashboard-kpi__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <div class="dashboard-kpi__body">
                    <div class="dashboard-kpi__label">Valeur contractuelle</div>
                    <div class="dashboard-kpi__value-row">
                        <span class="dashboard-kpi__value">{{ number_format($contractual, 0, ',', ' ') }} <em>USD</em></span>
                        <span class="dashboard-kpi__badge dashboard-kpi__badge--up">{{ number_format(min(100, $collectionRate), 0) }}%</span>
                    </div>
                    <div class="dashboard-kpi__subtext">Portefeuille souscrit</div>
                </div>
                <div class="dashboard-kpi__progress-wrap">
                    <div class="dashboard-kpi__progress-bar" style="width:100%"></div>
                </div>
            </article>

            {{-- KPI : Total encaissé --}}
            <article class="dashboard-kpi dashboard-kpi--red">
                <div class="dashboard-kpi__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="dashboard-kpi__body">
                    <div class="dashboard-kpi__label">Total encaissé</div>
                    <div class="dashboard-kpi__value-row">
                        <span class="dashboard-kpi__value">{{ number_format($collected, 0, ',', ' ') }} <em>USD</em></span>
                        <span class="dashboard-kpi__badge dashboard-kpi__badge--up">{{ number_format(min(100, $collectionRate), 0) }}%</span>
                    </div>
                    <div class="dashboard-kpi__subtext">{{ number_format($finances['month'], 0, ',', ' ') }} USD ce mois</div>
                </div>
                <div class="dashboard-kpi__progress-wrap">
                    <div class="dashboard-kpi__progress-bar" style="width:{{ $collectionRate }}%"></div>
                </div>
            </article>

            {{-- KPI : Échéances en retard --}}
            <article class="dashboard-kpi dashboard-kpi--purple">
                <div class="dashboard-kpi__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div class="dashboard-kpi__body">
                    <div class="dashboard-kpi__label">Échéances en retard</div>
                    <div class="dashboard-kpi__value-row">
                        <span class="dashboard-kpi__value">{{ number_format($installments['overdue'], 0, ',', ' ') }}</span>
                        @if($installments['overdue'] > 0)
                            <span class="dashboard-kpi__badge dashboard-kpi__badge--down">{{ $clients['overdue'] }} client(s)</span>
                        @endif
                    </div>
                    <div class="dashboard-kpi__subtext">{{ number_format($clients['overdue'], 0, ',', ' ') }} client(s) concerné(s)</div>
                </div>
                <div class="dashboard-kpi__progress-wrap">
                    <div class="dashboard-kpi__progress-bar" style="width:{{ min(100, $installments['overdue'] * 10) }}%"></div>
                </div>
            </article>

        </section>

        <div class="dashboard-primary-grid">
            <section class="dashboard-card dashboard-card--chart">
                <div class="dashboard-card__chart-header">
                    <div><h2>Activité des encaissements</h2><strong>{{ number_format($chartPeriodTotal, 0, ',', ' ') }} <small>USD</small></strong><p>Total encaissé · {{ $periodLabels[$period] }}</p></div>
                    <span class="status-badge status-badge--active">{{ $periodLabels[$period] }}</span>
                </div>
                @if($hasChartData)
                    <div class="dashboard-chart-container" style="position: relative; height: 320px; width: 100%; padding: 1rem 1.25rem;">
                        <canvas id="dashboard-chart-canvas"
                                data-labels='@json($payment_chart->pluck("label"))'
                                data-current='@json($payment_chart->pluck("current"))'
                                data-previous='@json($payment_chart->pluck("previous"))'>
                        </canvas>
                    </div>
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
                    <div class="dashboard-card__header"><div><h2>Échéances à venir</h2><p>Paiements attendus sous 7 jours</p></div></div>
                    <div class="dashboard-list">
                        @forelse($due_soon->take(5) as $item)
                            <a href="{{ route('subscriptions.installments.index', $item->subscription) }}" class="dashboard-list__item">
                                <span><strong>{{ $item->subscription->customer->first_name }} {{ $item->subscription->customer->last_name }}</strong><small>{{ $item->subscription->subscription_number }} · {{ $item->due_date->format('d/m/Y') }}</small></span>
                                <span class="dashboard-list__amount">{{ number_format((float) $item->balance, 2, ',', ' ') }} USD</span>
                            </a>
                        @empty
                            <div class="dashboard-empty"><strong>Aucune échéance proche</strong><span>Aucun paiement prévu dans les 7 prochains jours.</span></div>
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
