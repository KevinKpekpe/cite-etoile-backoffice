@php
    $user = auth()->user();
    $isPortalClient = $user?->hasRole('customer') && $user?->customer !== null;
    $overdueNavCount = (!$isPortalClient && $user?->can('subscriptions.view'))
        ? \App\Models\Installment::query()->where('status', 'overdue')->distinct('subscription_id')->count('subscription_id')
        : 0;
    $homeRoute = $isPortalClient ? route('portal.dashboard') : route('dashboard');
    $userName = trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')) ?: ($user?->email ?? __('ui.account_user'));
    $userInitials = mb_strtoupper(mb_substr($user?->first_name ?? '', 0, 1).mb_substr($user?->last_name ?? '', 0, 1));
    $userInitials = $userInitials ?: mb_strtoupper(mb_substr($user?->email ?? 'U', 0, 1));
    $flashType = session('error') ? 'danger' : (session('warning') ? 'warning' : 'success');
    $flashMessage = session('error') ?? session('warning') ?? session('success') ?? session('status');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ __($title ?? 'Back-office') }} · Cité Étoile du Monde</title>
    <script>
        document.documentElement.dataset.bsTheme = localStorage.getItem('cite-etoile-theme') || 'light';
        document.documentElement.dataset.theme = document.documentElement.dataset.bsTheme;
        document.documentElement.dataset.sidebar = localStorage.getItem('cite-etoile-sidebar') || 'expanded';
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell min-h-screen" data-ui-locale="{{ app()->getLocale() }}" data-ui-copy="{{ json_encode(['expandSidebar' => __('Déployer le menu latéral'), 'collapseSidebar' => __('Réduire le menu latéral'), 'enableLightMode' => __('Activer le mode clair'), 'enableDarkMode' => __('Activer le mode sombre'), 'lightMode' => __('Mode clair'), 'darkMode' => __('Mode sombre'), 'confirmQuestion' => __('Confirmer cette opération ?'), 'confirmDelete' => __('Confirmer la suppression'), 'confirmAction' => __('Confirmer l’action'), 'copied' => __('Copié')], JSON_HEX_APOS | JSON_HEX_QUOT) }}">
    @if($flashMessage)
        <div class="app-toast app-toast--{{ $flashType }}" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast__icon" aria-hidden="true"><i class="bi bi-{{ $flashType === 'success' ? 'check-lg' : ($flashType === 'warning' ? 'exclamation-lg' : 'x-lg') }}"></i></span>
            <div class="app-toast__content">
                <strong>{{ $flashType === 'success' ? 'Opération réussie' : ($flashType === 'warning' ? 'Attention' : 'Erreur') }}</strong>
                <p>{{ $flashMessage }}</p>
            </div>
            <button type="button" class="app-toast__close" data-toast-close aria-label="{{ __('ui.close_notification') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
    @endif
    <div class="app-sidebar-backdrop" data-sidebar-close></div>

    <aside class="app-sidebar" id="app-sidebar" aria-label="{{ __('ui.main_navigation') }}">
        <div class="app-sidebar__brand">
            <a href="{{ $homeRoute }}" class="app-brand" aria-label="{{ __('ui.brand_home') }}">
                <span class="app-brand__mark" aria-hidden="true">CÉ</span>
                <span class="min-w-0">
                    <strong class="app-brand__name">Cité Étoile</strong>
                    <span class="app-brand__caption">du Monde</span>
                </span>
            </a>
            <button class="app-icon-button app-sidebar-close-button" type="button" data-sidebar-close aria-label="{{ __('ui.close_menu') }}">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="app-sidebar__nav">
            <p class="app-nav-label">{{ __('ui.navigation') }}</p>

            @if($isPortalClient)
                <a href="{{ route('portal.dashboard') }}" class="app-nav-link {{ request()->routeIs('portal.dashboard') ? 'is-active' : '' }}"><i class="bi bi-grid app-nav-link__marker" aria-hidden="true"></i><span>{{ __('portal.overview') }}</span></a>
                <a href="{{ route('portal.subscriptions.index') }}" class="app-nav-link {{ request()->routeIs('portal.subscriptions.*') ? 'is-active' : '' }}"><i class="bi bi-file-earmark-text app-nav-link__marker" aria-hidden="true"></i><span>{{ __('portal.my_subscriptions') }}</span></a>
                <a href="{{ route('portal.payments.index') }}" class="app-nav-link {{ request()->routeIs('portal.payments.*') ? 'is-active' : '' }}"><i class="bi bi-credit-card app-nav-link__marker" aria-hidden="true"></i><span>{{ __('portal.my_payments') }}</span></a>
                <a href="{{ route('portal.installments.index') }}" class="app-nav-link {{ request()->routeIs('portal.installments.*') ? 'is-active' : '' }}"><i class="bi bi-calendar3 app-nav-link__marker" aria-hidden="true"></i><span>{{ __('portal.my_installments') }}</span></a>
                <a href="{{ route('portal.ancillary-fees.index') }}" class="app-nav-link {{ request()->routeIs('portal.ancillary-fees.*') ? 'is-active' : '' }}"><i class="bi bi-receipt-cutoff app-nav-link__marker" aria-hidden="true"></i><span>{{ __('portal.my_ancillary_fees') }}</span></a>
                <a href="{{ route('portal.receipts.index') }}" class="app-nav-link {{ request()->routeIs('portal.receipts.*') ? 'is-active' : '' }}"><i class="bi bi-receipt app-nav-link__marker" aria-hidden="true"></i><span>{{ __('portal.my_receipts') }}</span></a>
                <a href="{{ route('portal.profile.edit') }}" class="app-nav-link {{ request()->routeIs('portal.profile.*') ? 'is-active' : '' }}"><i class="bi bi-person app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.my_profile') }}</span></a>
                <a href="{{ route('portal.settings.index') }}" class="app-nav-link {{ request()->routeIs('portal.settings.*') ? 'is-active' : '' }}"><i class="bi bi-gear app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.settings') }}</span></a>
            @else
                @can('dashboard.view')
                    <a href="{{ route('dashboard') }}" class="app-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"><i class="bi bi-grid app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.dashboard') }}</span></a>
                @endcan
                @can('customers.view')
                    <a href="{{ route('customers.index') }}" class="app-nav-link {{ request()->routeIs('customers.*') ? 'is-active' : '' }}"><i class="bi bi-people app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.customers') }}</span></a>
                @endcan
                @can('plots.view')
                    <a href="{{ route('plots.index') }}" class="app-nav-link {{ request()->routeIs('plots.*') ? 'is-active' : '' }}"><i class="bi bi-map app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.plots') }}</span></a>
                @endcan
                @can('subscriptions.view')
                    <a href="{{ route('subscriptions.index') }}" class="app-nav-link {{ request()->routeIs('subscriptions.*') ? 'is-active' : '' }}">
                        <i class="bi bi-file-earmark-check app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.subscriptions') }}</span>
                        @if($overdueNavCount > 0)
                            <span class="app-nav-badge" title="{{ __('ui.overdue_subscriptions', ['count' => $overdueNavCount]) }}">{{ $overdueNavCount }}</span>
                        @endif
                    </a>
                @endcan
                @can('payments.view')
                    <a href="{{ route('payments.index') }}" class="app-nav-link {{ request()->routeIs('payments.*', 'receipts.*') ? 'is-active' : '' }}"><i class="bi bi-credit-card app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.payments') }}</span></a>
                    <a href="{{ route('ancillary-fee-types.index') }}" class="app-nav-link {{ request()->routeIs('ancillary-fee-types.*') ? 'is-active' : '' }}"><i class="bi bi-receipt-cutoff app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.ancillary_fee_types') }}</span></a>
                @endcan

                <p class="app-nav-label app-nav-label--spaced">{{ __('ui.management') }}</p>

                @can('payment_plans.view')
                    <a href="{{ route('payment-plans.index') }}" class="app-nav-link {{ request()->routeIs('payment-plans.*') ? 'is-active' : '' }}"><i class="bi bi-wallet2 app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.plans') }}</span></a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('reports.index') }}" class="app-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}"><i class="bi bi-bar-chart app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.reports') }}</span></a>
                @endcan
                @can('plots.manage')
                    <a href="{{ route('neighborhoods.index') }}" class="app-nav-link {{ request()->routeIs('neighborhoods.*') ? 'is-active' : '' }}"><i class="bi bi-buildings app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.neighborhoods') }}</span></a>
                    <a href="{{ route('avenues.index') }}" class="app-nav-link {{ request()->routeIs('avenues.*') ? 'is-active' : '' }}"><i class="bi bi-signpost-2 app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.avenues') }}</span></a>
                @endcan
                @can('audit_logs.view')
                    <a href="{{ route('audit-logs.index') }}" class="app-nav-link {{ request()->routeIs('audit-logs.*') ? 'is-active' : '' }}"><i class="bi bi-clock-history app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.audit_log') }}</span></a>
                @endcan
                @can('users.manage')
                    <a href="{{ route('users.index') }}" class="app-nav-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}"><i class="bi bi-person-gear app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.users') }}</span></a>
                @endcan
                @can('settings.manage')
                    <a href="{{ route('settings.index') }}" class="app-nav-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}"><i class="bi bi-gear app-nav-link__marker" aria-hidden="true"></i><span>{{ __('ui.settings') }}</span></a>
                @endcan
            @endif
        </nav>

        <div class="app-sidebar__footer">
            <div class="app-sidebar-user">
                <span class="app-sidebar-user__avatar" aria-hidden="true">
                    @if($user?->avatar_path)
                        <img src="{{ Storage::url($user->avatar_path) }}" alt="{{ $userName }}" class="w-full h-full object-cover rounded-full">
                    @else
                        {{ $userInitials }}
                    @endif
                    <span></span>
                </span>
                <span class="app-sidebar-user__identity">
                    <strong>{{ $userName }}</strong>
                    <small>{{ $isPortalClient ? __('ui.client_role') : __('ui.admin_role') }}</small>
                </span>
            </div>
        </div>
    </aside>

    <div class="app-workspace">
        <header class="app-topbar">
            <div class="flex min-w-0 items-center gap-3">
                <button class="app-icon-button app-sidebar-open-button" type="button" data-sidebar-open aria-controls="app-sidebar" aria-expanded="false" aria-label="{{ __('ui.open_menu') }}">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <button class="app-icon-button app-sidebar-collapse-button" type="button" data-sidebar-collapse aria-controls="app-sidebar" aria-label="{{ __('ui.collapse_sidebar') }}">
                    <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                </button>
                <nav class="app-breadcrumb" aria-label="{{ __('ui.breadcrumb') }}">
                    <a href="{{ $homeRoute }}">{{ __('ui.home') }}</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span aria-current="page">{{ __($title ?? 'Back-office') }}</span>
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <form method="POST" action="{{ route('locale.update') }}" aria-label="{{ __('ui.language') }}">
                    @csrf
                    <label class="visually-hidden" for="app-language">{{ __('ui.language') }}</label>
                    <select id="app-language" class="form-select form-select-sm" name="locale" data-auto-submit>
                        <option value="fr" @selected(app()->getLocale() === 'fr')>Français</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                    </select>
                </form>
                <button class="app-theme-toggle" type="button" data-theme-toggle aria-label="{{ __('ui.enable_dark_mode') }}" title="{{ __('ui.change_theme') }}">
                    <i class="bi bi-moon-stars" aria-hidden="true"></i><span class="visually-hidden" data-theme-label>{{ __('ui.dark_mode') }}</span>
                </button>

                <div class="dropdown">
                    <button class="app-user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="app-user-menu__avatar" aria-hidden="true">
                            @if($user?->avatar_path)
                                <img src="{{ Storage::url($user->avatar_path) }}" alt="{{ $userName }}" class="w-full h-full object-cover rounded-full">
                            @else
                                {{ $userInitials }}
                            @endif
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end app-user-dropdown">
                        @if($isPortalClient)
                            <a class="dropdown-item" href="{{ route('portal.profile.edit') }}">{{ __('ui.my_profile') }}</a>
                        @else
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('ui.my_profile') }}</a>
                            <a class="dropdown-item" href="{{ route('two-factor.setup') }}">{{ __('ui.account_security') }}</a>
                        @endif
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">{{ __('ui.log_out') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-main">
            {{ $slot }}
        </main>
    </div>
    <dialog class="confirm-modal" data-confirm-modal aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-message">
        <div class="confirm-modal__header">
            <span class="confirm-modal__icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <h2 id="confirm-modal-title" data-confirm-title>{{ __('ui.confirm_action') }}</h2>
                <p>{{ __('ui.confirmation_required') }}</p>
            </div>
            <button type="button" class="confirm-modal__close" data-confirm-cancel aria-label="{{ __('ui.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <p class="confirm-modal__message" id="confirm-modal-message" data-confirm-message></p>
        <div class="confirm-modal__actions">
            <button type="button" class="btn btn-outline" data-confirm-cancel>{{ __('ui.cancel') }}</button>
            <button type="button" class="btn btn-danger" data-confirm-accept>{{ __('ui.confirm') }}</button>
        </div>
    </dialog>
</body>
</html>
