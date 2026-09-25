<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class InstallmentScheduleService
{
    /** @return Collection<int, Installment> */
    public function generate(Subscription $subscription): Collection
    {
        return DB::transaction(function () use ($subscription): Collection {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if ($subscription->installments()->exists() || $subscription->duration_months === 0) {
                return $subscription->installments()->orderBy('installment_number')->get();
            }

            if ($subscription->monthly_amount === null) {
                throw new LogicException('Une souscription à crédit doit définir une mensualité.');
            }

            $totalCents = $this->toCents($subscription->contract_total);
            $monthlyCents = $this->toCents($subscription->monthly_amount);
            $lastAmountCents = $totalCents - ($monthlyCents * ($subscription->duration_months - 1));

            if ($monthlyCents <= 0 || $lastAmountCents <= 0) {
                throw new LogicException('Les conditions financières ne permettent pas un échéancier valide.');
            }

            $startDate = CarbonImmutable::parse($subscription->start_date);

            foreach (range(1, $subscription->duration_months) as $number) {
                $subscription->installments()->create([
                    'installment_number' => $number,
                    'due_date' => $startDate->addMonthsNoOverflow($number),
                    'amount_due' => $this->fromCents($number === $subscription->duration_months ? $lastAmountCents : $monthlyCents),
                    'amount_paid' => '0.00', 'status' => 'upcoming',
                ]);
            }

            return $subscription->installments()->orderBy('installment_number')->get();
        });
    }

    public function refreshStatuses(Subscription $subscription, ?CarbonImmutable $today = null): void
    {
        $today ??= CarbonImmutable::today();

        foreach ($subscription->installments()->get() as $installment) {
            $status = match (true) {
                $this->toCents($installment->balance) === 0 => 'paid',
                $this->toCents($installment->amount_paid) > 0 => 'partially_paid',
                CarbonImmutable::parse($installment->due_date)->lt($today) => 'overdue',
                CarbonImmutable::parse($installment->due_date)->isSameDay($today) => 'due',
                default => 'upcoming',
            };

            if ($installment->status !== $status) {
                $installment->update(['status' => $status, 'paid_at' => $status === 'paid' ? ($installment->paid_at ?? now()) : null]);
            }
        }
    }

    public function syncOverdueStatuses(?CarbonImmutable $today = null): int
    {
        $todayStr = ($today ?? CarbonImmutable::today())->toDateString();

        return Installment::query()
            ->whereDate('due_date', '<', $todayStr)
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->whereNot('status', 'overdue')
            ->update(['status' => 'overdue']);
    }

    private function toCents(string|float|int $amount): int
    {
        $amount = number_format((float) $amount, 2, '.', '');
        [$units, $decimals] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $units * 100) + (int) substr(str_pad($decimals, 2, '0'), 0, 2);
    }

    private function fromCents(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
