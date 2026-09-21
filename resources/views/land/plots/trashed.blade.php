<x-layouts.app title="Corbeille — Parcelles supprimées">
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold">🗑 Corbeille Parcelles</h1>
        <p class="text-slate-600">Parcelles en corbeille (soft delete). Elles peuvent être restaurées ou supprimées définitivement.</p>
    </div>
    <a href="{{ route('plots.index') }}" class="rounded-lg border bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
        ← Retour aux parcelles
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
                    <th class="p-4">Référence</th>
                    <th class="p-4">Quartier / Avenue</th>
                    <th class="p-4">Superficie</th>
                    <th class="p-4">Prix de base</th>
                    <th class="p-4">Supprimée le</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($plots as $plot)
                <tr class="hover:bg-red-50/50">
                    <td class="p-4 font-mono font-bold text-slate-800">
                        <a href="{{ route('plots.show', $plot) }}" class="text-slate-700 hover:underline">
                            {{ $plot->reference }}
                        </a>
                    </td>
                    <td class="p-4 text-slate-600">
                        {{ $plot->avenue?->neighborhood?->name }} / {{ $plot->avenue?->name }}
                    </td>
                    <td class="p-4 text-slate-600">{{ number_format($plot->surface_area, 2) }} m²</td>
                    <td class="p-4 font-semibold text-slate-900">${{ number_format($plot->base_price, 2) }}</td>
                    <td class="p-4 text-red-700">{{ $plot->deleted_at->format('d/m/Y H:i') }}</td>
                    <td class="p-4">
                        <div class="flex gap-2">
                            {{-- Restaurer --}}
                            @can('plots.restore')
                            <form method="POST" action="{{ route('plots.restore', $plot) }}">
                                @csrf
                                <button class="rounded-lg bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-200 transition">
                                    ♻ Restaurer
                                </button>
                            </form>
                            @endcan

                            {{-- Supprimer définitivement (super_admin uniquement) --}}
                            @can('plots.force_delete')
                            <form method="POST" action="{{ route('plots.force-delete', $plot) }}"
                                  onsubmit="return confirm('SUPPRESSION DÉFINITIVE de la parcelle {{ $plot->reference }}. Irréversible !')">
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
                        ✅ La corbeille parcelles est vide.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-5">{{ $plots->links() }}</div>
</x-layouts.app>
