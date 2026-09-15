<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Plot;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    /** @return array<string, mixed> */
    public function metrics(string $period, bool $includeSensitive): array
    {
        $now = CarbonImmutable::now();
        $validSubscriptions = Subscription::query()->whereNotIn('commercial_status', ['cancelled', 'terminated']);
        $contractual = (float) (clone $validSubscriptions)->sum('contract_total');
        $collected = (float) Payment::query()->where('status', 'validated')->sum('amount');

        return [
            'clients' => [
                'total' => Customer::query()->count(), 'active' => Customer::query()->where('status', 'active')->count(),
                'new_month' => Customer::query()->whereBetween('created_at', [$now->startOfMonth(), $now->endOfMonth()])->count(),
                'settled' => Customer::query()->where('status', 'settled')->count(),
                'overdue' => Customer::query()->whereHas('subscriptions.installments', fn ($query) => $query->where('status', 'overdue'))->count(),
            ],
            'plots' => Plot::query()->select('commercial_status', DB::raw('COUNT(*) AS total'))->groupBy('commercial_status')->pluck('total', 'commercial_status')->map(fn ($value) => (int) $value)->all(),
            'subscriptions' => Subscription::query()->select('commercial_status', DB::raw('COUNT(*) AS total'))->groupBy('commercial_status')->pluck('total', 'commercial_status')->map(fn ($value) => (int) $value)->all(),
            'finances' => [
                'contractual' => $contractual, 'collected' => $collected, 'remaining' => max(0, $contractual - $collected),
                'today' => (float) Payment::query()->where('status', 'validated')->whereDate('payment_date', $now)->sum('amount'),
                'month' => (float) Payment::query()->where('status', 'validated')->whereBetween('payment_date', [$now->startOfMonth(), $now->endOfMonth()])->sum('amount'),
            ],
            'installments' => [
                'expected' => (float) Installment::query()->sum('amount_due'), 'paid' => (float) Installment::query()->sum('amount_paid'),
                'partial' => Installment::query()->where('status', 'partially_paid')->count(), 'overdue' => Installment::query()->where('status', 'overdue')->count(),
            ],
            'payment_chart' => $this->paymentChart($period, $now),
            'plan_distribution' => Subscription::query()->join('payment_plans', 'payment_plans.id', '=', 'subscriptions.payment_plan_id')->select('payment_plans.name', DB::raw('COUNT(*) AS total'))->groupBy('payment_plans.id', 'payment_plans.name')->orderByDesc('total')->get(),
            'due_soon' => $includeSensitive ? Installment::query()->with('subscription.customer')->whereIn('status', ['upcoming', 'due'])->whereBetween('due_date', [$now->toDateString(), $now->addDays(7)->toDateString()])->orderBy('due_date')->limit(10)->get() : collect(),
            'overdue_installments' => $includeSensitive ? Installment::query()->with('subscription.customer')->where('status', 'overdue')->oldest('due_date')->limit(10)->get() : collect(),
            'recent_activity' => $includeSensitive ? AuditLog::query()->latest()->limit(10)->get() : collect(),
            'period' => $period,
        ];
    }

    /** @return Collection<int, Payment> */
    private function paymentChart(string $period, CarbonImmutable $now): Collection
    {
        $query = Payment::query()->where('status', 'validated');

        [$from, $format] = match ($period) {
            'day' => [$now->startOfDay(), '%H:00'], 'week' => [$now->subDays(6)->startOfDay(), '%Y-%m-%d'],
            'year' => [$now->startOfYear(), '%Y-%m'], default => [$now->startOfMonth(), '%Y-%m-%d'],
        };

        return $query->where('payment_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(payment_date, '{$format}') AS label, SUM(amount) AS total")
            ->groupBy('label')->orderBy('label')->get();
    }
}
