<?php

namespace App\Services;

use App\Models\AncillaryFee;
use App\Models\AncillaryFeeType;
use App\Models\Contract;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

class AncillaryFeeService
{
    public function __construct(private AuditService $auditService) {}

    public function createForSignedContract(Contract $contract): void
    {
        DB::transaction(function () use ($contract): void {
            if ($contract->status !== 'signed' || $contract->signed_at === null) {
                return;
            }

            $subscription = Subscription::query()->lockForUpdate()->findOrFail($contract->subscription_id);
            $signatureDate = CarbonImmutable::parse($contract->signed_at);
            $cadastralDueDate = $this->cadastralDueDate($subscription);

            foreach (['cadastral_number', 'occupancy_certificate', 'registration_certificate'] as $typeCode) {
                $type = AncillaryFeeType::query()->where('code', $typeCode)->firstOrFail();
                $this->createFeeOnce($subscription, $type, 1, $cadastralDueDate, $type->default_amount);
            }

            $development = $this->developmentTerms($subscription);
            $developmentType = AncillaryFeeType::query()->where('code', 'development')->firstOrFail();
            $firstDevelopmentDueDate = $signatureDate->addMonthsNoOverflow(1);
            $installmentCount = $subscription->development_payment_mode === 'monthly' ? 36 : 1;

            for ($number = 1; $number <= $installmentCount; $number++) {
                $dueDate = $subscription->development_payment_mode === 'monthly'
                    ? $signatureDate->addMonthsNoOverflow($number)
                    : $firstDevelopmentDueDate;
                $amount = $subscription->development_payment_mode === 'monthly'
                    ? $development['monthly']
                    : $development['total'];

                $this->createFeeOnce($subscription, $developmentType, $number, $dueDate, $amount);
            }
        });
    }

    public function realizeSurvey(Subscription $subscription, User $user): AncillaryFee
    {
        return DB::transaction(function () use ($subscription, $user): AncillaryFee {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $existing = $subscription->ancillaryFees()->where('fee_type', 'survey')->first();

            if ($existing !== null) {
                return $existing;
            }

            $realizedAt = now();
            $type = AncillaryFeeType::query()->where('code', 'survey')->firstOrFail();
            $fee = $subscription->ancillaryFees()->create([
                'fee_type' => $type->code,
                'fee_label' => $type->name,
                'installment_number' => 1,
                'due_date' => $realizedAt->toDateString(),
                'amount_due' => $type->default_amount,
                'amount_paid' => '0.00',
                'realized_at' => $realizedAt,
                'status' => 'due',
            ]);

            $this->auditService->record($user, 'ancillary_fee.survey_realized', $fee, null, [
                'subscription_id' => $subscription->id,
                'realized_at' => $realizedAt->toDateTimeString(),
            ]);

            return $fee;
        }, 3);
    }

    public function refreshStatus(AncillaryFee $fee): void
    {
        $fee->refresh();
        $today = CarbonImmutable::today();
        $dueDate = CarbonImmutable::parse($fee->due_date);
        $status = match (true) {
            $this->toCents($fee->balance) === 0 => 'paid',
            $this->toCents($fee->amount_paid) > 0 => 'partially_paid',
            $dueDate->lt($today) => 'overdue',
            $dueDate->isSameDay($today) => 'due',
            default => 'upcoming',
        };

        if ($fee->status !== $status || ($status === 'paid' && $fee->paid_at === null)) {
            $fee->update([
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($fee->paid_at ?? now()) : null,
            ]);
        }
    }

    public function syncOverdueStatuses(?CarbonImmutable $today = null): int
    {
        $todayDate = ($today ?? CarbonImmutable::today())->toDateString();

        return AncillaryFee::query()
            ->whereDate('due_date', '<', $todayDate)
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->where('amount_paid', 0)
            ->whereNot('status', 'overdue')
            ->update(['status' => 'overdue']);
    }

    /** @return array{total: string, monthly: string} */
    private function developmentTerms(Subscription $subscription): array
    {
        $option = match ($subscription->duration_months) {
            0 => 'cash',
            12 => 'one_year',
            36 => 'three_years',
            60 => 'five_years',
            120 => 'ten_years',
            default => throw new LogicException('La formule ne définit pas de frais d’aménagement.'),
        };
        $pricing = AncillaryFeeType::query()->where('code', 'development')->value('pricing_options');

        if (is_string($pricing)) {
            $pricing = json_decode($pricing, true, flags: JSON_THROW_ON_ERROR);
        }

        if (! is_array($pricing) || ! isset($pricing[$option]['total'], $pricing[$option]['monthly'])) {
            throw new LogicException('Les montants d’aménagement de cette formule ne sont pas configurés.');
        }

        return $pricing[$option];
    }

    private function cadastralDueDate(Subscription $subscription): CarbonImmutable
    {
        if ($subscription->duration_months === 0) {
            return CarbonImmutable::parse($subscription->subscription_date);
        }

        if ($subscription->expected_end_date === null) {
            throw new LogicException('La souscription doit définir une date de fin pour les échéances cadastrales.');
        }

        $monthsBeforeEnd = match ($subscription->duration_months) {
            12 => 3,
            36 => 6,
            60 => 12,
            120 => 24,
            default => throw new LogicException('La formule ne définit pas d’échéance cadastrale.'),
        };

        return CarbonImmutable::parse($subscription->expected_end_date)->subMonthsNoOverflow($monthsBeforeEnd);
    }

    private function createFeeOnce(Subscription $subscription, AncillaryFeeType $type, int $number, CarbonImmutable $dueDate, string $amount): AncillaryFee
    {
        return $subscription->ancillaryFees()->firstOrCreate(
            ['fee_type' => $type->code, 'installment_number' => $number],
            [
                'fee_label' => $type->name,
                'due_date' => $dueDate->toDateString(),
                'amount_due' => $amount,
                'amount_paid' => '0.00',
                'status' => match (true) {
                    $dueDate->lt(CarbonImmutable::today()) => 'overdue',
                    $dueDate->isSameDay(CarbonImmutable::today()) => 'due',
                    default => 'upcoming',
                },
            ],
        );
    }

    private function toCents(string|float|int $amount): int
    {
        $amount = number_format((float) $amount, 2, '.', '');
        [$units, $decimals] = explode('.', $amount);

        return ((int) $units * 100) + (int) $decimals;
    }
}
