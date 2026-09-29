<x-layouts.app title="Mes frais connexes">
    <div class="resource-page portal-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Échéances annexes</p><h1 class="resource-heading__title">Mes frais connexes</h1><p class="resource-heading__description">Consultez les frais de bornage, de cadastre et d’aménagement associés à vos parcelles, ainsi que les reçus des paiements validés.</p></div>
        </header>
        <section class="resource-table">
            <div class="resource-table__header"><div><h2>Frais et échéances</h2><p>{{ $ancillaryFees->total() }} échéance(s)</p></div></div>
            <div class="table-responsive">
                <table class="table resource-data-table portal-data-table">
                    <thead><tr><th>Frais</th><th>Parcelle</th><th>Échéance</th><th class="text-end">Montant</th><th class="text-end">Payé</th><th class="text-end">Solde</th><th>Statut</th><th>Reçus</th></tr></thead>
                    <tbody>
                        @forelse($ancillaryFees as $fee)
                            <tr class="{{ $fee->status === 'overdue' ? 'resource-data-table__row--attention' : '' }}">
                                <td><strong>{{ $fee->label() }}</strong>@if($fee->fee_type === 'development' && $fee->installment_number > 1)<small class="d-block text-muted">Mensualité {{ $fee->installment_number }} sur 36</small>@endif</td>
                                <td>{{ $fee->subscription->plot->reference }}</td>
                                <td class="resource-data-table__secondary">{{ $fee->due_date->format('d/m/Y') }}</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->amount_due, 2, ',', ' ') }} USD</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->amount_paid, 2, ',', ' ') }} USD</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->balance, 2, ',', ' ') }} USD</td>
                                <td><span class="status-badge status-badge--{{ $fee->status === 'paid' ? 'active' : ($fee->status === 'overdue' ? 'danger' : 'neutral') }}">{{ str($fee->status)->replace('_', ' ')->title() }}</span></td>
                                <td>
                                    @foreach($fee->payments->where('status', 'validated')->sortBy('payment_date') as $payment)
                                        @if($payment->receipt && $payment->receipt->status === 'valid')
                                            <a class="resource-reference d-block" href="{{ URL::temporarySignedRoute('portal.receipts.download', now()->addMinutes(10), $payment->receipt) }}">{{ $payment->receipt->receipt_number }}</a>
                                        @endif
                                    @endforeach
                                    @if($fee->payments->where('status', 'validated')->isEmpty())<span class="resource-data-table__secondary">—</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="resource-empty"><strong>Aucun frais connexe disponible</strong><span>Les frais liés à vos parcelles apparaîtront lorsqu’ils seront générés.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if($ancillaryFees->hasPages())<div class="resource-pagination">{{ $ancillaryFees->links() }}</div>@endif
    </div>
</x-layouts.app>
