<x-layouts.app title="Corbeille — Clients supprimés">
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold">🗑 Corbeille Clients</h1>
        <p class="text-slate-600">Clients en corbeille (soft delete). Ils peuvent être restaurés ou supprimés définitivement.</p>
    </div>
    <a href="{{ route('customers.index') }}" class="rounded-lg border bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
        ← Retour aux clients
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
                    <th class="p-4">N° Client</th>
                    <th class="p-4">Nom & Prénom</th>
                    <th class="p-4">Téléphone / Email</th>
                    <th class="p-4">Agent</th>
                    <th class="p-4">Supprimé le</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($customers as $customer)
                <tr class="hover:bg-red-50/50">
                    <td class="p-4 font-mono font-bold text-slate-800">{{ $customer->customer_number }}</td>
                    <td class="p-4 font-medium">
                        <a href="{{ route('customers.show', $customer) }}" class="text-slate-700 hover:underline">
                            {{ $customer->first_name }} {{ $customer->last_name }}
                        </a>
                    </td>
                    <td class="p-4 text-slate-600">
                        <div>{{ $customer->phone }}</div>
                        <div class="text-xs text-slate-500">{{ $customer->email ?? '—' }}</div>
                    </td>
                    <td class="p-4 text-slate-600">{{ $customer->assignedAgent?->first_name ?? '—' }}</td>
                    <td class="p-4 text-red-700">{{ $customer->deleted_at->format('d/m/Y H:i') }}</td>
                    <td class="p-4">
                        <div class="flex gap-2">
                            {{-- Restaurer --}}
                            @can('customers.restore')
                            <form method="POST" action="{{ route('customers.restore', $customer) }}">
                                @csrf
                                <button class="rounded-lg bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-200 transition">
                                    ♻ Restaurer
                                </button>
                            </form>
                            @endcan

                            {{-- Supprimer définitivement (super_admin uniquement) --}}
                            @can('customers.force_delete')
                            <form method="POST" action="{{ route('customers.force-delete', $customer) }}"
                                  onsubmit="return confirm('SUPPRESSION DÉFINITIVE du client {{ $customer->first_name }} {{ $customer->last_name }}. Irréversible !')">
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
                        ✅ La corbeille clients est vide.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-5">{{ $customers->links() }}</div>
</x-layouts.app>
