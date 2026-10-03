<x-layouts.app title="{{ __('portal.my_ancillary_fees') }}">
    <div class="resource-page portal-page">
        <header class="resource-heading">
            <div><p class="app-kicker">{{ __('portal.due_dates') }} annexes</p><h1 class="resource-heading__title">{{ __('portal.my_ancillary_fees') }}</h1><p class="resource-heading__description">{{ __('portal.ancillary_fees_description') }}</p></div>
        </header>
        <section class="resource-table">
            <div class="resource-table__header"><div><h2>{{ __('portal.fees_and_due_dates') }}</h2><p>{{ $ancillaryFees->total() }} {{ __('portal.due_dates_count') }}</p></div></div>
            <div class="table-responsive">
                <table class="table resource-data-table portal-data-table">
                    <thead><tr><th>{{ __('portal.fee') }}</th><th>{{ __('portal.plot') }}</th><th>{{ __("Échéance") }}</th><th class="text-end">{{ __('portal.amount') }}</th><th class="text-end">{{ __('portal.paid') }}</th><th class="text-end">{{ __('portal.balance') }}</th><th>{{ __("Statut") }}</th><th>{{ __('portal.receipts') }}</th></tr></thead>
                    <tbody>
                        @forelse($ancillaryFees as $fee)
                            <tr class="{{ $fee->status === 'overdue' ? 'resource-data-table__row--attention' : '' }}">
                                <td><strong>{{ $fee->fee_label ?: __('fee_types.'.$fee->fee_type) }}</strong>@if($fee->fee_type === 'development' && $fee->installment_number > 1)<small class="d-block text-muted">{{ __('portal.installment_number') }} {{ $fee->installment_number }} {{ __('portal.out of 36') }}</small>@endif</td>
                                <td>{{ $fee->subscription->plot->reference }}</td>
                                <td class="resource-data-table__secondary">{{ $fee->due_date->format('d/m/Y') }}</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->amount_due, 2, ',', ' ') }} USD</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->amount_paid, 2, ',', ' ') }} USD</td>
                                <td class="record-money text-end">{{ number_format((float) $fee->balance, 2, ',', ' ') }} USD</td>
                                <td><span class="status-badge status-badge--{{ $fee->status === 'paid' ? 'active' : ($fee->status === 'overdue' ? 'danger' : 'neutral') }}">{{ __('statuses.'.$fee->status) }}</span></td>
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
                            <tr><td colspan="8"><div class="resource-empty"><strong>{{ __('portal.no_ancillary_fees') }}</strong><span>{{ __('portal.no_ancillary_fees_yet') }}</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if($ancillaryFees->hasPages())<div class="resource-pagination">{{ $ancillaryFees->links() }}</div>@endif
    </div>
</x-layouts.app>
