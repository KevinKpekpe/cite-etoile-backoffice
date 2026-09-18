<x-layouts.app title="Souscriptions">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Souscriptions</h1>
            <p class="text-sm text-slate-500">Gestion des contrats de réservation et suivi des paiements.</p>
        </div>
        @can('subscriptions.create')
            <a href="{{ route('subscriptions.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 font-semibold text-white transition hover:bg-amber-700 shadow-sm">
                <span>+</span> Nouvelle souscription
            </a>
        @endcan
    </div>

    {{-- Onglets de filtrage par statut --}}
    <div class="mb-6 flex flex-wrap gap-2 rounded-xl bg-slate-200/60 p-1.5 text-sm font-medium">
        <a href="{{ route('subscriptions.index', ['status' => 'all']) }}"
           class="rounded-lg px-4 py-2 transition {{ $statusFilter === 'all' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}">
            Toutes <span class="ml-1 text-xs opacity-75">({{ $counts['all'] }})</span>
        </a>
        <a href="{{ route('subscriptions.index', ['status' => 'overdue']) }}"
           class="flex items-center gap-1.5 rounded-lg px-4 py-2 transition {{ $statusFilter === 'overdue' ? 'bg-red-600 text-white font-bold shadow-sm' : 'text-red-700 hover:bg-red-100/50' }}">
            <span>⚠️</span> En retard
            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-800 {{ $statusFilter === 'overdue' ? 'bg-white text-red-700' : '' }}">
                {{ $counts['overdue'] }}
            </span>
        </a>
        <a href="{{ route('subscriptions.index', ['status' => 'active']) }}"
           class="rounded-lg px-4 py-2 transition {{ $statusFilter === 'active' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}">
            En cours <span class="ml-1 text-xs opacity-75">({{ $counts['active'] }})</span>
        </a>
        <a href="{{ route('subscriptions.index', ['status' => 'paid']) }}"
           class="rounded-lg px-4 py-2 transition {{ $statusFilter === 'paid' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}">
            Soldées <span class="ml-1 text-xs opacity-75">({{ $counts['paid'] }})</span>
        </a>
        <a href="{{ route('subscriptions.index', ['status' => 'cancelled']) }}"
           class="rounded-lg px-4 py-2 transition {{ $statusFilter === 'cancelled' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}">
            Clôturées <span class="ml-1 text-xs opacity-75">({{ $counts['cancelled'] }})</span>
        </a>
    </div>

    {{-- Table des souscriptions --}}
    <div class="overflow-x-auto rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                    <th class="py-3">Numéro</th>
                    <th>Client</th>
                    <th>Parcelle</th>
                    <th>Formule</th>
                    <th>Échéances / Retards</th>
                    <th>Statut</th>
                    <th>Solde restant</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($subscriptions as $item)
                    @php
                        $overdueInstallments = $item->installments->where('status', 'overdue');
                        $overdueCount = $overdueInstallments->count();
                        $overdueTotal = $overdueInstallments->sum('balance');
                        $isOverdue = $overdueCount > 0;
                        $canPay = $item->financial_status !== 'paid' && !in_array($item->commercial_status, ['completed', 'cancelled', 'terminated'], true);
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors {{ $isOverdue ? 'bg-red-50/40' : '' }}">
                        <td class="py-3.5">
                            <a class="font-mono font-bold text-amber-700 hover:underline" href="{{ route('subscriptions.show', $item) }}">
                                {{ $item->subscription_number }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('customers.show', $item->customer) }}" class="font-medium text-slate-900 hover:text-amber-600">
                                {{ $item->customer->first_name }} {{ $item->customer->last_name }}
                            </a>
                        </td>
                        <td>
                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                {{ $item->plot->reference }}
                            </span>
                        </td>
                        <td>{{ $item->paymentPlan->name }}</td>
                        <td>
                            @if($isOverdue)
                                <div class="inline-flex flex-col">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800">
                                        <span>⚠️</span> {{ $overdueCount }} retard(s)
                                    </span>
                                    <span class="text-[11px] font-semibold text-red-700 mt-0.5">
                                        {{ number_format((float) $overdueTotal, 2) }} USD impayés
                                    </span>
                                </div>
                            @elseif($item->financial_status === 'paid')
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                    ✓ Soldée
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600">
                                    À jour
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($item->commercial_status === 'active')
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">En cours</span>
                            @elseif($item->commercial_status === 'pending')
                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">En attente</span>
                            @elseif($item->commercial_status === 'completed')
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Terminée</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $item->commercial_status }}</span>
                            @endif
                        </td>
                        <td class="font-semibold text-slate-900">
                            {{ number_format((float) $item->balance, 2) }} USD
                        </td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('subscriptions.show', $item) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 transition">
                                    Détails
                                </a>
                                @if($canPay)
                                    @can('payments.create')
                                        <a href="{{ route('payments.create', ['subscription' => $item->id]) }}"
                                           class="rounded-lg {{ $isOverdue ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }} px-3 py-1.5 text-xs font-semibold text-white transition shadow-sm">
                                            Encaisser
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500">
                            Aucune souscription ne correspond aux critères sélectionnés.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $subscriptions->links() }}
    </div>
</x-layouts.app>
