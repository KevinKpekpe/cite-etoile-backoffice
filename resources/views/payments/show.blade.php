<x-layouts.app :title="$payment->payment_reference">

{{-- En-tête --}}
<div class="mb-6">
    <p class="font-mono text-amber-700">{{ $payment->payment_reference }}</p>
    <h1 class="text-3xl font-bold">{{ $payment->amount }} {{ $payment->currency }}</h1>
    <p class="text-slate-600">
        {{ $payment->customer->first_name }} {{ $payment->customer->last_name }}
        · {{ $payment->subscription->plot->reference }}
    </p>
    <div class="mt-3 flex flex-wrap gap-3">
        @if($payment->receipt)
        <a href="{{ route('receipts.show', $payment->receipt) }}"
           class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white">
            <i class="bi bi-receipt me-1"></i> Voir / Télécharger le reçu
        </a>
        @endif
        <a href="{{ route('subscriptions.show', $payment->subscription) }}"
           class="rounded-lg border bg-white px-4 py-2 text-sm">
            Fiche souscription
        </a>
    </div>
</div>

{{-- Encadré prochain paiement (crédit, non soldé) --}}
@if($nextInstallment && $payment->subscription->commercial_status === 'active')
<div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-bold text-blue-900"><i class="bi bi-calendar-event me-1"></i> Prochain paiement à prévoir</p>
            <div class="mt-2 grid gap-1 text-sm text-blue-800">
                <p>Date d'échéance : <strong>{{ \Carbon\Carbon::parse($nextInstallment->due_date)->translatedFormat('d F Y') }}</strong></p>
                <p>Montant minimum : <strong>{{ $nextInstallment->amount_due }} USD</strong></p>
                <p>Solde restant : <strong>{{ $payment->subscription->balance }} USD</strong></p>
            </div>
        </div>
        @can('payments.create')
        <a href="{{ route('payments.create', $payment->subscription) }}"
           class="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white">
            Encaisser →
        </a>
        @endcan
    </div>
</div>
@elseif($payment->subscription->financial_status === 'paid' || $payment->subscription->commercial_status === 'completed')
<div class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 p-5">
    <p class="font-bold text-emerald-900"><i class="bi bi-check-circle-fill me-1"></i> Souscription soldée — tous les paiements ont été reçus.</p>
</div>
@endif

{{-- Détails --}}
<div class="grid gap-5 lg:grid-cols-2">
    <section class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="font-bold">Détail du paiement</h2>
        <dl class="mt-3 grid gap-2 text-sm">
            <div><dt class="text-slate-500">Statut</dt><dd>{{ ucfirst($payment->status) }}</dd></div>
            <div><dt class="text-slate-500">Date &amp; heure</dt><dd>{{ $payment->payment_date->format('d/m/Y H:i:s') }}</dd></div>
            <div><dt class="text-slate-500">Mode</dt><dd>{{ $payment->payment_method }}</dd></div>
            <div><dt class="text-slate-500">Référence transaction</dt><dd>{{ $payment->transaction_reference ?: '—' }}</dd></div>
            <div class="border-t pt-2"><dt class="text-slate-500">Formule</dt><dd>{{ $payment->subscription->paymentPlan->name }}</dd></div>
            <div><dt class="text-slate-500">Parcelle</dt><dd>{{ $payment->subscription->plot->reference }} · {{ $payment->subscription->plot->avenue->neighborhood->name }}</dd></div>
            <div class="border-t pt-2"><dt class="text-slate-500">Cumul payé</dt><dd class="font-semibold">{{ $payment->subscription->amount_paid }} USD</dd></div>
            <div><dt class="text-slate-500">Solde restant</dt><dd class="font-semibold {{ (float)$payment->subscription->balance > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $payment->subscription->balance }} USD</dd></div>
        </dl>
    </section>

    <section class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="font-bold">Affectations aux échéances</h2>
        @forelse($payment->allocations as $allocation)
        <div class="flex justify-between border-b py-3 text-sm">
            <span>Échéance {{ $allocation->installment->installment_number }}
                <span class="text-slate-500">({{ \Carbon\Carbon::parse($allocation->installment->due_date)->format('d/m/Y') }})</span>
            </span>
            <strong>{{ $allocation->amount }} USD</strong>
        </div>
        @empty
        <p class="mt-3 text-sm text-slate-500">Paiement comptant sans échéance mensuelle.</p>
        @endforelse
    </section>
</div>

{{-- Extourne --}}
@can('payments.cancel')
@if($payment->status === 'validated')
<form method="POST" action="{{ route('payments.reverse', $payment) }}"
      class="mt-5 max-w-xl rounded-xl border border-red-200 bg-red-50 p-5">
    @csrf @method('PATCH')
    <label class="block text-sm font-medium">Motif d'extourne
        <textarea name="reason" required minlength="10" rows="3"
                  class="mt-2 w-full rounded-lg border p-3 text-sm"></textarea>
    </label>
    <button class="mt-3 rounded-lg bg-red-700 px-4 py-2 text-white text-sm font-semibold">Extourner le paiement</button>
</form>
@endif
@endcan

</x-layouts.app>
