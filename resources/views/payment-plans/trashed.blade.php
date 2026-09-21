<x-layouts.app title="Corbeille — Formules supprimées">
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold">🗑 Corbeille Formules de Paiement</h1>
        <p class="text-slate-600">Formules en corbeille (soft delete). Elles peuvent être restaurées ou supprimées définitivement.</p>
    </div>
    <a href="{{ route('payment-plans.index') }}" class="rounded-lg border bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
        ← Retour aux formules
    </a>
</div>

@if(session('status'))
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
    </div>
@endif

<div class="overflow-hidden rounded-xl bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-red-50 text-red-800">
                <tr>
                    <th class="p-4">Code / Nom</th>
                    <th class="p-4">Prix total</th>
                    <th class="p-4">Mensualité</th>
                    <th class="p-4">Durée</th>
                    <th class="p-4">Supprimée le</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($paymentPlans as $plan)
                <tr class="hover:bg-red-50/50">
                    <td class="p-4">
                        <div class="font-bold text-slate-800">{{ $plan->name }}</div>
                        <div class="font-mono text-xs text-slate-500">{{ $plan->code }}</div>
                    </td>
                    <td class="p-4 font-semibold text-slate-900">${{ number_format($plan->total_price, 2) }}</td>
                    <td class="p-4 text-slate-600">${{ number_format($plan->monthly_amount, 2) }} / mois</td>
                    <td class="p-4 text-slate-600">{{ $plan->duration_months }} mois</td>
                    <td class="p-4 text-red-700">{{ $plan->deleted_at->format('d/m/Y H:i') }}</td>
                    <td class="p-4">
                        <div class="flex gap-2">
                            {{-- Restaurer --}}
                            @can('payment_plans.restore')
                            <form method="POST" action="{{ route('payment-plans.restore', $plan) }}">
                                @csrf
                                <button class="rounded-lg bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-200 transition">
                                    ♻ Restaurer
                                </button>
                            </form>
                            @endcan

                            {{-- Supprimer définitivement (super_admin uniquement) --}}
                            @can('payment_plans.force_delete')
                            <form method="POST" action="{{ route('payment-plans.force-delete', $plan) }}"
                                  onsubmit="return confirm('SUPPRESSION DÉFINITIVE de la formule {{ $plan->name }}. Irréversible !')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 hover:bg-red-200 transition">
                                    ☠ Définitif
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-8 text-center text-slate-500">
                        ✅ La corbeille formules est vide.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.app>
