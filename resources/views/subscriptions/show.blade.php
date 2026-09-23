<x-layouts.app :title="$subscription->subscription_number">

{{-- Flash warning (redirigé depuis payments.create) --}}
@if(session('warning'))
<div class="mb-6 rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-800">
    ⚠️ {{ session('warning') }}
</div>
@endif

{{-- ── Bandeau : Soldée ──────────────────────────────────────────────── --}}
@if($subscription->financial_status === 'paid' || $subscription->commercial_status === 'completed')
<div class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 p-5">
    <p class="font-bold text-emerald-900">✅ Souscription soldée</p>
    <p class="mt-1 text-sm text-emerald-800">Tous les paiements ont été reçus. Aucun encaissement supplémentaire n'est possible.</p>
</div>

{{-- ── Bandeau : Annulée / Résiliée ───────────────────────────────────── --}}
@elseif(in_array($subscription->commercial_status, ['cancelled', 'terminated']))
<div class="mb-6 rounded-xl border border-red-300 bg-red-50 p-5">
    <p class="font-bold text-red-900">🚫 Souscription {{ $subscription->commercial_status === 'cancelled' ? 'annulée' : 'résiliée' }}</p>
    <p class="mt-1 text-sm text-red-800">Aucun encaissement n'est possible sur cette souscription.</p>
</div>

{{-- ── Bandeau : En attente premier versement ─────────────────────────── --}}
@elseif($subscription->commercial_status === 'pending')
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

{{-- ── Encadré : Prochain paiement (actif + crédit + non soldé) ───────── --}}
@elseif($subscription->commercial_status === 'active' && $subscription->duration_months > 0 && $nextInstallment)
<div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-bold text-blue-900">📅 Prochain paiement</p>
            <div class="mt-2 grid gap-1 text-sm text-blue-800">
                <p>Échéance : <strong>{{ \Carbon\Carbon::parse($nextInstallment->due_date)->translatedFormat('d F Y') }}</strong></p>
                <p>Montant minimum : <strong>{{ $nextInstallment->amount_due }} USD</strong></p>
                <p>Solde total restant : <strong>{{ $subscription->balance }} USD</strong></p>
            </div>
        </div>
        @can('payments.create')
        <a href="{{ route('payments.create', $subscription) }}" class="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white">
            Encaisser ce paiement →
        </a>
        @endcan
    </div>
</div>
@endif

{{-- ── En-tête ──────────────────────────────────────────────────────── --}}
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="font-mono text-amber-700">{{ $subscription->subscription_number }}</p>
        <h1 class="text-3xl font-bold">{{ $subscription->customer->first_name }} {{ $subscription->customer->last_name }}</h1>
        <p>{{ $subscription->plot->reference }} · {{ $subscription->plot->avenue->neighborhood->name }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        {{-- Bouton Encaisser : seulement si active + pas soldée + pas annulée --}}
        @if($subscription->commercial_status === 'active'
            && $subscription->financial_status !== 'paid'
            && !in_array($subscription->commercial_status, ['completed', 'cancelled', 'terminated']))
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

{{-- ── Grille principale ────────────────────────────────────────────── --}}
<div class="grid gap-5 lg:grid-cols-3">

    {{-- Conditions figées --}}
    <section class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="font-bold">Conditions figées</h2>
        <dl class="mt-4 grid gap-2 text-sm">
            <div>Formule : <strong>{{ $subscription->paymentPlan->name }}</strong></div>
            <div>Total : <strong>{{ $subscription->contract_total }} USD</strong></div>
            @if($subscription->duration_months > 0)
            <div>Mensualité : <strong>{{ $subscription->monthly_amount }} USD</strong></div>
            <div>Durée : <strong>{{ $subscription->duration_months }} mois</strong></div>
            @else
            <div>Type : <strong>Comptant</strong></div>
            @endif
            <div class="border-t pt-2">Payé : <strong>{{ $subscription->amount_paid }} USD</strong></div>
            <div>Solde : <strong class="{{ (float) $subscription->balance > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $subscription->balance }} USD</strong></div>
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
                    } }}">{{ ucfirst($subscription->commercial_status) }}</span></dd>
            </div>
            <div><dt class="text-slate-500">Financier</dt><dd>{{ $subscription->financial_status }}</dd></div>
            <div><dt class="text-slate-500">Administratif</dt><dd>{{ $subscription->administrative_status }}</dd></div>
        </dl>

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

{{-- Historique des versements & Reçus (Factures PDF) --}}
<section class="mt-6 rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
    <h2 class="mb-4 font-bold text-slate-900"><i class="bi bi-receipt me-2 text-primary"></i> Historique des paiements & reçus (Factures PDF)</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="p-3">Référence</th>
                    <th class="p-3">Date</th>
                    <th class="p-3">Mode</th>
                    <th class="p-3">Montant</th>
                    <th class="p-3 text-right">Facture / Reçu PDF</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($subscription->payments as $payment)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 font-mono font-bold">
                            <a href="{{ route('payments.show', $payment) }}" class="text-amber-800 hover:underline">
                                {{ $payment->payment_reference }}
                            </a>
                        </td>
                        <td class="p-3 text-slate-600">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                        <td class="p-3 text-slate-600">{{ ucfirst($payment->payment_method) }}</td>
                        <td class="p-3 font-bold text-slate-900">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                        <td class="p-3 text-right">
                            @if($payment->receipt)
                                @can('receipts.download')
                                    <a href="{{ route('receipts.download', $payment->receipt) }}"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-slate-950 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition">
                                        <i class="bi bi-download me-1"></i> Télécharger PDF ({{ $payment->receipt->receipt_number }})
                                    </a>
                                @endcan
                            @else
                                <span class="text-xs text-slate-400">Pas de reçu</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-slate-500">Aucun versement enregistré sur cette souscription.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

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
