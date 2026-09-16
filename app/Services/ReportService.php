<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Plot;
use Illuminate\Database\Eloquent\Collection;

class ReportService
{
    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $customers = Customer::query()
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['customer_status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('last_name')->get();
        $plots = Plot::query()->with('avenue.neighborhood')
            ->when($filters['plot_status'] ?? null, fn ($query, $status) => $query->where('commercial_status', $status))
            ->orderBy('reference')->get();
        $payments = Payment::query()->with(['customer', 'subscription.paymentPlan', 'receivedBy'])
            ->where('status', 'validated')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('payment_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('payment_date', '<=', $to))
            ->when($filters['payment_plan_id'] ?? null, fn ($query, $plan) => $query->whereHas('subscription', fn ($query) => $query->where('payment_plan_id', $plan)))
            ->when($filters['agent_id'] ?? null, fn ($query, $agent) => $query->where('received_by', $agent))
            ->latest('payment_date')->get();
        $overdue = Installment::query()->with(['subscription.customer', 'subscription.plot'])
            ->whereDate('due_date', '<', now()->toDateString())->whereColumn('amount_paid', '<', 'amount_due')
            ->oldest('due_date')->get();

        return [
            'customers' => $customers, 'plots' => $plots, 'payments' => $payments, 'overdue' => $overdue,
            'customerCounts' => $customers->countBy('status'), 'plotCounts' => $plots->countBy('commercial_status'),
            'paymentTotal' => (float) $payments->sum('amount'), 'overdueTotal' => (float) $overdue->sum('balance'),
        ];
    }

    /** @param array<string, mixed> $report
     * @return Collection<int, Customer|Plot|Payment|Installment>
     */
    public function rows(array $report, string $type): Collection
    {
        /** @var Collection<int, Customer|Plot|Payment|Installment> */
        return $report[$type];
    }
}
