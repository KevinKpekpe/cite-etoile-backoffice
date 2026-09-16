<x-layouts.app title="Rapports">
<div class="flex flex-col gap-6">
    <div><h1 class="text-3xl font-bold">Rapports et exports</h1><p class="text-slate-600">Pilotage commercial et financier selon les filtres sélectionnés.</p></div>
    <form class="grid gap-3 rounded-xl bg-white p-5 shadow-sm md:grid-cols-3 xl:grid-cols-6">
        <label class="text-sm">Du<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
        <label class="text-sm">Au<input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
        <label class="text-sm">Statut client<select name="customer_status" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Tous</option>@foreach(['prospect','active','settled','suspended','archived'] as $status)<option @selected(($filters['customer_status'] ?? '')===$status)>{{ $status }}</option>@endforeach</select></label>
        <label class="text-sm">Statut parcelle<select name="plot_status" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Tous</option>@foreach(['available','reserved','subscribed','blocked','unavailable'] as $status)<option @selected(($filters['plot_status'] ?? '')===$status)>{{ $status }}</option>@endforeach</select></label>
        <label class="text-sm">Formule<select name="payment_plan_id" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Toutes</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected((string)($filters['payment_plan_id'] ?? '')===(string)$plan->id)>{{ $plan->name }}</option>@endforeach</select></label>
        <label class="text-sm">Agent<select name="agent_id" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Tous</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected((string)($filters['agent_id'] ?? '')===(string)$agent->id)>{{ $agent->first_name }} {{ $agent->last_name }}</option>@endforeach</select></label>
        <div class="xl:col-span-6"><button class="rounded-lg bg-slate-950 px-5 py-2 text-white">Appliquer les filtres</button></div>
    </form>
    <section class="grid gap-4 md:grid-cols-4">
        @foreach([['Clients',$customers->count()],['Parcelles',$plots->count()],['Paiements',number_format($paymentTotal,2,',',' ').' USD'],['Impayés',number_format($overdueTotal,2,',',' ').' USD']] as [$label,$value])
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">{{ $label }}</p><p class="text-2xl font-bold">{{ $value }}</p></div>
        @endforeach
    </section>
    @foreach(['customers'=>'Rapport clients','plots'=>'Rapport parcelles','payments'=>'Rapport paiements','overdue'=>'Rapport impayés'] as $type=>$title)
        @php($rows = ${$type})
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-bold">{{ $title }}</h2><a href="{{ route('reports.export', ['report'=>$type,...$filters]) }}" class="rounded-lg border px-4 py-2 text-sm font-semibold">Exporter CSV</a></div>
            <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><tbody>
            @forelse($rows->take(10) as $row)
                <tr class="border-t"><td class="p-3">
                @if($type === 'customers')
                    {{ $row->customer_number }} · {{ $row->first_name }} {{ $row->last_name }} · {{ $row->status }} <a class="ml-2 text-amber-700" href="{{ route('reports.customers.statement', $row) }}">PDF</a>
                @elseif($type === 'plots')
                    {{ $row->reference }} · {{ $row->avenue->neighborhood->name }} · {{ $row->commercial_status }}
                @elseif($type === 'payments')
                    {{ $row->payment_reference }} · {{ $row->customer->first_name }} {{ $row->customer->last_name }} · {{ number_format((float) $row->amount, 2, ',', ' ') }} {{ $row->currency }}
                @else
                    {{ $row->subscription->customer->first_name }} {{ $row->subscription->customer->last_name }} · {{ $row->subscription->plot->reference }} · {{ $row->due_date->diffInDays(now()) }} jours · {{ number_format((float) $row->balance, 2, ',', ' ') }} USD
                @endif
                </td></tr>
            @empty
                <tr><td class="p-4 text-slate-500">Aucune donnée.</td></tr>
            @endforelse
            </tbody></table></div>
        </section>
    @endforeach
</div>
</x-layouts.app>
