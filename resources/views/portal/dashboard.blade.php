<x-layouts.app title="{{ __('portal.my_space') }}">
    <div class="resource-page portal-page">
        <header class="resource-heading">
            <div><p class="app-kicker">{{ __('portal.customer_area') }}</p><h1 class="resource-heading__title">Bonjour {{ $customer->first_name }}</h1><p class="resource-heading__description">{{ __('portal.dashboard_description') }}</p></div>
            <a href="{{ route('portal.profile.edit') }}" class="btn btn-outline"><i class="bi bi-person" aria-hidden="true"></i>{{ __('portal.ui.my_profile') }}</a>
        </header>

        <section class="report-metrics portal-metrics" aria-label="Synthèse financière">
            <div class="report-metric--success"><span>{{ __('portal.total_paid') }}</span><strong>{{ number_format($paid, 2, ',', ' ') }} <em>USD</em></strong><small>{{ __('portal.validated_payments') }}</small></div>
            <div><span>{{ __('portal.remaining_balance') }}</span><strong>{{ number_format($remaining, 2, ',', ' ') }} <em>USD</em></strong><small>{{ __('portal.contract_balance') }}</small></div>
            <div><span>{{ __('portal.next_due_date') }}</span><strong>{{ $nextInstallment ? $nextInstallment->due_date->format('d/m/Y') : 'Aucune' }}</strong><small>{{ $nextInstallment ? number_format((float) $nextInstallment->balance, 2, ',', ' ').' USD attendus' : 'Échéancier à jour' }}</small></div>
        </section>

        <section class="resource-table">
            <div class="resource-table__header"><div><h2>{{ __('portal.plots_and_plans') }}</h2><p>{{ trans_choice('portal.subscription_count', $subscriptions->count()) }}</p></div><a href="{{ route('portal.subscriptions.index') }}" class="btn btn-outline btn-sm">{{ __('portal.view_all') }}</a></div>
            <div class="table-responsive"><table class="table resource-data-table portal-data-table"><thead><tr><th>{{ __('portal.reference') }}</th><th>{{ __('portal.plot') }}</th><th>{{ __('portal.location') }}</th><th>{{ __('portal.plan') }}</th><th class="text-end">{{ __('portal.paid') }}</th><th class="text-end">{{ __('portal.remaining') }}</th></tr></thead><tbody>
                @forelse($subscriptions as $subscription)
                    <tr><td><a href="{{ route('portal.subscriptions.show', $subscription) }}" class="resource-reference">{{ $subscription->subscription_number }}</a></td><td><strong>{{ $subscription->plot->reference }}</strong></td><td class="resource-data-table__secondary">{{ $subscription->plot->avenue->neighborhood->name }}</td><td>{{ $subscription->paymentPlan->name }}</td><td class="record-money text-end">{{ number_format((float) $subscription->validated_paid, 2, ',', ' ') }} USD</td><td class="record-money text-end">{{ number_format((float) $subscription->balance, 2, ',', ' ') }} USD</td></tr>
                @empty<tr><td colspan="6"><div class="resource-empty"><strong>{{ __('portal.none') }} souscription</strong><span>{{ __('portal.no_subscriptions_dashboard') }}</span></div></td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
</x-layouts.app>
