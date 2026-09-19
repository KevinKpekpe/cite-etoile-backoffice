<x-layouts.app title="Paiements">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Encaissements</h1>
            <p class="text-sm text-slate-500">Sélectionnez ou recherchez un dossier pour enregistrer un versement.</p>
        </div>
        <a href="{{ route('subscriptions.index', ['status' => 'overdue']) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-100 transition shadow-sm">
            <span>⚠️</span> Voir les dossiers en retard ({{ $overdueCount }})
        </a>
    </div>

    @if($overdueCount > 0)
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-900 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h3 class="font-bold text-red-800">Paiements en retard nécessitant un encaissement</h3>
                    <p class="text-sm text-red-700">
                        Il y a actuellement <strong>{{ $overdueCount }} échéance(s) en retard</strong> pour un total impayé de
                        <strong>{{ number_format((float) $overdueTotal, 2) }} USD</strong>.
                    </p>
                </div>
            </div>
            <a href="{{ route('subscriptions.index', ['status' => 'overdue']) }}"
               class="rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-red-700 transition">
                Filtrer les souscriptions en retard
            </a>
        </div>
    @endif

    <form class="mb-6 flex gap-3 rounded-2xl bg-white p-4 shadow-sm border border-slate-100">
        <input name="search" value="{{ $search }}" placeholder="Rechercher par client, téléphone, n° souscription, contrat ou parcelle..." class="grow rounded-xl border border-slate-200 p-3 text-sm focus:border-amber-500 focus:outline-none">
        <button class="rounded-xl bg-slate-950 px-6 font-medium text-white transition hover:bg-slate-800">
            Rechercher
        </button>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                    <th class="py-3">Souscription</th>
                    <th>Client</th>
                    <th>Parcelle</th>
                    <th>Échéance / Retard</th>
                    <th>Contractuel</th>
                    <th>Payé</th>
                    <th>Solde restant</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($subscriptions as $subscription)
                    @php
                        $subOverdue = $subscription->installments->where('status', 'overdue');
                        $subOverdueCount = $subOverdue->count();
                        $subOverdueTotal = $subOverdue->sum('balance');
                        $canPay = $subscription->financial_status !== 'paid' && !in_array($subscription->commercial_status, ['completed', 'cancelled', 'terminated'], true);
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors {{ $subOverdueCount > 0 ? 'bg-red-50/40' : '' }}">
                        <td class="py-3.5 font-mono font-bold">
                            <a href="{{ route('subscriptions.show', $subscription) }}" class="text-amber-700 hover:underline">
                                {{ $subscription->subscription_number }}
                            </a>
                        </td>
                        <td class="font-semibold text-slate-900">
                            {{ $subscription->customer->first_name }} {{ $subscription->customer->last_name }}
                        </td>
                        <td>
                            <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                {{ $subscription->plot->reference }}
                            </span>
                        </td>
                        <td>
                            @if($subOverdueCount > 0)
                                <div class="inline-flex flex-col">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800">
                                        ⚠️ {{ $subOverdueCount }} retard(s)
                                    </span>
                                    <span class="text-[11px] font-semibold text-red-700 mt-0.5">
                                        {{ number_format((float) $subOverdueTotal, 2) }} USD
                                    </span>
                                </div>
                            @elseif($subscription->financial_status === 'paid')
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                                    ✓ Soldée
                                </span>
                            @else
                                <span class="text-xs text-slate-500">À jour</span>
                            @endif
                        </td>
                        <td class="text-slate-600">{{ number_format((float) $subscription->contract_total, 2) }} USD</td>
                        <td class="text-emerald-700 font-medium">{{ number_format((float) $subscription->amount_paid, 2) }} USD</td>
                        <td class="font-bold text-slate-900">{{ number_format((float) $subscription->balance, 2) }} USD</td>
                        <td class="text-right">
                            @if($canPay)
                                @can('payments.create')
                                    <a href="{{ route('payments.create', $subscription) }}"
                                       class="rounded-xl {{ $subOverdueCount > 0 ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }} px-4 py-2 text-xs font-bold text-white transition shadow-sm">
                                        Encaisser
                                    </a>
                                @endcan
                            @else
                                <span class="text-xs text-slate-400">Paiement clôturé</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500">
                            Aucun dossier trouvé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $subscriptions->links() }}
    </div>

    {{-- Section Historique des reçus / factures téléchargeables --}}
    <div class="mt-10">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">🧾 Historique des versements & Reçus PDF</h2>
                <p class="text-sm text-slate-500">Téléchargez directement les reçus / factures de tous les versements déjà enregistrés.</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="py-3">Réf. Paiement</th>
                        <th>Client</th>
                        <th>Parcelle</th>
                        <th>Date & Heure</th>
                        <th>Mode</th>
                        <th>Montant</th>
                        <th class="text-right">Reçu / Facture PDF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentPayments as $payment)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 font-mono font-bold">
                                <a href="{{ route('payments.show', $payment) }}" class="text-amber-800 hover:underline">
                                    {{ $payment->payment_reference }}
                                </a>
                            </td>
                            <td class="font-semibold text-slate-900">
                                {{ $payment->customer->first_name }} {{ $payment->customer->last_name }}
                            </td>
                            <td>
                                <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                    {{ $payment->subscription->plot->reference }}
                                </span>
                            </td>
                            <td class="text-slate-600">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                            <td class="text-slate-600">{{ ucfirst($payment->payment_method) }}</td>
                            <td class="font-bold text-slate-900">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                            <td class="text-right">
                                @if($payment->receipt)
                                    @can('receipts.download')
                                        <a href="{{ route('receipts.download', $payment->receipt) }}"
                                           class="inline-flex items-center gap-1.5 rounded-xl bg-slate-950 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition">
                                            📥 Télécharger PDF ({{ $payment->receipt->receipt_number }})
                                        </a>
                                    @endcan
                                @else
                                    <span class="text-xs text-slate-400">Pas de reçu</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-500">
                                Aucun versement enregistré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
