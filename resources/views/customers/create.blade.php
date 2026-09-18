<x-layouts.app title="Nouveau client">
<div class="mx-auto max-w-4xl">
    <h1 class="mb-6 text-3xl font-bold">Nouveau client</h1>

    <form method="POST" action="{{ route('customers.store') }}" class="space-y-6">
        @csrf

        {{-- Section 1 : Informations client --}}
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-bold">1 — Informations client</h2>
            <x-customer-form :agents="$agents" />
        </div>

        {{-- Section 2 : Souscription initiale (optionnelle) --}}
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-lg font-bold">2 — Souscription initiale</h2>
            <p class="mb-5 text-sm text-slate-500">Optionnelle. Si une parcelle est choisie, vous serez redirigé vers l'encaissement après la création.</p>

            <div class="grid gap-5 md:grid-cols-2">
                {{-- Parcelle --}}
                <label class="flex flex-col gap-2 text-sm font-medium text-slate-700">Parcelle disponible
                    <select name="plot_id" id="plot_id" class="rounded-lg border border-slate-300 px-3 py-2.5">
                        <option value="">— Aucune souscription maintenant —</option>
                        @foreach($plots as $plot)
                            <option value="{{ $plot->id }}" @selected(old('plot_id') == $plot->id)>
                                {{ $plot->reference }} · {{ $plot->avenue->neighborhood->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('plot_id')<span class="text-xs text-red-700">{{ $message }}</span>@enderror
                </label>

                {{-- Formule --}}
                <label class="flex flex-col gap-2 text-sm font-medium text-slate-700">Formule de paiement
                    <select name="payment_plan_id" id="payment_plan_id" class="rounded-lg border border-slate-300 px-3 py-2.5">
                        <option value="">— Choisir une formule —</option>
                        @foreach($paymentPlans as $plan)
                            <option value="{{ $plan->id }}"
                                data-duration="{{ $plan->duration_months }}"
                                data-price="{{ $plan->total_price }}"
                                data-monthly="{{ $plan->monthly_amount }}"
                                @selected(old('payment_plan_id') == $plan->id)>
                                {{ $plan->name }} — {{ number_format((float)$plan->total_price, 2) }} USD
                                @if($plan->duration_months > 0)
                                    ({{ $plan->monthly_amount }} USD × {{ $plan->duration_months }} mois)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('payment_plan_id')<span class="text-xs text-red-700">{{ $message }}</span>@enderror
                </label>

                {{-- Récapitulatif formule --}}
                <div id="plan-summary" class="hidden md:col-span-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <span id="plan-summary-text"></span>
                </div>

                <x-auth-input name="subscription_date" label="Date de souscription" type="date" :value="old('subscription_date', now()->toDateString())" />
                <x-auth-input name="start_date" label="Date de début d'échéancier" type="date" :value="old('start_date', now()->toDateString())" />

                {{-- Acompte --}}
                <div class="md:col-span-2 border-t pt-4">
                    <p class="mb-3 text-sm font-semibold text-slate-700">Acompte initial
                        <span class="font-normal text-slate-500"> — facultatif. Si renseigné, le reçu s'affiche immédiatement. Sinon vous serez dirigé vers le formulaire de paiement.</span>
                    </p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-auth-input name="deposit" label="Montant de l'acompte (USD)" type="number" step="0.01" min="1" :value="old('deposit')" />
                        <label class="flex flex-col gap-2 text-sm font-medium text-slate-700">Mode de paiement
                            <select name="deposit_method" class="rounded-lg border border-slate-300 px-3 py-2.5">
                                <option value="cash" @selected(old('deposit_method', 'cash') === 'cash')>Espèces</option>
                                <option value="bank_transfer" @selected(old('deposit_method') === 'bank_transfer')>Virement bancaire</option>
                                <option value="mobile_money" @selected(old('deposit_method') === 'mobile_money')>Mobile Money</option>
                                <option value="card" @selected(old('deposit_method') === 'card')>Carte</option>
                                <option value="other" @selected(old('deposit_method') === 'other')>Autre</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button class="rounded-lg bg-slate-950 px-6 py-3 font-semibold text-white">Créer le client</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const planSelect = document.getElementById('payment_plan_id');
    const summary = document.getElementById('plan-summary');
    const summaryText = document.getElementById('plan-summary-text');

    function updateSummary() {
        const option = planSelect.options[planSelect.selectedIndex];
        const duration = parseInt(option.dataset.duration || '0');
        const price = option.dataset.price;
        const monthly = option.dataset.monthly;

        if (!price) { summary.classList.add('hidden'); return; }

        summary.classList.remove('hidden');
        if (duration > 0) {
            summaryText.textContent = `Crédit sur ${duration} mois — Total ${price} USD — ${monthly} USD/mois`;
        } else {
            summaryText.textContent = `Paiement comptant — ${price} USD en une seule fois`;
        }
    }

    planSelect.addEventListener('change', updateSummary);
    if (planSelect.value) updateSummary();
});
</script>
</x-layouts.app>
