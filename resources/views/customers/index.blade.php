<x-layouts.app title="Clients">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Clients</h1>
            <p class="text-sm text-slate-500">Recherche, dossiers souscripteurs et suivi des paiements.</p>
        </div>
        @can('customers.create')
            <a href="{{ route('customers.create') }}" class="rounded-xl bg-amber-600 px-4 py-2.5 font-semibold text-white transition hover:bg-amber-700 shadow-sm">
                + Nouveau client
            </a>
        @endcan
    </div>

    <form class="mb-6 grid gap-3 rounded-2xl bg-white p-4 shadow-sm border border-slate-100 md:grid-cols-[1fr_240px_auto]">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, téléphone ou N° client..." class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:outline-none">
        <select name="status" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:outline-none">
            <option value="">Tous les statuts</option>
            <option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>⚠️ En retard de paiement</option>
            @foreach(['prospect' => 'Prospect', 'active' => 'Actif', 'settled' => 'Soldé', 'suspended' => 'Suspendu', 'archived' => 'Archivé'] as $statusKey => $statusLabel)
                <option @selected(($filters['status'] ?? '') === $statusKey) value="{{ $statusKey }}">{{ $statusLabel }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
            Filtrer
        </button>
    </form>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="p-4">N° client</th>
                        <th class="p-4">Identité</th>
                        <th class="p-4">Téléphone</th>
                        <th class="p-4">Statut</th>
                        <th class="p-4">Retard impayé</th>
                        <th class="p-4">Responsable</th>
                        <th class="p-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        @php
                            $overdueCount = $customer->subscriptions->pluck('installments')->flatten()->where('status', 'overdue')->count();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $overdueCount > 0 ? 'bg-red-50/30' : '' }}">
                            <td class="p-4 font-mono font-bold">
                                <a class="text-amber-700 hover:underline" href="{{ route('customers.show', $customer) }}">
                                    {{ $customer->customer_number }}
                                </a>
                            </td>
                            <td class="p-4 font-semibold text-slate-900">
                                {{ $customer->first_name }} {{ $customer->last_name }}
                            </td>
                            <td class="p-4 text-slate-600">{{ $customer->phone }}</td>
                            <td class="p-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                    {{ ucfirst($customer->status) }}
                                </span>
                            </td>
                            <td class="p-4">
                                @if($overdueCount > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800">
                                        <span>⚠️</span> {{ $overdueCount }} impayé(s)
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-slate-600">
                                {{ $customer->assignedAgent ? $customer->assignedAgent->first_name.' '.$customer->assignedAgent->last_name : '—' }}
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('customers.show', $customer) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 transition">
                                    Voir dossier
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                Aucun client trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">
        {{ $customers->links() }}
    </div>
</x-layouts.app>
