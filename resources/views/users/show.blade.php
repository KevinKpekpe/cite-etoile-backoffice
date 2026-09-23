<x-layouts.app :title="$user->first_name.' '.$user->last_name">
    <div class="resource-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Fiche utilisateur</p>
                <h1 class="resource-heading__title">{{ $user->first_name }} {{ $user->last_name }}</h1>
                <p class="resource-heading__description">{{ $user->email }} · {{ $user->phone ?: 'Sans téléphone' }}</p>
                @if($user->trashed())
                    <span class="status-badge status-badge--danger mt-2">
                        Compte en corbeille depuis le {{ $user->deleted_at->format('d/m/Y H:i') }}
                    </span>
                @endif
            </div>

            <div class="resource-heading__actions">
                @if(! $user->trashed())
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary resource-button">
                        <i class="bi bi-pencil me-1"></i> Modifier
                    </a>
                    @if(!$user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
                        <form method="POST" action="{{ route('users.toggle-status', $user) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button class="btn {{ $user->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }} resource-button" type="submit">
                                {{ $user->status === 'active' ? 'Suspendre le compte' : 'Réactiver le compte' }}
                            </button>
                        </form>
                    @endif
                    @can('users.delete')
                        @if((! $user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin')) && $user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                                  onsubmit="return confirm('Supprimer {{ $user->first_name }} {{ $user->last_name }} ? Le compte sera placé en corbeille.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger resource-button" type="submit">
                                    <i class="bi bi-trash me-1"></i> Supprimer
                                </button>
                            </form>
                        @endif
                    @endcan
                @else
                    {{-- Compte en corbeille --}}
                    @can('users.delete')
                        @if(! $user->hasRole('super_admin') || auth()->user()?->hasRole('super_admin'))
                            <form method="POST" action="{{ route('users.restore', $user) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-outline-success resource-button" type="submit">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurer
                                </button>
                            </form>
                        @endif
                    @endcan
                    @can('users.force_delete')
                        <form method="POST" action="{{ route('users.force-delete', $user) }}" class="d-inline"
                              onsubmit="return confirm('SUPPRESSION DÉFINITIVE — action irréversible. Continuer ?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger resource-button" type="submit">
                                <i class="bi bi-x-circle me-1"></i> Supprimer définitivement
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </header>

        <x-user-credentials-markdown />

        <div class="row g-4 mt-2">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                    <h2 class="h5 font-bold mb-3 text-dark border-bottom pb-2">Informations profil</h2>
                    <dl class="row g-2 mb-0 text-sm">
                        <dt class="col-sm-5 text-secondary">Statut</dt>
                        <dd class="col-sm-7 mb-2">
                            <span class="status-badge status-badge--{{ $user->trashed() ? 'danger' : ($user->status === 'active' ? 'active' : 'suspended') }}">
                                {{ $user->trashed() ? 'Supprimé' : ($user->status === 'active' ? 'Actif' : 'Suspendu') }}
                            </span>
                        </dd>

                        <dt class="col-sm-5 text-secondary">Rôle principal</dt>
                        <dd class="col-sm-7 mb-2 font-bold">{{ $user->roles->where('name', '!=', 'customer')->first()?->name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-secondary">Téléphone</dt>
                        <dd class="col-sm-7 mb-2">{{ $user->phone ?? '—' }}</dd>

                        <dt class="col-sm-5 text-secondary">Sécurité 2FA</dt>
                        <dd class="col-sm-7 mb-2">{{ $user->hasTwoFactorAuthenticationEnabled() ? 'Activée' : 'Désactivée' }}</dd>

                        <dt class="col-sm-5 text-secondary">Dernière connexion</dt>
                        <dd class="col-sm-7 mb-2">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd>

                        <dt class="col-sm-5 text-secondary">Date de création</dt>
                        <dd class="col-sm-7 mb-0">{{ $user->created_at->format('d/m/Y') }}</dd>

                        @if($user->trashed())
                            <dt class="col-sm-5 text-secondary">Date suppression</dt>
                            <dd class="col-sm-7 mb-0 text-danger font-semibold">{{ $user->deleted_at->format('d/m/Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-3 p-4 bg-white">
                    <h2 class="h5 font-bold mb-3 text-dark border-bottom pb-2">Historique d’activité & modifications</h2>
                    @forelse($auditEntries as $entry)
                        <div class="border-bottom py-2.5 text-sm last:border-bottom-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-dark">{{ $entry->action }}</strong>
                                <small class="text-muted">{{ $entry->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="resource-empty py-3">
                            <span>Aucune modification enregistrée dans le journal.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
