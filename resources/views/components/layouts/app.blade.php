@php
    $isPortalClient = auth()->user()?->hasRole('customer') && auth()->user()?->customer !== null;
    $overdueNavCount = (!$isPortalClient && auth()->user()?->can('subscriptions.view'))
        ? \App\Models\Installment::query()->where('status', 'overdue')->distinct('subscription_id')->count('subscription_id')
        : 0;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Back-office' }} · Cité Étoile du Monde</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="bg-slate-950 text-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 md:flex-row md:items-center md:justify-between">
            <a href="{{ $isPortalClient ? route('portal.dashboard') : route('dashboard') }}" class="font-bold">Cité Étoile du Monde</a>
            <nav class="flex flex-wrap items-center gap-4 text-sm">
                @if($isPortalClient)
                    <a href="{{ route('portal.dashboard') }}">Accueil</a>
                    <a href="{{ route('portal.subscriptions.index') }}">Mes souscriptions</a>
                    <a href="{{ route('portal.payments.index') }}">Mes paiements</a>
                    <a href="{{ route('portal.installments.index') }}">Mon échéancier</a>
                    <a href="{{ route('portal.receipts.index') }}">Mes reçus</a>
                    <a href="{{ route('portal.profile.edit') }}">Mon profil</a>
                @else
                    @can('dashboard.view')<a href="{{ route('dashboard') }}">Tableau de bord</a>@endcan
                    @can('customers.view')<a href="{{ route('customers.index') }}">Clients</a>@endcan
                    @can('plots.view')<a href="{{ route('plots.index') }}">Parcelles</a>@endcan
                    @can('subscriptions.view')
                        <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center gap-1.5">
                            Souscriptions
                            @if($overdueNavCount > 0)
                                <span class="rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white leading-none" title="{{ $overdueNavCount }} souscription(s) en retard">
                                    {{ $overdueNavCount }}
                                </span>
                            @endif
                        </a>
                    @endcan
                    @can('payments.view')<a href="{{ route('payments.index') }}">Paiements</a>@endcan

                    @can('payment_plans.view')<a href="{{ route('payment-plans.index') }}">Formules</a>@endcan
                    @can('reports.view')<a href="{{ route('reports.index') }}">Rapports</a>@endcan
                    @can('plots.manage')
                        <a href="{{ route('neighborhoods.index') }}">Quartiers</a>
                        <a href="{{ route('avenues.index') }}">Avenues</a>
                    @endcan
                    @can('audit_logs.view')<a href="{{ route('audit-logs.index') }}">Audit</a>@endcan
                    @can('users.manage')<a href="{{ route('users.index') }}">Utilisateurs</a>@endcan
                    @can('settings.manage')<a href="{{ route('settings.index') }}">Paramètres</a>@endcan
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Déconnexion</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-8">
        @if(session('status'))
            <p class="mb-5 rounded-lg bg-emerald-100 p-3 text-emerald-900">{{ session('status') }}</p>
        @endif
        {{ $slot }}
    </main>
</body>
</html>

