<x-layouts.app title="Journal d’audit">
    @php
        $hasFilters = filled($filters['user_id'] ?? null)
            || filled($filters['action'] ?? null)
            || filled($filters['from'] ?? null)
            || filled($filters['to'] ?? null);
    @endphp

    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Sécurité & Traçabilité</p>
                <h1 class="resource-heading__title">Journal d’audit</h1>
                <p class="resource-heading__description">Historique immuable des opérations sensibles et des modifications système.</p>
            </div>
        </header>

        <form method="GET" action="{{ route('audit-logs.index') }}" class="resource-filters resource-filters--wide">
            <div>
                <label for="audit-user" class="form-label">Utilisateur</label>
                <select id="audit-user" name="user_id" class="form-select">
                    <option value="">Tous les utilisateurs</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>
                            {{ $user->first_name }} {{ $user->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="audit-action" class="form-label">Action</label>
                <input id="audit-action" name="action" value="{{ $filters['action'] ?? '' }}" class="form-control" placeholder="ex: payment.reversed">
            </div>
            <div>
                <label for="audit-from" class="form-label">Du</label>
                <input type="date" id="audit-from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
            </div>
            <div>
                <label for="audit-to" class="form-label">Au</label>
                <input type="date" id="audit-to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
            </div>
            <div class="resource-filters__actions">
                @if($hasFilters)
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-link resource-filter-reset">Réinitialiser</a>
                @endif
                <button class="btn btn-app-primary resource-button" type="submit">Rechercher</button>
            </div>
        </form>

        <section class="resource-table" aria-labelledby="audit-logs-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="audit-logs-title">Traces d’audit</h2>
                    <p>{{ $logs->total() }} {{ Str::plural('événement', $logs->total()) }} enregistrés</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Date & Heure</th>
                            <th scope="col">Utilisateur</th>
                            <th scope="col">Action</th>
                            <th scope="col">Entité concernée</th>
                            <th scope="col" class="text-end">Modifications (Avant / Après)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="align-top">
                                <td><span class="resource-data-table__secondary">{{ $log->created_at?->format('d/m/Y H:i') }}</span></td>
                                <td><strong>{{ $log->user ? $log->user->first_name.' '.$log->user->last_name : 'Système' }}</strong></td>
                                <td><span class="status-badge status-badge--neutral font-monospace">{{ $log->action }}</span></td>
                                <td>
                                    <span class="resource-reference">{{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</span>
                                </td>
                                <td class="text-end">
                                    <details class="d-inline-block text-start">
                                        <summary class="btn btn-sm btn-outline-secondary py-1 px-2">Consulter les détails</summary>
                                        <div class="mt-2 rounded-3 bg-dark text-light p-3 text-start font-monospace small" style="max-width: 480px; font-size: 0.75rem;">
                                            <div class="text-warning mb-1 font-bold">Avant:</div>
                                            <pre class="mb-2 text-wrap" style="color: #cbd5e1;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                                            <div class="text-info mb-1 font-bold">Après:</div>
                                            <pre class="mb-0 text-wrap" style="color: #cbd5e1;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="resource-empty">
                                        <strong>Aucune trace d’audit trouvée</strong>
                                        <span>Modifiez ou réinitialisez les filtres de recherche.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($logs->hasPages())
            <div class="resource-pagination">{{ $logs->links() }}</div>
        @endif
    </div>
</x-layouts.app>
