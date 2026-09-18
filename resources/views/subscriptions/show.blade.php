<x-layouts.app :title="$subscription->subscription_number">

{{-- Bandeau : en attente de premier versement --}}
@if($subscription->commercial_status === 'pending')
<div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-bold text-amber-900">⏳ En attente du premier versement</p>
            <p class="mt-1 text-sm text-amber-800">La parcelle est réservée. La souscription sera activée automatiquement dès réception de l'acompte.</p>
        </div>
        @can('payments.create')
        <a href="{{ route('payments.create', $subscription) }}" class="rounded-lg bg-amber-600 px-5 py-2.5 font-semibold text-white">
            Encaisser l'acompte
        </a>
        @endcan
    </div>
</div>
@endif

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="font-mono text-amber-700">{{ $subscription->subscription_number }}</p>
        <h1 class="text-3xl font-bold">{{ $subscription->customer->first_name }} {{ $subscription->customer->last_name }}</h1>
        <p>{{ $subscription->plot->reference }} · {{ $subscription->plot->avenue->neighborhood->name }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($subscription->commercial_status === 'active')
        @can('payments.create')
        <a href="{{ route('payments.create', $subscription) }}" class="rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white">
            Encaisser un paiement
        </a>
        @endcan
        @endif
        @can('customers.view')
        <a href="{{ route('customers.show', $subscription->customer) }}" class="rounded-lg border bg-white px-4 py-2">
            Fiche client
        </a>
        @endcan
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-3">

    {{-- Conditions figées --}}
    <section class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="font-bold">Conditions figées</h2>
        <dl class="mt-4 grid gap-2 text-sm">
            <div>Formule : <strong>{{ $subscription->paymentPlan->name }}</strong></div>
            <div>Total : <strong>{{ $subscription->contract_total }} USD</strong></div>
            <div>Mensualité : <strong>{{ $subscription->monthly_amount ?? '—' }} {{ $subscription->monthly_amount ? 'USD' : '' }}</strong></div>
            <div>Durée : <strong>{{ $subscription->duration_months > 0 ? $subscription->duration_months.' mois' : 'Comptant' }}</strong></div>
            <div class="border-t pt-2">Payé : <strong>{{ $subscription->amount_paid }} USD</strong></div>
            <div>Solde : <strong class="{{ (float)$subscription->balance > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $subscription->balance }} USD</strong></div>
        </dl>
    </section>

    {{-- Statuts --}}
    <section class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="font-bold">Statuts</h2>
        <dl class="mt-3 grid gap-3 text-sm">
            <div>
                <dt class="text-slate-500">Commercial</dt>
                <dd><span class="rounded-full px-2 py-0.5 text-xs font-semibold
                    {{ match($subscription->commercial_status) {
                        'active'    => 'bg-emerald-100 text-emerald-800',
                        'pending'   => 'bg-amber-100 text-amber-800',
                        'completed' => 'bg-blue-100 text-blue-800',
                        'suspended','cancelled','terminated' => 'bg-red-100 text-red-800',
                        default     => 'bg-slate-100 text-slate-700',
                    } }}">
                    {{ ucfirst($subscription->commercial_status) }}
                </span></dd>
            </div>
            <div><dt class="text-slate-500">Financier</dt><dd>{{ $subscription->financial_status }}</dd></div>
            <div><dt class="text-slate-500">Administratif</dt><dd>{{ $subscription->administrative_status }}</dd></div>
        </dl>

        {{-- Changement de statut : réservé aux admins --}}
        @if(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('super_admin'))
        @can('subscriptions.update')
        <details class="mt-4">
            <summary class="cursor-pointer text-xs text-slate-500">Forcer le statut (admin)</summary>
            <form method="POST" action="{{ route('subscriptions.status', $subscription) }}" class="mt-3 flex gap-2">
                @csrf @method('PATCH')
                <select name="commercial_status" class="min-w-0 flex-1 rounded-lg border p-2 text-sm">
                    @foreach(['pending','active','suspended','cancelled','terminated','completed'] as $status)
                        <option @selected($subscription->commercial_status === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-slate-950 px-3 text-sm text-white">OK</button>
            </form>
        </details>
        @endcan
        @endif
    </section>

    {{-- Dossier contrat --}}
    <section class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="font-bold">Dossier contrat</h2>
        @if($subscription->contract)
            <p class="mt-3 font-mono text-sm">{{ $subscription->contract->contract_number }}</p>
            <p class="text-sm">{{ $subscription->contract->status }}</p>
            @if($subscription->contract->document_path)
                @can('documents.download')
                <a href="{{ route('subscriptions.contract.download', [$subscription, $subscription->contract]) }}" class="mt-2 inline-block text-sm text-amber-700">Télécharger le contrat</a>
                @endcan
            @endif
        @else
            <p class="mt-3 text-sm text-slate-500">Aucun contrat attaché.</p>
        @endif

        @can('subscriptions.update')
        <form method="POST" enctype="multipart/form-data" action="{{ route('subscriptions.contract.store', $subscription) }}" class="mt-4 grid gap-3">
            @csrf
            <input type="date" name="signed_at" value="{{ $subscription->contract?->signed_at?->format('Y-m-d') }}" class="rounded-lg border p-2 text-sm">
            <select name="status" class="rounded-lg border p-2 text-sm">
                @foreach(['draft','signed','cancelled','archived'] as $status)
                    <option @selected($subscription->contract?->status === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <input type="file" name="document" accept=".pdf" class="text-sm">
            <button class="rounded-lg bg-slate-950 p-2 text-sm text-white">Enregistrer le contrat</button>
        </form>
        @endcan
    </section>
</div>

{{-- Compteurs --}}
<div class="mt-5 grid gap-5 md:grid-cols-3">
    @foreach([['Échéances', $subscription->installments->count()], ['Paiements', $subscription->payments->count()], ['Reçus', $subscription->receipts->count()]] as [$label, $count])
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">{{ $label }}</p>
        <p class="text-3xl font-bold">{{ $count }}</p>
    </div>
    @endforeach
</div>

</x-layouts.app>
