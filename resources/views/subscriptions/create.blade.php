<x-layouts.app title="Nouvelle souscription">
<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Nouvelle souscription</h1>
        @if($preselectedCustomer)
            <p class="mt-1 text-slate-600">Client : <strong>{{ $preselectedCustomer->customer_number }} · {{ $preselectedCustomer->first_name }} {{ $preselectedCustomer->last_name }}</strong></p>
        @endif
    </div>

    <form method="POST" action="{{ route('subscriptions.store') }}" class="grid gap-5 rounded-xl bg-white p-6 shadow-sm md:grid-cols-2">
        @csrf

        {{-- Client --}}
        @if($preselectedCustomer)
            <input type="hidden" name="customer_id" value="{{ $preselectedCustomer->id }}">
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 md:col-span-2">
                <p class="text-sm font-semibold text-amber-900">Client pré-sélectionné</p>
                <p class="text-sm text-amber-800">{{ $preselectedCustomer->first_name }} {{ $preselectedCustomer->last_name }} — {{ $preselectedCustomer->customer_number }}</p>
            </div>
        @else
            <label class="md:col-span-2">Client <span class="text-danger text-red-500 ms-1 font-bold" style="color: var(--app-danger, #dc3545);">*</span>
                <select name="customer_id" class="mt-2 w-full rounded-lg border p-3">
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                            {{ $customer->customer_number }} · {{ $customer->first_name }} {{ $customer->last_name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </label>
        @endif

        {{-- Parcelle --}}
        @if($preselectedPlot)
            <input type="hidden" name="plot_id" value="{{ $preselectedPlot->id }}">
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3">
                <p class="text-sm font-semibold text-amber-900">Parcelle pré-sélectionnée</p>
                <p class="text-sm text-amber-800">{{ $preselectedPlot->reference }} — {{ $preselectedPlot->avenue->neighborhood->name }}</p>
            </div>
        @else
            <label>Parcelle disponible <span class="text-danger text-red-500 ms-1 font-bold" style="color: var(--app-danger, #dc3545);">*</span>
                <select name="plot_id" class="mt-2 w-full rounded-lg border p-3">
                    @foreach($plots as $plot)
                        <option value="{{ $plot->id }}" @selected(old('plot_id') == $plot->id)>
                            {{ $plot->reference }} · {{ $plot->avenue->neighborhood->name }}
                            @if($plot->commercial_status === 'reserved') (Réservée) @endif
                        </option>
                    @endforeach
                </select>
                @error('plot_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </label>
        @endif

        {{-- Formule --}}
        <label>Formule <span class="text-danger text-red-500 ms-1 font-bold" style="color: var(--app-danger, #dc3545);">*</span>
            <select name="payment_plan_id" id="payment_plan_id" class="mt-2 w-full rounded-lg border p-3">
                @foreach($paymentPlans as $plan)
                    <option value="{{ $plan->id }}" @selected(old('payment_plan_id') == $plan->id)>
                        {{ $plan->name }} · {{ $plan->total_price }} USD
                        @if($plan->duration_months > 0) ({{ $plan->monthly_amount }} USD × {{ $plan->duration_months }} mois) @endif
                    </option>
                @endforeach
            </select>
            @error('payment_plan_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </label>

        <x-auth-input name="subscription_date" label="Date de souscription" type="date" :value="old('subscription_date', now()->toDateString())" required />
        <x-auth-input name="start_date" label="Date de début d'échéancier" type="date" :value="old('start_date', now()->toDateString())" required />

        {{-- Acompte initial --}}
        <div class="md:col-span-2 border-t pt-4">
            <p class="mb-3 text-sm font-semibold text-slate-700">
                Acompte initial
                <span class="font-normal text-slate-500"> — facultatif. Si renseigné, la souscription sera activée immédiatement.</span>
            </p>
            <div class="grid gap-4 md:grid-cols-2">
                <x-auth-input name="deposit" label="Montant de l'acompte (USD)" type="number" step="0.01" min="1" :value="old('deposit')" />
                <label class="flex flex-col gap-2 text-sm font-medium text-slate-700">Mode de l'acompte
                    <select name="deposit_method" class="rounded-lg border border-slate-300 px-3 py-2.5">
                        <option value="cash" @selected(old('deposit_method','cash') === 'cash')>Espèces</option>
                        <option value="bank_transfer" @selected(old('deposit_method') === 'bank_transfer')>Virement</option>
                        <option value="mobile_money" @selected(old('deposit_method') === 'mobile_money')>Mobile Money</option>
                        <option value="card" @selected(old('deposit_method') === 'card')>Carte</option>
                        <option value="other" @selected(old('deposit_method') === 'other')>Autre</option>
                    </select>
                    @error('deposit_method')<span class="text-xs text-red-700">{{ $message }}</span>@enderror
                </label>
            </div>
        </div>

        <button class="rounded-lg bg-slate-950 p-3 font-semibold text-white md:col-span-2">
            Créer la souscription
        </button>
    </form>
</div>
</x-layouts.app>
