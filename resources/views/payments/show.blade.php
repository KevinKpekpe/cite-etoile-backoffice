<x-layouts.app :title="$payment->payment_reference">
    <div class="mb-6">
        <p class="font-mono text-amber-700">{{ $payment->payment_reference }}</p>
        <h1 class="text-3xl font-bold">{{ $payment->amount }} {{ $payment->currency }}</h1>
        <p>{{ $payment->customer->first_name }} {{ $payment->customer->last_name }} · {{ $payment->subscription->plot->reference }}</p>
        @if($payment->receipt)<a href="{{ route('receipts.show',$payment->receipt) }}" class="mt-2 inline-block text-amber-700">Voir et télécharger le reçu</a>@endif
    </div>
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-xl bg-white p-5 shadow-sm"><h2 class="font-bold">Paiement</h2><p class="mt-3">Statut : {{ $payment->status }}</p><p>Date : {{ $payment->payment_date->format('d/m/Y H:i') }}</p><p>Mode : {{ $payment->payment_method }}</p><p>Référence : {{ $payment->transaction_reference ?: '—' }}</p></section>
        <section class="rounded-xl bg-white p-5 shadow-sm"><h2 class="font-bold">Affectations</h2>@forelse($payment->allocations as $allocation)<div class="flex justify-between border-b py-3"><span>Échéance {{ $allocation->installment->installment_number }}</span><strong>{{ $allocation->amount }} USD</strong></div>@empty<p class="mt-3 text-slate-500">Paiement Cash sans échéance mensuelle.</p>@endforelse</section>
    </div>
    @can('payments.cancel') @if($payment->status==='validated')<form method="POST" action="{{ route('payments.reverse',$payment) }}" class="mt-5 max-w-xl rounded-xl border border-red-200 bg-red-50 p-5">@csrf @method('PATCH')<label class="block">Motif d’extourne<textarea name="reason" required minlength="10" class="mt-2 w-full rounded-lg border p-3"></textarea></label><button class="mt-3 rounded-lg bg-red-700 px-4 py-2 text-white">Extourner le paiement</button></form>@endif @endcan
</x-layouts.app>
