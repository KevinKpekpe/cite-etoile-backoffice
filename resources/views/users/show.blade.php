<x-layouts.app :title="$user->first_name.' '.$user->last_name">
    @php
        $roleName = $user->roles->where('name', '!=', 'customer')->first()?->name ?? '—';
        $initials = mb_strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1));
    @endphp
    <div class="customer-record">
        <header class="record-heading">
            <div class="record-heading__identity">
                <span class="record-heading__avatar" aria-hidden="true">{{ $initials }}</span>
                <div>
                    <p class="app-kicker">Fiche utilisateur</p>
                    <h1>{{ $user->first_name }} {{ $user->last_name }}</h1>
                    <p>{{ $user->email }} · {{ $user->phone ?: 'Sans téléphone' }}</p>
                </div>
            </div>
            <div class="resource-heading__actions">
                @if(! $user->trashed())
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-secondary resource-button">Modifier</a>
                    @if(!$user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
                        <form method="POST" action="{{ route('users.toggle-status', $user) }}">@csrf @method('PATCH')
                            <button class="btn {{ $user->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }} resource-button" type="submit">{{ $user->status === 'active' ? 'Suspendre' : 'Réactiver' }}</button>
                        </form>
                    @endif
                    @can('users.delete')
                        @if((! $user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin')) && $user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="Placer ce compte utilisateur en corbeille ?">@csrf @method('DELETE')
                                <button class="btn btn-outline-danger resource-button" type="submit">Supprimer</button>
                            </form>
                        @endif
                    @endcan
                @else
                    @can('users.delete')
                        @if(! $user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
                            <form method="POST" action="{{ route('users.restore', $user) }}">@csrf<button class="btn btn-outline-success resource-button" type="submit">Restaurer</button></form>
                        @endif
                    @endcan
                    @can('users.force_delete')
                        <form method="POST" action="{{ route('users.force-delete', $user) }}" data-confirm="Suppression définitive et irréversible de ce compte ?">@csrf @method('DELETE')
                            <button class="btn btn-danger resource-button" type="submit">Supprimer définitivement</button>
                        </form>
                    @endcan
                @endif
            </div>
        </header>

        @if($user->trashed())
            <div class="record-alert record-alert--danger"><div><strong>Compte placé en corbeille</strong><p>Supprimé le {{ $user->deleted_at->format('d/m/Y à H:i') }}.</p></div></div>
        @endif

        <x-user-credentials-markdown />

        <div class="detail-sheet">
            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Profil</p><h2>Informations du compte</h2></div>
                <dl class="detail-grid">
                    <div><dt>Statut</dt><dd><span class="status-badge status-badge--{{ $user->trashed() ? 'danger' : ($user->status === 'active' ? 'active' : 'suspended') }}">{{ $user->trashed() ? 'Supprimé' : ($user->status === 'active' ? 'Actif' : 'Suspendu') }}</span></dd></div>
                    <div><dt>Rôle principal</dt><dd>{{ ucfirst(str_replace('_', ' ', $roleName)) }}</dd></div>
                    <div><dt>Téléphone</dt><dd>{{ $user->phone ?? 'Non renseigné' }}</dd></div>
                    <div><dt>Authentification à deux facteurs</dt><dd>{{ $user->hasTwoFactorAuthenticationEnabled() ? 'Activée' : 'Désactivée' }}</dd></div>
                    <div><dt>Dernière connexion</dt><dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd></div>
                    <div><dt>Compte créé le</dt><dd>{{ $user->created_at->format('d/m/Y') }}</dd></div>
                </dl>
            </section>
            <section class="detail-section">
                <div class="detail-section__heading"><p class="app-kicker">Traçabilité</p><h2>Historique d’activité</h2></div>
                <div class="timeline-list">
                    @forelse($auditEntries as $entry)
                        <div class="timeline-list__item"><span class="timeline-list__marker"></span><span><strong>{{ ucfirst(str_replace(['.', '_'], ' ', $entry->action)) }}</strong><small>{{ $entry->created_at->format('d/m/Y H:i') }}</small></span></div>
                    @empty
                        <p class="record-empty-copy">Aucune modification enregistrée dans le journal.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
