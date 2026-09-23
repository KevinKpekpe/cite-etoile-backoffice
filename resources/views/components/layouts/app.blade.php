@php
    $user = auth()->user();
    $isPortalClient = $user?->hasRole('customer') && $user?->customer !== null;
    $overdueNavCount = (!$isPortalClient && $user?->can('subscriptions.view'))
        ? \App\Models\Installment::query()->where('status', 'overdue')->distinct('subscription_id')->count('subscription_id')
        : 0;
    $homeRoute = $isPortalClient ? route('portal.dashboard') : route('dashboard');
    $userName = trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')) ?: ($user?->email ?? 'Utilisateur');
    $userInitials = mb_strtoupper(mb_substr($user?->first_name ?? '', 0, 1).mb_substr($user?->last_name ?? '', 0, 1));
    $userInitials = $userInitials ?: mb_strtoupper(mb_substr($user?->email ?? 'U', 0, 1));
@endphp
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? 'Back-office' }} · Cité Étoile du Monde</title>
    <script>
        document.documentElement.dataset.bsTheme = localStorage.getItem('cite-etoile-theme') || 'light';
        document.documentElement.dataset.sidebar = localStorage.getItem('cite-etoile-sidebar') || 'expanded';
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell min-h-screen">
    <div class="app-sidebar-backdrop" data-sidebar-close></div>

    <aside class="app-sidebar" id="app-sidebar" aria-label="Navigation principale">
        <div class="app-sidebar__brand">
            <a href="{{ $homeRoute }}" class="app-brand" aria-label="Cité Étoile du Monde — Accueil">
                <span class="app-brand__mark" aria-hidden="true">CÉ</span>
                <span class="min-w-0">
                    <strong class="app-brand__name">Cité Étoile</strong>
                    <span class="app-brand__caption">du Monde</span>
                </span>
            </a>
            <button class="app-icon-button app-sidebar-close-button" type="button" data-sidebar-close aria-label="Fermer le menu">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="app-sidebar__nav">
            <p class="app-nav-label">Navigation</p>

            @if($isPortalClient)
                <a href="{{ route('portal.dashboard') }}" class="app-nav-link {{ request()->routeIs('portal.dashboard') ? 'is-active' : '' }}"><i class="bi bi-grid app-nav-link__marker" aria-hidden="true"></i><span>Vue d’ensemble</span></a>
                <a href="{{ route('portal.subscriptions.index') }}" class="app-nav-link {{ request()->routeIs('portal.subscriptions.*') ? 'is-active' : '' }}"><i class="bi bi-file-earmark-text app-nav-link__marker" aria-hidden="true"></i><span>Mes souscriptions</span></a>
                <a href="{{ route('portal.payments.index') }}" class="app-nav-link {{ request()->routeIs('portal.payments.*') ? 'is-active' : '' }}"><i class="bi bi-credit-card app-nav-link__marker" aria-hidden="true"></i><span>Mes paiements</span></a>
                <a href="{{ route('portal.installments.index') }}" class="app-nav-link {{ request()->routeIs('portal.installments.*') ? 'is-active' : '' }}"><i class="bi bi-calendar3 app-nav-link__marker" aria-hidden="true"></i><span>Mon échéancier</span></a>
                <a href="{{ route('portal.receipts.index') }}" class="app-nav-link {{ request()->routeIs('portal.receipts.*') ? 'is-active' : '' }}"><i class="bi bi-receipt app-nav-link__marker" aria-hidden="true"></i><span>Mes reçus</span></a>
                <a href="{{ route('portal.profile.edit') }}" class="app-nav-link {{ request()->routeIs('portal.profile.*') ? 'is-active' : '' }}"><i class="bi bi-person app-nav-link__marker" aria-hidden="true"></i><span>Mon profil</span></a>
            @else
                @can('dashboard.view')
                    <a href="{{ route('dashboard') }}" class="app-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"><i class="bi bi-grid app-nav-link__marker" aria-hidden="true"></i><span>Tableau de bord</span></a>
                @endcan
                @can('customers.view')
                    <a href="{{ route('customers.index') }}" class="app-nav-link {{ request()->routeIs('customers.*') ? 'is-active' : '' }}"><i class="bi bi-people app-nav-link__marker" aria-hidden="true"></i><span>Clients</span></a>
                @endcan
                @can('plots.view')
                    <a href="{{ route('plots.index') }}" class="app-nav-link {{ request()->routeIs('plots.*') ? 'is-active' : '' }}"><i class="bi bi-map app-nav-link__marker" aria-hidden="true"></i><span>Parcelles</span></a>
                @endcan
                @can('subscriptions.view')
                    <a href="{{ route('subscriptions.index') }}" class="app-nav-link {{ request()->routeIs('subscriptions.*') ? 'is-active' : '' }}">
                        <i class="bi bi-file-earmark-check app-nav-link__marker" aria-hidden="true"></i><span>Souscriptions</span>
                        @if($overdueNavCount > 0)
                            <span class="app-nav-badge" title="{{ $overdueNavCount }} souscription(s) en retard">{{ $overdueNavCount }}</span>
                        @endif
                    </a>
                @endcan
                @can('payments.view')
                    <a href="{{ route('payments.index') }}" class="app-nav-link {{ request()->routeIs('payments.*', 'receipts.*') ? 'is-active' : '' }}"><i class="bi bi-credit-card app-nav-link__marker" aria-hidden="true"></i><span>Paiements</span></a>
                @endcan

                <p class="app-nav-label app-nav-label--spaced">Gestion</p>

                @can('payment_plans.view')
                    <a href="{{ route('payment-plans.index') }}" class="app-nav-link {{ request()->routeIs('payment-plans.*') ? 'is-active' : '' }}"><i class="bi bi-wallet2 app-nav-link__marker" aria-hidden="true"></i><span>Formules</span></a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('reports.index') }}" class="app-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}"><i class="bi bi-bar-chart app-nav-link__marker" aria-hidden="true"></i><span>Rapports</span></a>
                @endcan
                @can('plots.manage')
                    <a href="{{ route('neighborhoods.index') }}" class="app-nav-link {{ request()->routeIs('neighborhoods.*') ? 'is-active' : '' }}"><i class="bi bi-buildings app-nav-link__marker" aria-hidden="true"></i><span>Quartiers</span></a>
                    <a href="{{ route('avenues.index') }}" class="app-nav-link {{ request()->routeIs('avenues.*') ? 'is-active' : '' }}"><i class="bi bi-signpost-2 app-nav-link__marker" aria-hidden="true"></i><span>Avenues</span></a>
                @endcan
                @can('audit_logs.view')
                    <a href="{{ route('audit-logs.index') }}" class="app-nav-link {{ request()->routeIs('audit-logs.*') ? 'is-active' : '' }}"><i class="bi bi-clock-history app-nav-link__marker" aria-hidden="true"></i><span>Journal d’audit</span></a>
                @endcan
                @can('users.manage')
                    <a href="{{ route('users.index') }}" class="app-nav-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}"><i class="bi bi-person-gear app-nav-link__marker" aria-hidden="true"></i><span>Utilisateurs</span></a>
                @endcan
                @can('settings.manage')
                    <a href="{{ route('settings.index') }}" class="app-nav-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}"><i class="bi bi-gear app-nav-link__marker" aria-hidden="true"></i><span>Paramètres</span></a>
                @endcan
            @endif
        </nav>

        <div class="app-sidebar__footer">
            <div class="app-sidebar-user">
                <span class="app-sidebar-user__avatar" aria-hidden="true">{{ $userInitials }}<span></span></span>
                <span class="app-sidebar-user__identity">
                    <strong>{{ $userName }}</strong>
                    <small>{{ $isPortalClient ? 'Client' : 'Administration' }}</small>
                </span>
            </div>
        </div>
    </aside>

    <div class="app-workspace">
        <header class="app-topbar">
            <div class="flex min-w-0 items-center gap-3">
                <button class="app-icon-button app-sidebar-open-button" type="button" data-sidebar-open aria-controls="app-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <button class="app-icon-button app-sidebar-collapse-button" type="button" data-sidebar-collapse aria-controls="app-sidebar" aria-label="Réduire le menu latéral">
                    <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                </button>
                <nav class="app-breadcrumb" aria-label="Fil d’Ariane">
                    <a href="{{ $homeRoute }}">Accueil</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span aria-current="page">{{ $title ?? 'Back-office' }}</span>
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <button class="app-theme-toggle" type="button" data-theme-toggle aria-label="Activer le mode sombre" title="Changer de thème">
                    <i class="bi bi-moon-stars" aria-hidden="true"></i><span class="visually-hidden" data-theme-label>Mode sombre</span>
                </button>

                <div class="dropdown">
                    <button class="app-user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="app-user-menu__avatar" aria-hidden="true">{{ $userInitials }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end app-user-dropdown">
                        @if($isPortalClient)
                            <a class="dropdown-item" href="{{ route('portal.profile.edit') }}">Mon profil</a>
                        @else
                            <a class="dropdown-item" href="{{ route('two-factor.setup') }}">Sécurité du compte</a>
                        @endif
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">Déconnexion</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-main">
            @if(session('status'))
                <div class="alert alert-success app-alert" role="status">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
