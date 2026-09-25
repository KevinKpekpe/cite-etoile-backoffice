<x-layouts.app :title="$customer->first_name.' '.$customer->last_name">
    <x-user-credentials-markdown />

    @php
        $allCustomerInstallments = $customer->subscriptions->pluck('installments')->flatten();
        $overdueInstallments = $allCustomerInstallments->where('status', 'overdue');
        $overdueCount = $overdueInstallments->count();
        $overdueTotal = $overdueInstallments->sum('balance');
        $firstOverdueSub = $customer->subscriptions->first(fn ($subscription) => $subscription->installments->where('status', 'overdue')->isNotEmpty());
        $validatedPayments = $customer->payments->where('status', 'validated')->sum('amount');
        $remainingBalance = $customer->subscriptions->sum('balance');
        $statusLabels = ['prospect' => 'Prospect', 'active' => 'Actif', 'settled' => 'Soldé', 'suspended' => 'Suspendu', 'archived' => 'Archivé'];
    @endphp

    <div class="customer-record">
        @if($overdueCount > 0)
            <div class="record-alert record-alert--danger">
                <div><strong>Retard de paiement détecté</strong><p>{{ $overdueCount }} {{ Str::plural('échéance', $overdueCount) }} en retard pour {{ number_format((float) $overdueTotal, 2, ',', ' ') }} USD.</p></div>
                @if($firstOverdueSub)
                    @can('payments.create')
                        <a href="{{ route('payments.create', ['subscription' => $firstOverdueSub->id]) }}" class="btn btn-danger resource-button">Encaisser l’impayé</a>
                    @endcan
                @endif
            </div>
        @endif

        @if($customer->trashed())
            <div class="record-alert record-alert--danger">
                <div><strong>Client placé en corbeille</strong><p>Supprimé le {{ $customer->deleted_at->format('d/m/Y à H:i') }}. Le dossier reste restaurable.</p></div>
                <div class="resource-heading__actions">
                    @can('customers.restore')
                        <form method="POST" action="{{ route('customers.restore', $customer) }}">@csrf<button class="btn btn-success resource-button" type="submit">Restaurer</button></form>
                    @endcan
                    @can('customers.force_delete')
                        <form method="POST" action="{{ route('customers.force-delete', $customer) }}" data-confirm="Supprimer définitivement ce client ? Cette action est irréversible.">@csrf @method('DELETE')<button class="btn btn-danger resource-button" type="submit">Supprimer définitivement</button></form>
                    @endcan
                </div>
            </div>
        @endif

        <header class="record-heading">
            <div class="record-heading__identity">
                <span class="record-heading__avatar" aria-hidden="true">
                    @if($customer->avatar_path)
                        <img src="{{ Storage::url($customer->avatar_path) }}" alt="{{ $customer->first_name }}" class="w-full h-full object-cover rounded-full">
                    @else
                        {{ mb_strtoupper(mb_substr($customer->first_name, 0, 1).mb_substr($customer->last_name, 0, 1)) }}
                    @endif
                </span>
                <div><p class="app-kicker">{{ $customer->customer_number }}</p><h1>{{ $customer->first_name }} {{ $customer->middle_name }} {{ $customer->last_name }}</h1><p>{{ $customer->phone }} · {{ $customer->email ?: 'Sans e-mail' }}</p></div>
            </div>
            <div class="resource-heading__actions">
                @if(! $customer->trashed())
                    @can('customers.update')<a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary resource-button">Modifier</a>@endcan
                    @can('subscriptions.create')<a href="{{ route('subscriptions.create', ['customer_id' => $customer->id]) }}" class="btn btn-app-primary resource-button">Réserver une parcelle</a>@endcan
                    @can('customers.delete')
                        @if($customer->status !== 'archived')
                            <form method="POST" action="{{ route('customers.archive', $customer) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary resource-button" type="submit">Archiver</button></form>
                        @endif
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="Placer ce client en corbeille ?">@csrf @method('DELETE')<button class="btn btn-outline-danger resource-button" type="submit">Corbeille</button></form>
                    @endcan
                @endif
            </div>
        </header>

        <div class="row g-4">
            <div class="col-lg-4">
                <section class="record-panel h-100">
                    <div class="record-panel__header"><h2>Profil</h2><span class="status-badge status-badge--{{ $customer->status }}">{{ $statusLabels[$customer->status] ?? ucfirst($customer->status) }}</span></div>
                    <dl class="record-definition-list">
                        <div><dt>Adresse</dt><dd>{{ $customer->address ?: 'Non renseignée' }}</dd></div>
                        <div><dt>Commune</dt><dd>{{ $customer->commune ?: 'Non renseignée' }}</dd></div>
                        <div><dt>Ville</dt><dd>{{ $customer->city ?: 'Non renseignée' }}</dd></div>
                        <div><dt>Pays</dt><dd>{{ $customer->country ?: 'Non renseigné' }}</dd></div>
                        <div><dt>Nationalité</dt><dd>{{ $customer->nationality ?: 'Non renseignée' }}</dd></div>
                        @if($customer->birth_date)<div><dt>Date de naissance</dt><dd>{{ $customer->birth_date->format('d/m/Y') }}</dd></div>@endif
                        @if($customer->gender)<div><dt>Genre</dt><dd>{{ ucfirst($customer->gender) }}</dd></div>@endif
                        <div><dt>Responsable</dt><dd>{{ $customer->assignedAgent ? $customer->assignedAgent->first_name.' '.$customer->assignedAgent->last_name : 'Non attribué' }}</dd></div>
                        <div><dt>Observations</dt><dd class="whitespace-pre-line">{{ $customer->internal_notes ?: 'Aucune observation' }}</dd></div>
                    </dl>
                </section>
            </div>
            <div class="col-lg-8">
                <section class="record-panel h-100">
                    <div class="record-panel__header"><h2>Situation financière</h2></div>
                    <div class="record-metrics">
                        <div><span>Souscriptions</span><strong>{{ $customer->subscriptions->count() }}</strong><small>Dossiers enregistrés</small></div>
                        <div class="record-metric--success"><span>Total versé</span><strong>{{ number_format((float) $validatedPayments, 2, ',', ' ') }} <small>USD</small></strong><small>Paiements validés</small></div>
                        <div class="{{ $overdueCount > 0 ? 'record-metric--danger' : '' }}"><span>Solde restant</span><strong>{{ number_format((float) $remainingBalance, 2, ',', ' ') }} <small>USD</small></strong><small>{{ $overdueCount > 0 ? number_format((float) $overdueTotal, 2, ',', ' ').' USD en retard' : 'Situation à jour' }}</small></div>
                    </div>
                </section>
            </div>
        </div>

        <section class="resource-table">
            <div class="resource-table__header"><div><h2>Parcelles et souscriptions</h2><p>{{ $customer->subscriptions->count() }} {{ Str::plural('souscription', $customer->subscriptions->count()) }}</p></div></div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead><tr><th>Souscription</th><th>Parcelle</th><th>Formule</th><th>Échéances</th><th>Statut</th><th class="text-end">Solde</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @forelse($customer->subscriptions as $subscription)
                            @php
                                $subOverdueCount = $subscription->installments->where('status', 'overdue')->count();
                                $canPay = $subscription->financial_status !== 'paid' && !in_array($subscription->commercial_status, ['completed', 'cancelled', 'terminated'], true);
                            @endphp
                            <tr class="{{ $subOverdueCount > 0 ? 'resource-data-table__row--attention' : '' }}">
                                <td><a href="{{ route('subscriptions.show', $subscription) }}" class="resource-reference">{{ $subscription->subscription_number }}</a></td>
                                <td>{{ $subscription->plot->reference }}</td><td class="resource-data-table__secondary">{{ $subscription->paymentPlan->name }}</td>
                                <td>@if($subOverdueCount > 0)<span class="status-badge status-badge--danger">{{ $subOverdueCount }} en retard</span>@elseif($subscription->financial_status === 'paid')<span class="status-badge status-badge--settled">Soldée</span>@else<span class="status-badge status-badge--neutral">À jour</span>@endif</td>
                                <td><span class="status-badge status-badge--neutral">{{ $subscription->commercial_status }}</span></td>
                                <td class="record-money text-end">{{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</td>
                                <td class="text-end"><div class="record-table-actions"><a href="{{ route('subscriptions.show', $subscription) }}" class="resource-row-action">Détails</a>@if($canPay)@can('payments.create')<a href="{{ route('payments.create', ['subscription' => $subscription->id]) }}" class="resource-row-action {{ $subOverdueCount > 0 ? 'resource-row-action--danger' : '' }}">Encaisser</a>@endcan @endif</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="resource-empty"><strong>Aucune souscription</strong><span>Ce client ne possède encore aucune parcelle réservée.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-6">
                <section class="record-panel h-100">
                    <div class="record-panel__header"><h2>Paiements et reçus</h2><span class="dashboard-count">{{ $customer->payments->count() }}</span></div>
                    <div class="record-list">
                        @forelse($customer->payments as $payment)
                            <div class="record-list__item"><div><a href="{{ route('payments.show', $payment) }}" class="resource-reference">{{ $payment->payment_reference }}</a><small>{{ $payment->payment_date->format('d/m/Y H:i') }} · {{ ucfirst($payment->payment_method) }}</small></div><div class="record-list__action"><strong>{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</strong>@if($payment->receipt)@can('receipts.download')<a href="{{ route('receipts.download', $payment->receipt) }}">Reçu PDF</a>@endcan @endif</div></div>
                        @empty
                            <div class="record-empty">Aucun paiement enregistré.</div>
                        @endforelse
                    </div>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="record-panel h-100">
                    <div class="record-panel__header"><h2>Documents privés</h2><span class="dashboard-count">{{ $customer->documents->count() }}</span></div>
                    <div class="record-list">
                        @forelse($customer->documents as $document)
                            <div class="record-list__item"><div><strong>{{ $document->name }}</strong><small>{{ $document->document_type }}</small></div>@can('documents.download')<a class="resource-row-action" href="{{ route('customers.documents.download', [$customer, $document]) }}">Télécharger</a>@endcan</div>
                        @empty
                            <div class="record-empty">Aucun document importé.</div>
                        @endforelse
                    </div>
                    @can('customers.update')
                        <form method="POST" enctype="multipart/form-data" action="{{ route('customers.documents.store', $customer) }}" class="record-upload">@csrf
                            <select name="document_type" class="form-select">@foreach(['identity'=>'Identité','photo'=>'Photo','contract'=>'Contrat','payment_proof'=>'Preuve de paiement','subscription_document'=>'Souscription','other'=>'Autre'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="form-control">
                            <button class="btn btn-app-primary resource-button" type="submit">Ajouter</button>
                            @error('document')<span class="form-field__error record-upload__error">{{ $message }}</span>@enderror
                        </form>
                    @endcan
                </section>
            </div>
        </div>
    </div>
</x-layouts.app>
