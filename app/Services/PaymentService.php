<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private InstallmentScheduleService $scheduleService, private ReceiptService $receiptService, private AuditService $auditService, private SettingService $settings, private ReferenceGenerator $references) {}

    /** @param array<string, mixed> $data */
    public function record(Subscription $subscription, User $user, array $data): Payment
    {
        return DB::transaction(function () use ($subscription, $user, $data): Payment {
            $existing = Payment::query()->where('idempotency_key', $data['idempotency_key'])->first();

            if ($existing !== null) {
                return $existing;
            }

            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $amountCents = $this->toCents($data['amount']);

            if ($amountCents > $this->toCents($subscription->balance)) {
                throw ValidationException::withMessages(['amount' => __('Le montant dépasse le solde de la souscription.')]);
            }

            if (! $this->settings->boolean('subscription', 'allow_partial_payment', true)) {
                $this->ensurePaymentIsNotPartial($subscription, $amountCents);
            }

            if (! $this->settings->boolean('subscription', 'allow_advance_payment', true)) {
                $dueCents = $subscription->installments()->whereDate('due_date', '<=', now())->get()->sum(fn ($installment): int => $this->toCents($installment->balance));
                if ($subscription->duration_months > 0 && $amountCents > $dueCents) {
                    throw ValidationException::withMessages(['amount' => __('Les paiements anticipés sont désactivés.')]);
                }
            }

            if ($subscription->duration_months > 0) {
                $this->scheduleService->generate($subscription);
            }

            $payment = Payment::query()->create([
                ...$data, 'payment_reference' => $this->references->generate(Payment::class, 'payment_reference', 'payment', 'PAY'),
                'customer_id' => $subscription->customer_id, 'subscription_id' => $subscription->id,
                'status' => 'validated', 'received_by' => $user->id,
            ]);

            $remainingCents = $amountCents;
            $installments = $subscription->installments()->whereRaw('amount_paid < amount_due')->orderBy('due_date')->orderBy('installment_number')->lockForUpdate()->get();

            foreach ($installments as $installment) {
                if ($remainingCents === 0) {
                    break;
                }

                $allocationCents = min($remainingCents, $this->toCents($installment->balance));
                $payment->allocations()->create(['installment_id' => $installment->id, 'amount' => $this->fromCents($allocationCents)]);
                $installment->update(['amount_paid' => $this->fromCents($this->toCents($installment->amount_paid) + $allocationCents)]);
                $remainingCents -= $allocationCents;
            }

            if ($subscription->duration_months > 0 && $remainingCents > 0) {
                throw ValidationException::withMessages(['amount' => __('Le montant ne peut pas être entièrement affecté à l’échéancier.')]);
            }

            $this->recalculate($subscription);
            $this->scheduleService->refreshStatuses($subscription);

            // Premier paiement sur une souscription en attente : activation automatique.
            if ($subscription->commercial_status === 'pending') {
                $subscription->update(['commercial_status' => 'active']);
                $subscription->plot()->update(['commercial_status' => 'subscribed']);
                $this->auditService->record($user, 'subscription.activated', $subscription, ['commercial_status' => 'pending'], ['commercial_status' => 'active']);
            }

            $this->receiptService->createForPayment($payment, $user);
            $this->auditService->record($user, 'payment.created', $payment, null, ['status' => $payment->status, 'amount' => $payment->amount, 'subscription_id' => $subscription->id]);

            return $payment->load('allocations.installment');
        }, 3);
    }

    public function reverse(Payment $payment, User $user, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $user, $reason): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== 'validated') {
                throw ValidationException::withMessages(['reason' => __('Seul un paiement validé peut être extourné.')]);
            }

            $subscription = Subscription::query()->lockForUpdate()->findOrFail($payment->subscription_id);

            foreach ($payment->allocations()->with('installment')->lockForUpdate()->get() as $allocation) {
                $newPaidCents = $this->toCents($allocation->installment->amount_paid) - $this->toCents($allocation->amount);
                $allocation->installment->update(['amount_paid' => $this->fromCents($newPaidCents), 'paid_at' => null]);
            }

            $oldValues = $payment->only(['status', 'reversal_reason', 'reversed_by', 'reversed_at']);
            $payment->update(['status' => 'reversed', 'reversal_reason' => $reason, 'reversed_by' => $user->id, 'reversed_at' => now()]);
            $this->receiptService->cancelForPayment($payment);
            $this->recalculate($subscription);
            $this->scheduleService->refreshStatuses($subscription);
            $this->auditService->record($user, 'payment.reversed', $payment, $oldValues, $payment->only(['status', 'reversal_reason', 'reversed_by', 'reversed_at']));

            return $payment->refresh();
        }, 3);
    }

    private function recalculate(Subscription $subscription): void
    {
        $paid = Payment::query()->where('subscription_id', $subscription->id)->where('status', 'validated')->sum('amount');
        $paidCents = $this->toCents($paid);
        $totalCents = $this->toCents($subscription->contract_total);
        $status = match (true) {
            $paidCents === 0 => 'unpaid', $paidCents >= $totalCents => 'paid', default => 'partially_paid',
        };

        DB::table('subscriptions')->where('id', $subscription->id)->update(['amount_paid' => $this->fromCents($paidCents), 'financial_status' => $status]);
        $subscription->plot()->update(['financial_status' => $status === 'paid' ? 'paid' : $status]);
        $subscription->refresh();
    }

    private function ensurePaymentIsNotPartial(Subscription $subscription, int $amountCents): void
    {
        if ($subscription->duration_months === 0 && $amountCents !== $this->toCents($subscription->balance)) {
            throw ValidationException::withMessages(['amount' => __('Les paiements partiels sont désactivés.')]);
        }

        $remaining = $amountCents;
        foreach ($subscription->installments()->whereRaw('amount_paid < amount_due')->orderBy('due_date')->get() as $installment) {
            $balance = $this->toCents($installment->balance);
            if ($remaining < $balance) {
                throw ValidationException::withMessages(['amount' => __('Les paiements partiels sont désactivés.')]);
            }
            $remaining -= $balance;
            if ($remaining === 0) {
                return;
            }
        }
    }

    private function toCents(string|float|int $amount): int
    {
        $amount = number_format((float) $amount, 2, '.', '');
        [$units, $decimals] = explode('.', $amount);

        return ((int) $units * 100) + (int) $decimals;
    }

    private function fromCents(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
