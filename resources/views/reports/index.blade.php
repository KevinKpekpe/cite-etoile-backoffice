<x-layouts.app title="Rapports">
    @php
        $hasFilters = filled($filters['from'] ?? null)
            || filled($filters['to'] ?? null)
            || filled($filters['customer_status'] ?? null)
            || filled($filters['plot_status'] ?? null)
            || filled($filters['payment_plan_id'] ?? null)
            || filled($filters['agent_id'] ?? null);

        $customerStatusLabels = ['prospect' => 'Prospect', 'active' => 'Actif', 'settled' => 'Soldé', 'suspended' => 'Suspendu', 'archived' => 'Archivé'];
        $plotStatusLabels = ['available' => 'Disponible', 'reserved' => 'Réservée', 'subscribed' => 'Souscrite', 'blocked' => 'Bloquée', 'unavailable' => 'Indisponible'];
    @endphp

    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Pilotage & Statistiques</p>
                <h1 class="resource-heading__title">Rapports et exports</h1>
                <p class="resource-heading__description">Analysez l’activité commerciale et financière selon vos critères de filtrage.</p>
            </div>
        </header>

        {{-- Formulaire de filtrage --}}
        <form method="GET" action="{{ route('reports.index') }}" class="resource-filters resource-filters--wide">
            <div>
                <label for="report-from" class="form-label">Du</label>
                <input type="date" id="report-from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
            </div>
            <div>
                <label for="report-to" class="form-label">Au</label>
                <input type="date" id="report-to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
            </div>
            <div>
                <label for="report-customer-status" class="form-label">Statut client</label>
                <select id="report-customer-status" name="customer_status" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach($customerStatusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['customer_status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-plot-status" class="form-label">Statut parcelle</label>
                <select id="report-plot-status" name="plot_status" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach($plotStatusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['plot_status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-plan" class="form-label">Formule</label>
                <select id="report-plan" name="payment_plan_id" class="form-select">
                    <option value="">Toutes les formules</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((string)($filters['payment_plan_id'] ?? '') === (string)$plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-agent" class="form-label">Agent commercial</label>
                <select id="report-agent" name="agent_id" class="form-select">
                    <option value="">Tous les agents</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((string)($filters['agent_id'] ?? '') === (string)$agent->id)>{{ $agent->first_name }} {{ $agent->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="resource-filters__actions">
                @if($hasFilters)
                    <a href="{{ route('reports.index') }}" class="btn btn-link resource-filter-reset">Réinitialiser</a>
                @endif
                <button class="btn btn-app-primary resource-button" type="submit">Appliquer</button>
            </div>
        </form>

        <section class="report-metrics" aria-label="Synthèse des rapports">
            <div><span>Clients</span><strong>{{ number_format($customers->count(), 0, ",", " ") }}</strong><small>Dossiers extraits</small></div>
            <div><span>Parcelles</span><strong>{{ number_format($plots->count(), 0, ",", " ") }}</strong><small>Biens extraits</small></div>
            <div class="report-metric--success"><span>Paiements encaissés</span><strong>{{ number_format((float) $paymentTotal, 2, ",", " ") }} <em>USD</em></strong><small>Transactions validées</small></div>
            <div class="report-metric--danger"><span>Total impayé</span><strong>{{ number_format((float) $overdueTotal, 2, ",", " ") }} <em>USD</em></strong><small>Solde en retard</small></div>
        </section>

        {{-- Sections Rapports --}}
        @foreach(['customers' => 'Rapport des dossiers clients', 'plots' => 'Rapport des parcelles', 'payments' => 'Rapport des paiements', 'overdue' => 'Rapport des impayés'] as $type => $title)
            @php($rows = ${$type})
            <section class="resource-table mb-4" aria-labelledby="report-section-{{ $type }}">
                <div class="resource-table__header">
                    <div>
                        <h2 id="report-section-{{ $type }}">{{ $title }}</h2>
                        <p>{{ $rows->count() }} enregistrement(s) extrait(s)</p>
                    </div>
                    <div>
                        <a href="{{ route('reports.export', ['report' => $type, ...$filters]) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i> Exporter CSV
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table resource-data-table align-middle mb-0">
                        <tbody>
                            @forelse($rows->take(10) as $row)
                                <tr>
                                    <td>
                                        @if($type === 'customers')
                                            <a href="{{ route('customers.show', $row) }}" class="resource-reference me-2">{{ $row->customer_number }}</a>
                                            <strong>{{ $row->first_name }} {{ $row->last_name }}</strong>
                                            <span class="status-badge status-badge--{{ $row->status }} ms-2">{{ $customerStatusLabels[$row->status] ?? ucfirst($row->status) }}</span>
                                        @elseif($type === 'plots')
                                            <a href="{{ route('plots.show', $row) }}" class="resource-reference me-2">{{ $row->reference }}</a>
                                            <span>{{ $row->avenue->neighborhood->name }} · {{ $row->avenue->name }}</span>
                                            <span class="status-badge status-badge--{{ $row->commercial_status }} ms-2">{{ $plotStatusLabels[$row->commercial_status] ?? ucfirst($row->commercial_status) }}</span>
                                        @elseif($type === 'payments')
                                            <a href="{{ route('payments.show', $row) }}" class="resource-reference me-2">{{ $row->payment_reference }}</a>
                                            @if($row->customer)
                                                <strong>{{ $row->customer->first_name }} {{ $row->customer->last_name }}</strong>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        @else
                                            @if($row->subscription)
                                                <a href="{{ route('subscriptions.show', $row->subscription) }}" class="resource-reference me-2">{{ $row->subscription->subscription_number }}</a>
                                                @if($row->subscription->customer)
                                                    <strong>{{ $row->subscription->customer->first_name }} {{ $row->subscription->customer->last_name }}</strong>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                                <small class="text-muted ms-2">({{ $row->subscription->plot?->reference }})</small>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        @if($type === 'customers')
                                            <a class="btn btn-sm btn-outline-primary py-1 px-2" href="{{ route('reports.customers.statement', $row) }}">
                                                <i class="bi bi-file-earmark-pdf me-1"></i> Relevé PDF
                                            </a>
                                        @elseif($type === 'plots')
                                            <span class="record-money">{{ $row->base_price ? number_format((float) $row->base_price, 2, ',', ' ').' USD' : '—' }}</span>
                                        @elseif($type === 'payments')
                                            <span class="record-money text-success font-bold">{{ number_format((float) $row->amount, 2, ',', ' ') }} {{ $row->currency }}</span>
                                        @else
                                            <span class="status-badge status-badge--danger me-2">{{ $row->due_date->diffInDays(now()) }} j. retard</span>
                                            <span class="record-money text-danger font-bold">{{ number_format((float) $row->balance, 2, ',', ' ') }} USD</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">
                                        <div class="resource-empty py-3">
                                            <span>Aucune donnée enregistrée pour ce filtre.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
</x-layouts.app>
