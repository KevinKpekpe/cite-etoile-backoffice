<x-layouts.app :title="$customer->first_name.' '.$customer->last_name">

    <x-user-credentials-markdown />

    @php
        $allCustomerInstallments = $customer->subscriptions->pluck('installments')->flatten();
        $overdueInstallments = $allCustomerInstallments->where('status', 'overdue');
        $overdueCount = $overdueInstallments->count();
        $overdueTotal = $overdueInstallments->sum('balance');
    @endphp

    @if($overdueCount > 0)
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h3 class="font-bold text-lg text-red-800">Retard de paiement détecté</h3>
                    <p class="text-sm text-red-700">
                        Ce client a <strong>{{ $overdueCount }} échéance(s) en retard</strong> pour un montant total impayé de
                        <strong>{{ number_format((float) $overdueTotal, 2) }} USD</strong>.
                    </p>
                </div>
            </div>
            @php
                $firstOverdueSub = $customer->subscriptions->first(fn($s) => $s->installments->where('status', 'overdue')->isNotEmpty());
            @endphp
            @if($firstOverdueSub)
                @can('payments.create')
                    <a href="{{ route('payments.create', ['subscription' => $firstOverdueSub->id]) }}"
                       class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition">
                        Encaisser l'impayé
                    </a>
                @endcan
            @endif
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-sm text-amber-700 font-bold">{{ $customer->customer_number }}</p>
            <h1 class="text-3xl font-bold text-slate-900">{{ $customer->first_name }} {{ $customer->middle_name }} {{ $customer->last_name }}</h1>
            <p class="text-slate-600">{{ $customer->phone }} · {{ $customer->email ?: 'Sans e-mail' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('customers.update')
                <a href="{{ route('customers.edit', $customer) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">Modifier</a>
            @endcan
            @can('subscriptions.create')
                <a href="{{ route('subscriptions.create', ['customer_id' => $customer->id]) }}" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 transition shadow-sm">Réserver une parcelle</a>
            @endcan
            @can('customers.delete')
                @if($customer->status !== 'archived')
                    <form method="POST" action="{{ route('customers.archive', $customer) }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 transition">Archiver</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <h2 class="mb-4 font-bold text-slate-900">Profil</h2>
            <dl class="grid gap-3 text-sm">
                <div><dt class="text-slate-500">Statut</dt><dd class="font-medium text-slate-900">{{ ucfirst($customer->status) }}</dd></div>
                <div><dt class="text-slate-500">Adresse</dt><dd class="text-slate-900">{{ $customer->address ?: '—' }}, {{ $customer->commune ?: '' }} {{ $customer->city ?: '' }}</dd></div>
                <div><dt class="text-slate-500">Responsable</dt><dd class="text-slate-900">{{ $customer->assignedAgent ? $customer->assignedAgent->first_name.' '.$customer->assignedAgent->last_name : 'Non attribué' }}</dd></div>
                <div><dt class="text-slate-500">Observations</dt><dd class="whitespace-pre-line text-slate-900">{{ $customer->internal_notes ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100 lg:col-span-2">
            <h2 class="mb-4 font-bold text-slate-900">Situation financière</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                    <p class="text-xs text-slate-500 font-medium">Souscriptions</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $customer->subscriptions->count() }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50 p-4 border border-emerald-100">
                    <p class="text-xs text-emerald-700 font-medium">Total versé</p>
                    <p class="text-2xl font-bold text-emerald-900">{{ number_format((float)$customer->payments->where('status','validated')->sum('amount'),2) }} USD</p>
                </div>
                <div class="rounded-xl {{ $overdueCount > 0 ? 'bg-red-50 border-red-100' : 'bg-slate-50 border-slate-100' }} p-4">
                    <p class="text-xs {{ $overdueCount > 0 ? 'text-red-700' : 'text-slate-500' }} font-medium">Solde restant</p>
                    <p class="text-2xl font-bold {{ $overdueCount > 0 ? 'text-red-900' : 'text-slate-900' }}">{{ number_format((float)$customer->subscriptions->sum('balance'),2) }} USD</p>
                    @if($overdueCount > 0)
                        <p class="text-xs font-semibold text-red-700 mt-1">⚠️ Dont {{ number_format((float) $overdueTotal, 2) }} USD en retard</p>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <section class="mt-5 rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
        <h2 class="mb-4 font-bold text-slate-900">Parcelles et souscriptions</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="py-2">Souscription</th>
                        <th>Parcelle</th>
                        <th>Formule</th>
                        <th>Échéances</th>
                        <th>Statut</th>
                        <th>Solde</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customer->subscriptions as $subscription)
                        @php
                            $subOverdue = $subscription->installments->where('status', 'overdue');
                            $subOverdueCount = $subOverdue->count();
                            $canPay = $subscription->financial_status !== 'paid' && !in_array($subscription->commercial_status, ['completed', 'cancelled', 'terminated'], true);
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ $subOverdueCount > 0 ? 'bg-red-50/40' : '' }}">
                            <td class="py-3 font-mono font-bold text-amber-700">
                                <a href="{{ route('subscriptions.show', $subscription) }}" class="hover:underline">
                                    {{ $subscription->subscription_number }}
                                </a>
                            </td>
                            <td>{{ $subscription->plot->reference }}</td>
                            <td>{{ $subscription->paymentPlan->name }}</td>
                            <td>
                                @if($subOverdueCount > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800">
                                        ⚠️ {{ $subOverdueCount }} retard(s)
                                    </span>
                                @elseif($subscription->financial_status === 'paid')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                        ✓ Soldée
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">À jour</span>
                                @endif
                            </td>
                            <td>{{ $subscription->commercial_status }}</td>
                            <td class="font-semibold text-slate-900">{{ number_format((float) $subscription->balance, 2) }} USD</td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('subscriptions.show', $subscription) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 transition">
                                        Détails
                                    </a>
                                    @if($canPay)
                                        @can('payments.create')
                                            <a href="{{ route('payments.create', ['subscription' => $subscription->id]) }}"
                                               class="rounded-lg {{ $subOverdueCount > 0 ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }} px-3 py-1.5 text-xs font-semibold text-white transition shadow-sm">
                                                Encaisser
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-5 text-slate-500">Aucune souscription pour ce client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <h2 class="mb-4 font-bold text-slate-900">Paiements et reçus</h2>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse($customer->payments as $payment)
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-mono font-semibold text-slate-900">{{ $payment->payment_reference }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->payment_date->format('d/m/Y') }} · {{ ucfirst($payment->payment_method) }}</p>
                        </div>
                        <strong class="text-slate-900">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</strong>
                    </li>
                @empty
                    <li class="py-3 text-slate-500">Aucun paiement effectué.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <h2 class="mb-4 font-bold text-slate-900">Documents privés</h2>
            <ul class="mb-5 divide-y divide-slate-100 text-sm">
                @forelse($customer->documents as $document)
                    <li class="flex items-center justify-between py-3">
                        <span>{{ $document->name }} <small class="text-slate-500">({{ $document->document_type }})</small></span>
                        @can('documents.download')
                            <a class="font-medium text-amber-700 hover:underline" href="{{ route('customers.documents.download', [$customer, $document]) }}">Télécharger</a>
                        @endcan
                    </li>
                @empty
                    <li class="py-3 text-slate-500">Aucun document importé.</li>
                @endforelse
            </ul>
            @can('customers.update')
                <form method="POST" enctype="multipart/form-data" action="{{ route('customers.documents.store', $customer) }}" class="grid gap-3">
                    @csrf
                    <select name="document_type" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                        @foreach(['identity'=>'Identité','photo'=>'Photo','contract'=>'Contrat','payment_proof'=>'Preuve de paiement','subscription_document'=>'Souscription','other'=>'Autre'] as $value=>$label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="text-sm">
                    <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition">Ajouter un document</button>
                    @error('document')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </form>
            @endcan
        </section>
    </div>

</x-layouts.app>
