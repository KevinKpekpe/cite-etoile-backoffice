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
            <button class="app-icon-button lg:hidden" type="button" data-sidebar-close aria-label="Fermer le menu">
                <span aria-hidden="true">×</span>
            </button>
        </div>

        <nav class="app-sidebar__nav">
            <p class="app-nav-label">Navigation</p>

            @if($isPortalClient)
                <a href="{{ route('portal.dashboard') }}" class="app-nav-link {{ request()->routeIs('portal.dashboard') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">01</span><span>Vue d’ensemble</span></a>
                <a href="{{ route('portal.subscriptions.index') }}" class="app-nav-link {{ request()->routeIs('portal.subscriptions.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">02</span><span>Mes souscriptions</span></a>
                <a href="{{ route('portal.payments.index') }}" class="app-nav-link {{ request()->routeIs('portal.payments.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">03</span><span>Mes paiements</span></a>
                <a href="{{ route('portal.installments.index') }}" class="app-nav-link {{ request()->routeIs('portal.installments.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">04</span><span>Mon échéancier</span></a>
                <a href="{{ route('portal.receipts.index') }}" class="app-nav-link {{ request()->routeIs('portal.receipts.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">05</span><span>Mes reçus</span></a>
                <a href="{{ route('portal.profile.edit') }}" class="app-nav-link {{ request()->routeIs('portal.profile.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">06</span><span>Mon profil</span></a>
            @else
                @can('dashboard.view')
                    <a href="{{ route('dashboard') }}" class="app-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">01</span><span>Tableau de bord</span></a>
                @endcan
                @can('customers.view')
                    <a href="{{ route('customers.index') }}" class="app-nav-link {{ request()->routeIs('customers.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">02</span><span>Clients</span></a>
                @endcan
                @can('plots.view')
                    <a href="{{ route('plots.index') }}" class="app-nav-link {{ request()->routeIs('plots.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">03</span><span>Parcelles</span></a>
                @endcan
                @can('subscriptions.view')
                    <a href="{{ route('subscriptions.index') }}" class="app-nav-link {{ request()->routeIs('subscriptions.*') ? 'is-active' : '' }}">
                        <span class="app-nav-link__marker" aria-hidden="true">04</span><span>Souscriptions</span>
                        @if($overdueNavCount > 0)
                            <span class="app-nav-badge" title="{{ $overdueNavCount }} souscription(s) en retard">{{ $overdueNavCount }}</span>
                        @endif
                    </a>
                @endcan
                @can('payments.view')
                    <a href="{{ route('payments.index') }}" class="app-nav-link {{ request()->routeIs('payments.*', 'receipts.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">05</span><span>Paiements</span></a>
                @endcan

                <p class="app-nav-label app-nav-label--spaced">Gestion</p>

                @can('payment_plans.view')
                    <a href="{{ route('payment-plans.index') }}" class="app-nav-link {{ request()->routeIs('payment-plans.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">06</span><span>Formules</span></a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('reports.index') }}" class="app-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">07</span><span>Rapports</span></a>
                @endcan
                @can('plots.manage')
                    <a href="{{ route('neighborhoods.index') }}" class="app-nav-link {{ request()->routeIs('neighborhoods.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">08</span><span>Quartiers</span></a>
                    <a href="{{ route('avenues.index') }}" class="app-nav-link {{ request()->routeIs('avenues.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">09</span><span>Avenues</span></a>
                @endcan
                @can('audit_logs.view')
                    <a href="{{ route('audit-logs.index') }}" class="app-nav-link {{ request()->routeIs('audit-logs.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">10</span><span>Journal d’audit</span></a>
                @endcan
                @can('users.manage')
                    <a href="{{ route('users.index') }}" class="app-nav-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">11</span><span>Utilisateurs</span></a>
                @endcan
                @can('settings.manage')
                    <a href="{{ route('settings.index') }}" class="app-nav-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}"><span class="app-nav-link__marker" aria-hidden="true">12</span><span>Paramètres</span></a>
                @endcan
            @endif
        </nav>

        <div class="app-sidebar__footer">
            <div class="app-sidebar__status"><span class="app-sidebar__status-dot" aria-hidden="true"></span><span>Système opérationnel</span></div>
        </div>
    </aside>

    <div class="app-workspace">
        <header class="app-topbar">
            <div class="flex min-w-0 items-center gap-3">
                <button class="app-icon-button lg:hidden" type="button" data-sidebar-open aria-controls="app-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="app-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
                <button class="app-icon-button hidden lg:inline-flex" type="button" data-sidebar-collapse aria-controls="app-sidebar" aria-label="Réduire le menu latéral">
                    <span class="app-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
                <div class="min-w-0">
                    <p class="app-topbar__context">{{ $isPortalClient ? 'Espace client' : 'Administration' }}</p>
                    <p class="app-topbar__title">{{ $title ?? 'Back-office' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <button class="app-theme-toggle" type="button" data-theme-toggle aria-label="Activer le mode sombre">
                    <span class="app-theme-toggle__indicator" aria-hidden="true"></span><span class="hidden sm:inline" data-theme-label>Mode sombre</span>
                </button>

                <div class="dropdown">
                    <button class="app-user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="app-user-menu__avatar" aria-hidden="true">{{ $userInitials }}</span>
                        <span class="hidden min-w-0 text-left md:block">
                            <strong class="app-user-menu__name">{{ $userName }}</strong><span class="app-user-menu__email">{{ $user?->email }}</span>
                        </span>
                        <span class="app-user-menu__chevron" aria-hidden="true">⌄</span>
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
