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
        $customerAvatar = $customer->avatar_path ?? $customer->user?->avatar_path;
    @endphp

    <div class="customer-record">

        @if($overdueCount > 0)
            <div class="record-alert record-alert--danger">
                <div>
                    <strong>Retard de paiement détecté</strong>
                    <p>{{ $overdueCount }} {{ Str::plural('échéance', $overdueCount) }} en retard pour {{ number_format((float) $overdueTotal, 2, ',', ' ') }} USD.</p>
                </div>
                @if($firstOverdueSub)
                    @can('payments.create')
                        <a href="{{ route('payments.create', ['subscription' => $firstOverdueSub->id]) }}" class="btn btn-danger resource-button">Encaisser l'impayé</a>
                    @endcan
                @endif
            </div>
        @endif

        @if($customer->trashed())
            <div class="record-alert record-alert--danger">
                <div>
                    <strong>{{ __("Client placé en corbeille") }}</strong>
                    <p>Supprimé le {{ $customer->deleted_at->format('d/m/Y à H:i') }}. Le dossier reste restaurable.</p>
                </div>
                <div class="resource-heading__actions">
                    @can('customers.restore')
                        <form method="POST" action="{{ route('customers.restore', $customer) }}">
                            @csrf
                            <button class="btn btn-success resource-button" type="submit">{{ __("Restaurer") }}</button>
                        </form>
                    @endcan
                    @can('customers.force_delete')
                        <form method="POST" action="{{ route('customers.force-delete', $customer) }}" data-confirm="Supprimer définitivement ce client ? Cette action est irréversible.">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger resource-button" type="submit">{{ __("Supprimer définitivement") }}</button>
                        </form>
                    @endcan
                </div>
            </div>
        @endif

        <header class="record-heading">
            <div class="record-heading__identity">
                <span class="record-heading__avatar" aria-hidden="true">
                    @if($customerAvatar)
                        <img src="{{ Storage::url($customerAvatar) }}" alt="{{ $customer->first_name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    @else
                        {{ mb_strtoupper(mb_substr($customer->first_name, 0, 1).mb_substr($customer->last_name, 0, 1)) }}
                    @endif
                </span>
                <div>
                    <p class="app-kicker">{{ $customer->customer_number }}</p>
                    <h1>{{ $customer->first_name }} {{ $customer->middle_name }} {{ $customer->last_name }}</h1>
                    <p>
                        <span class="status-badge status-badge--{{ $customer->status }}">{{ __('statuses.'.$customer->status) }}</span>
                        · {{ $customer->phone }}
                        @if($customer->email) · {{ $customer->email }} @endif
                    </p>
                </div>
            </div>
            <div class="resource-heading__actions">
                @if(! $customer->trashed())
                    @can('reports.view')
                        <a href="{{ route('reports.customers.statement', $customer) }}" class="btn btn-outline-secondary resource-button">
                            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>{{ __("Relevé client") }}
                        </a>
                    @endcan
                    @can('customers.update')
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary resource-button">
                            <i class="bi bi-pencil" aria-hidden="true"></i>{{ __("Modifier") }}
                        </a>
                    @endcan
                    @can('subscriptions.create')
                        <a href="{{ route('subscriptions.create', ['customer_id' => $customer->id]) }}" class="btn btn-app-primary resource-button">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>{{ __("Réserver une parcelle") }}
                        </a>
                    @endcan
                    @can('customers.delete')
                        @if($customer->status !== 'archived')
                            <form method="POST" action="{{ route('customers.archive', $customer) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-outline-secondary resource-button" type="submit">{{ __("Archiver") }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="Placer ce client en corbeille ?">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger resource-button" type="submit">{{ __("Corbeille") }}</button>
                        </form>
                    @endcan
                @endif
            </div>
        </header>

        {{-- KPI financiers --}}
        <div class="record-metrics record-metrics--bordered">
            <div>
                <span>{{ __("Souscriptions") }}</span>
                <strong>{{ $customer->subscriptions->count() }}</strong>
                <small>{{ __("Dossiers enregistrés") }}</small>
            </div>
            <div class="record-metric--success">
                <span>{{ __("Total versé") }}</span>
                <strong>{{ number_format((float) $validatedPayments, 2, ',', ' ') }} <small>USD</small></strong>
                <small>{{ __("Paiements validés") }}</small>
            </div>
            <div class="{{ $overdueCount > 0 ? 'record-metric--danger' : '' }}">
                <span>{{ __("Solde restant") }}</span>
                <strong>{{ number_format((float) $remainingBalance, 2, ',', ' ') }} <small>USD</small></strong>
                <small>{{ $overdueCount > 0 ? number_format((float) $overdueTotal, 2, ',', ' ').' USD en retard' : 'Situation à jour' }}</small>
            </div>
        </div>

        {{-- Profil et coordonnées --}}
        <div class="detail-sheet">
            <section class="detail-section">
                <div class="detail-section__heading">
                    <p class="app-kicker">{{ __("Dossier") }}</p>
                    <h2>{{ __("Profil et coordonnées") }}</h2>
                </div>
                <dl class="detail-grid">
                    <div><dt>{{ __("Téléphone principal") }}</dt><dd>{{ $customer->phone }}</dd></div>
                    @if($customer->secondary_phone)
                        <div><dt>{{ __("Téléphone secondaire") }}</dt><dd>{{ $customer->secondary_phone }}</dd></div>
                    @endif
                    @if($customer->whatsapp)
                        <div>
                            <dt>WhatsApp</dt>
                            <dd><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->whatsapp) }}" target="_blank" rel="noopener noreferrer">{{ $customer->whatsapp }}</a></dd>
                        </div>
                    @endif
                    <div><dt>{{ __("E-mail") }}</dt><dd>{{ $customer->email ?: 'Non renseigné' }}</dd></div>
                    <div><dt>{{ __("Adresse résidentielle") }}</dt><dd>{{ $customer->address ?: 'Non renseignée' }}</dd></div>
                    <div><dt>{{ __("Commune / Ville") }}</dt><dd>{{ implode(', ', array_filter([$customer->commune, $customer->city])) ?: 'Non renseignée' }}</dd></div>
                    <div><dt>{{ __("Pays") }}</dt><dd>{{ $customer->country ?: 'Non renseigné' }}</dd></div>
                    <div><dt>{{ __("Nationalité") }}</dt><dd>{{ $customer->nationality ?: 'Non renseignée' }}</dd></div>
                    @if($customer->birth_date)
                        <div><dt>{{ __("Date de naissance") }}</dt><dd>{{ $customer->birth_date->format('d/m/Y') }}</dd></div>
                    @endif
                    @if($customer->gender)
                        <div><dt>{{ __("Genre") }}</dt><dd>{{ ucfirst($customer->gender) }}</dd></div>
                    @endif
                    <div><dt>{{ __("Agent responsable") }}</dt><dd>{{ $customer->assignedAgent ? $customer->assignedAgent->first_name.' '.$customer->assignedAgent->last_name : 'Non attribué' }}</dd></div>
                </dl>
                @if($customer->internal_notes)
                    <div>
                        <p style="font-size:0.66rem;font-weight:600;color:var(--app-text-muted);margin-bottom:0.25rem;">{{ __("Observations") }}</p>
                        <p style="font-size:0.78rem;white-space:pre-line;margin:0;">{{ $customer->internal_notes }}</p>
                    </div>
                @endif
            </section>
        </div>

        {{-- Tableau souscriptions --}}
        <section class="resource-table" aria-labelledby="customer-subs-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="customer-subs-title">{{ __("Parcelles et souscriptions") }}</h2>
                    <p>{{ $customer->subscriptions->count() }} {{ Str::plural('souscription', $customer->subscriptions->count()) }}</p>
                </div>
                @can('subscriptions.create')
                    <a href="{{ route('subscriptions.create', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-app-primary">{{ __("Nouvelle souscription") }}</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __("Souscription") }}</th>
                            <th>{{ __("Parcelle") }}</th>
                            <th>{{ __("Formule") }}</th>
                            <th>{{ __("Échéances") }}</th>
                            <th>{{ __("Statut") }}</th>
                            <th class="text-end">{{ __("Solde") }}</th>
                            <th class="text-end">{{ __("Actions") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->subscriptions as $subscription)
                            @php
                                $subOverdueCount = $subscription->installments->where('status', 'overdue')->count();
                                $canPay = $subscription->financial_status !== 'paid' && !in_array($subscription->commercial_status, ['completed', 'cancelled', 'terminated'], true);
                            @endphp
                            <tr class="{{ $subOverdueCount > 0 ? 'resource-data-table__row--attention' : '' }}">
                                <td>
                                    <a href="{{ route('subscriptions.show', $subscription) }}" class="resource-reference">{{ $subscription->subscription_number }}</a>
                                </td>
                                <td>
                                    <strong>{{ $subscription->plot->reference }}</strong>
                                    @if($subscription->plot->avenue?->neighborhood)
                                        <small class="d-block text-muted" style="font-size:0.72rem;">{{ $subscription->plot->avenue->neighborhood->name }}</small>
                                    @endif
                                </td>
                                <td class="resource-data-table__secondary">{{ $subscription->paymentPlan->name }}</td>
                                <td>
                                    @if($subOverdueCount > 0)
                                        <span class="status-badge status-badge--danger">{{ $subOverdueCount }} en retard</span>
                                    @elseif($subscription->financial_status === 'paid')
                                        <span class="status-badge status-badge--settled">{{ __("Soldée") }}</span>
                                    @else
                                        <span class="status-badge status-badge--neutral">{{ __("À jour") }}</span>
                                    @endif
                                </td>
                                <td><span class="status-badge status-badge--neutral">{{ __('statuses.'.$subscription->commercial_status) }}</span></td>
                                <td class="record-money text-end">{{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</td>
                                <td class="text-end">
                                    <div class="record-table-actions">
                                        <a href="{{ route('subscriptions.show', $subscription) }}" class="resource-row-action">{{ __("Détails") }}</a>
                                        @if($canPay)
                                            @can('payments.create')
                                                <a href="{{ route('payments.create', ['subscription' => $subscription->id]) }}" class="resource-row-action {{ $subOverdueCount > 0 ? 'resource-row-action--danger' : '' }}">{{ __("Encaisser") }}</a>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="resource-empty">
                                        <strong>{{ __("Aucune souscription") }}</strong>
                                        <span>{{ __("Ce client ne possède encore aucune parcelle réservée.") }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Historique des paiements --}}
        <section class="resource-table" aria-labelledby="customer-payments-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="customer-payments-title">{{ __("Paiements et reçus") }}</h2>
                    <p>{{ $customer->payments->count() }} versement(s) enregistré(s)</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __("Référence") }}</th>
                            <th>{{ __("Date") }}</th>
                            <th>{{ __("Mode") }}</th>
                            <th class="text-end">{{ __("Montant") }}</th>
                            <th class="text-end">{{ __("Reçu") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->payments as $payment)
                            <tr>
                                <td><a href="{{ route('payments.show', $payment) }}" class="resource-reference">{{ $payment->payment_reference }}</a></td>
                                <td class="resource-data-table__secondary">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                <td class="resource-data-table__secondary">{{ __('payment_methods.'.$payment->payment_method) }}</td>
                                <td class="record-money text-end">{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                <td class="text-end">
                                    @if($payment->receipt)
                                        @can('receipts.download')
                                            <a href="{{ route('receipts.download', $payment->receipt) }}" class="btn btn-sm btn-outline-secondary">{{ __("Télécharger") }}</a>
                                        @endcan
                                    @else
                                        <span class="text-muted small">{{ __("Non généré") }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="resource-empty">
                                        <strong>{{ __("Aucun paiement enregistré") }}</strong>
                                        <span>{{ __("Les versements associés à ce client apparaîtront ici.") }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Documents privés --}}
        <section class="resource-table" aria-labelledby="customer-docs-title">
            <div class="resource-table__header">
                <div>
                    <h2 id="customer-docs-title">{{ __("Documents privés") }}</h2>
                    <p>{{ $customer->documents->count() }} document(s) importé(s)</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table resource-data-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __("Nom") }}</th>
                            <th>{{ __("Type") }}</th>
                            <th class="text-end">{{ __("Action") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->documents as $document)
                            <tr>
                                <td><strong>{{ $document->name }}</strong></td>
                                <td class="resource-data-table__secondary">{{ ucfirst($document->document_type) }}</td>
                                <td class="text-end">
                                    @can('documents.download')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('customers.documents.download', [$customer, $document]) }}">
                                            <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __("Télécharger") }}
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                    <div class="resource-empty">
                                        <strong>{{ __("Aucun document importé") }}</strong>
                                        <span>Ajoutez une pièce d'identité, une photo ou tout autre document lié à ce client.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('customers.update')
                <div class="resource-table__header" style="border-top: 1px solid var(--app-border-subtle);">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('customers.documents.store', $customer) }}" class="d-flex gap-2 flex-wrap align-items-end w-100">
                        @csrf
                        <div>
                            <label class="form-label" style="font-size:0.72rem;font-weight:600;">{{ __("Type") }}</label>
                            <select name="document_type" class="form-select form-select-sm">
                                @foreach(['identity' => 'Identité', 'photo' => 'Photo', 'contract' => 'Contrat', 'payment_proof' => 'Preuve de paiement', 'subscription_document' => 'Souscription', 'other' => 'Autre'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label" style="font-size:0.72rem;font-weight:600;">{{ __("Fichier") }}</label>
                            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="form-control form-control-sm">
                        </div>
                        <button class="btn btn-sm btn-app-primary resource-button" type="submit">
                            <i class="bi bi-upload me-1" aria-hidden="true"></i>{{ __("Ajouter") }}
                        </button>
                        @error('document')<span class="form-field__error w-100">{{ $message }}</span>@enderror
                    </form>
                </div>
            @endcan
        </section>

        {{-- Accès portail client --}}
        @can('customers.update')
            <section class="detail-sheet" aria-labelledby="customer-portal-title">
                <div class="detail-section">
                    <div class="detail-section__heading">
                        <p class="app-kicker">{{ __("Sécurité") }}</p>
                        <h2 id="customer-portal-title">{{ __("Accès au portail client") }}</h2>
                    </div>

                    @if($customer->user)
                        @php $portalUser = $customer->user; $isActive = $portalUser->status === 'active'; @endphp
                        <dl class="detail-grid">
                            <div>
                                <dt>{{ __("Statut du compte") }}</dt>
                                <dd>
                                    <span class="status-badge status-badge--{{ $isActive ? 'active' : 'suspended' }}">
                                        {{ $isActive ? 'Actif' : 'Suspendu' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt>{{ __("E-mail de connexion") }}</dt>
                                <dd>{{ $portalUser->email }}</dd>
                            </div>
                            <div>
                                <dt>{{ __("Dernière connexion") }}</dt>
                                <dd>{{ $portalUser->last_login_at?->translatedFormat('d F Y à H:i') ?? 'Jamais connecté' }}</dd>
                            </div>
                            <div>
                                <dt>{{ __("Mot de passe") }}</dt>
                                <dd>
                                    @if($portalUser->must_change_password)
                                        <span class="status-badge status-badge--warning">{{ __("Changement requis") }}</span>
                                    @else
                                        <span class="status-badge status-badge--neutral">{{ __("Défini") }}</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <div class="record-disclosure" style="border-top: 1px solid var(--app-border-subtle); margin-top: 1.25rem; padding-top: 1rem;">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                <form method="POST" action="{{ route('customers.portal.reset-password', $customer) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary resource-button"
                                            data-confirm="Réinitialiser le mot de passe de ce client et lui envoyer de nouveaux identifiants ?">
                                        <i class="bi bi-key me-1" aria-hidden="true"></i>{{ __("Réinitialiser le mot de passe") }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('customers.portal.resend-credentials', $customer) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary resource-button">
                                        <i class="bi bi-envelope me-1" aria-hidden="true"></i>{{ __("Renvoyer les identifiants") }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('customers.portal.toggle-status', $customer) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $isActive ? 'btn-outline-danger' : 'btn-outline-success' }} resource-button"
                                            data-confirm="{{ $isActive ? 'Suspendre l\'accès au portail de ce client ?' : 'Réactiver l\'accès au portail de ce client ?' }}">
                                        <i class="bi bi-{{ $isActive ? 'slash-circle' : 'check-circle' }} me-1" aria-hidden="true"></i>
                                        {{ $isActive ? 'Suspendre l\'accès' : 'Réactiver l\'accès' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <p class="record-empty-copy">{{ __("Ce client n'a pas encore d'accès au portail client.") }}</p>
                        <div style="margin-top: 1rem;">
                            <form method="POST" action="{{ route('customers.portal.create-access', $customer) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-app-primary resource-button">
                                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>{{ __("Créer un accès portail") }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </section>
        @endcan

    </div>
</x-layouts.app>
