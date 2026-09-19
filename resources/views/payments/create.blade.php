<x-layouts.app title="Nouveau paiement">
    <div class="mx-auto max-w-3xl space-y-6">

        {{-- En-tête --}}
        <div>
            <a href="{{ route('subscriptions.show', $subscription) }}"
               class="text-sm text-slate-500 hover:text-slate-700">← Retour à la souscription</a>
            <h1 class="mt-1 text-3xl font-bold">Nouveau paiement</h1>
            <p class="text-slate-600">
                {{ $subscription->customer->first_name }} {{ $subscription->customer->last_name }}
                · Parcelle <strong>{{ $subscription->plot->reference }}</strong>
                · {{ $subscription->plot->avenue->neighborhood->name }}
            </p>
        </div>

        {{-- ═══ Résumé financier ═══ --}}
        <div class="grid grid-cols-3 gap-3">
            @foreach([
                ['Contractuel',    $subscription->contract_total, 'text-slate-700', '💰'],
                ['Déjà payé',      $subscription->amount_paid,    'text-emerald-700', '✅'],
                ['Solde restant',  $subscription->balance,        'text-red-700', '⏳'],
            ] as [$label, $amount, $color, $icon])
                <div class="rounded-xl bg-white p-4 shadow-sm border border-slate-100">
                    <p class="text-xs text-slate-500">{{ $icon }} {{ $label }}</p>
                    <p class="mt-1 text-lg font-bold {{ $color }}">{{ number_format((float)$amount, 2) }} {{ $currency }}</p>
                </div>
            @endforeach
        </div>

        {{-- ═══ Situation des échéances ═══ --}}
        @php
            $overdueInstallments = $subscription->installments->where('status', 'overdue')->sortBy('due_date');
            $overdueTotal        = (float) $overdueInstallments->sum('balance');
            $partialInstallments = $subscription->installments->where('status', 'partially_paid')->sortBy('due_date');
            $partialTotal        = (float) $partialInstallments->sum('balance');
            $dueInstallments     = $subscription->installments->where('status', 'due')->sortBy('due_date');
            $dueTotal            = (float) $dueInstallments->sum('balance');

            $suggestedAmount     = null;
            if ($overdueTotal > 0) {
                $suggestedAmount = $overdueTotal + $partialTotal + $dueTotal;
            } elseif ($partialTotal > 0) {
                $suggestedAmount = $partialTotal;
            } elseif ($nextInstallment) {
                $suggestedAmount = (float) $nextInstallment->balance;
            }
        @endphp

        <div class="rounded-xl bg-white shadow-sm border border-slate-100 overflow-hidden">
            <div class="bg-slate-50 border-b border-slate-100 px-5 py-3 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">📋 Situation des échéances</h2>
                @if($suggestedAmount)
                    <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-2 py-1 rounded-full">
                        Montant suggéré : {{ number_format($suggestedAmount, 2) }} {{ $currency }}
                    </span>
                @endif
            </div>

            <div class="divide-y divide-slate-100">

                {{-- Échéances en retard --}}
                @if($overdueInstallments->isNotEmpty())
                    <div class="px-5 py-3 bg-red-50">
                        <p class="text-xs font-bold text-red-700 uppercase tracking-wide mb-2">🔴 En retard ({{ $overdueInstallments->count() }})</p>
                        @foreach($overdueInstallments as $inst)
                            <div class="flex justify-between text-sm py-0.5">
                                <span class="text-red-800">
                                    Échéance #{{ $inst->installment_number }}
                                    — <span class="font-mono">{{ \Carbon\Carbon::parse($inst->due_date)->format('d/m/Y') }}</span>
                                </span>
                                <span class="font-bold text-red-700">
                                    {{ number_format((float)$inst->balance, 2) }} {{ $currency }}
                                    @if($inst->amount_paid > 0)
                                        <span class="font-normal text-red-400 text-xs">(payé : {{ number_format((float)$inst->amount_paid, 2) }})</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                        <div class="flex justify-between text-sm font-bold border-t border-red-200 mt-2 pt-1 text-red-800">
                            <span>Total impayé en retard</span>
                            <span>{{ number_format($overdueTotal, 2) }} {{ $currency }}</span>
                        </div>
                    </div>
                @endif

                {{-- Partiellement payées --}}
                @if($partialInstallments->isNotEmpty())
                    <div class="px-5 py-3 bg-orange-50">
                        <p class="text-xs font-bold text-orange-700 uppercase tracking-wide mb-2">🟠 Partiellement payées ({{ $partialInstallments->count() }})</p>
                        @foreach($partialInstallments as $inst)
                            <div class="flex justify-between text-sm py-0.5">
                                <span class="text-orange-800">
                                    Échéance #{{ $inst->installment_number }}
                                    — <span class="font-mono">{{ \Carbon\Carbon::parse($inst->due_date)->format('d/m/Y') }}</span>
                                </span>
                                <span class="font-bold text-orange-700">
                                    Reste : {{ number_format((float)$inst->balance, 2) }} {{ $currency }}
                                    <span class="font-normal text-orange-400 text-xs">(payé : {{ number_format((float)$inst->amount_paid, 2) }})</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Échéance du mois en cours --}}
                @if($dueInstallments->isNotEmpty())
                    <div class="px-5 py-3 bg-blue-50">
                        <p class="text-xs font-bold text-blue-700 uppercase tracking-wide mb-2">🔵 À payer ce mois ({{ $dueInstallments->count() }})</p>
                        @foreach($dueInstallments as $inst)
                            <div class="flex justify-between text-sm py-0.5">
                                <span class="text-blue-800">
                                    Échéance #{{ $inst->installment_number }}
                                    — <span class="font-mono">{{ \Carbon\Carbon::parse($inst->due_date)->format('d/m/Y') }}</span>
                                </span>
                                <span class="font-bold text-blue-700">{{ number_format((float)$inst->amount_due, 2) }} {{ $currency }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Prochain à venir --}}
                @if($nextInstallment && $nextInstallment->status === 'upcoming')
                    <div class="px-5 py-3 bg-slate-50">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">⏰ Prochaine échéance</p>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-700">
                                Échéance #{{ $nextInstallment->installment_number }}
                                — <span class="font-mono">{{ \Carbon\Carbon::parse($nextInstallment->due_date)->format('d/m/Y') }}</span>
                            </span>
                            <span class="font-bold text-slate-700">{{ number_format((float)$nextInstallment->amount_due, 2) }} {{ $currency }}</span>
                        </div>
                    </div>
                @endif

                {{-- Tout est à jour --}}
                @if($overdueInstallments->isEmpty() && $partialInstallments->isEmpty() && $dueInstallments->isEmpty() && !$nextInstallment)
                    <div class="px-5 py-4 text-center text-emerald-700 font-semibold text-sm">
                        ✅ Toutes les échéances sont à jour.
                    </div>
                @endif
            </div>
        </div>

        {{-- ═══ Formulaire de paiement ═══ --}}
        <form method="POST" enctype="multipart/form-data"
              action="{{ route('payments.store') }}"
              class="rounded-xl bg-white p-6 shadow-sm border border-slate-100 space-y-4"
              id="payment-form">
            @csrf
            <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
            <input type="hidden" name="currency" value="{{ $currency }}">

            <h2 class="font-semibold text-slate-800 text-lg">💳 Détails du paiement</h2>

            <div class="grid gap-4 md:grid-cols-2">

                {{-- Date --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date du paiement <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="payment_date" id="payment_date"
                           value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-lg border border-slate-300 p-3 text-sm focus:ring-2 focus:ring-slate-900 focus:border-transparent"
                           required>
                    @error('payment_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Montant --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Montant ({{ $currency }}) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="amount" id="amount-input" step="0.01" min="0.01"
                               value="{{ old('amount', $suggestedAmount ? number_format($suggestedAmount, 2, '.', '') : '') }}"
                               class="w-full rounded-lg border border-slate-300 p-3 pr-24 text-sm focus:ring-2 focus:ring-slate-900 focus:border-transparent"
                               required>
                        @if($suggestedAmount)
                            <button type="button"
                                    onclick="document.getElementById('amount-input').value = '{{ number_format($suggestedAmount, 2, '.', '') }}'"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-xs bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold px-2 py-1 rounded transition">
                                Suggéré
                            </button>
                        @endif
                    </div>
                    @if($suggestedAmount)
                        <p class="text-xs text-amber-700 mt-1">
                            💡 Montant suggéré : <strong>{{ number_format($suggestedAmount, 2) }} {{ $currency }}</strong>
                            @if($overdueTotal > 0) (retards + mois en cours) @elseif($partialTotal > 0) (reste à payer) @else (mensualité) @endif
                        </p>
                    @endif
                    @error('amount')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Mode de paiement --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mode de règlement <span class="text-red-500">*</span></label>
                    <select name="payment_method" id="payment_method"
                            class="w-full rounded-lg border border-slate-300 p-3 text-sm focus:ring-2 focus:ring-slate-900">
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method }}" {{ old('payment_method') === $method ? 'selected' : '' }}>
                                {{ __('payment_methods.'.$method) === 'payment_methods.'.$method ? str_replace('_', ' ', ucfirst($method)) : __('payment_methods.'.$method) }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_method')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Référence externe --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Référence externe</label>
                    <input type="text" name="transaction_reference" id="transaction_reference"
                           value="{{ old('transaction_reference') }}"
                           placeholder="N° virement, reçu mobile..."
                           class="w-full rounded-lg border border-slate-300 p-3 text-sm focus:ring-2 focus:ring-slate-900">
                    @error('transaction_reference')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Preuve --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Preuve de paiement</label>
                    <input type="file" name="proof" id="proof"
                           accept=".pdf,.jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
                    @error('proof')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Commentaire --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Commentaire</label>
                    <textarea name="notes" id="notes" rows="2"
                              placeholder="Observations, notes..."
                              class="w-full rounded-lg border border-slate-300 p-3 text-sm focus:ring-2 focus:ring-slate-900">{{ old('notes') }}</textarea>
                    @error('notes')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-slate-950 p-3 text-white font-semibold hover:bg-slate-800 transition">
                ✔ Valider le paiement
            </button>
        </form>

    </div>
</x-layouts.app>
