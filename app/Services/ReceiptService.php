<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReceiptService
{
    public function createForPayment(Payment $payment, User $issuer): Receipt
    {
        $existing = Receipt::query()->where('payment_id', $payment->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $payment->load(['customer', 'subscription.plot.avenue.neighborhood', 'subscription.paymentPlan', 'subscription.installments']);
        $receipt = Receipt::query()->create([
            'receipt_number' => 'REC-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'payment_id' => $payment->id, 'customer_id' => $payment->customer_id,
            'subscription_id' => $payment->subscription_id, 'amount' => $payment->amount,
            'issued_at' => now(), 'verification_code' => Str::random(48), 'issued_by' => $issuer->id, 'status' => 'valid',
        ]);
        $receipt->load(['payment', 'customer', 'subscription.plot.avenue.neighborhood', 'subscription.paymentPlan', 'issuedBy']);
        $verificationUrl = route('receipts.verify', $receipt->verification_code);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('receipts.pdf', compact('receipt', 'verificationUrl'))->render());
        $dompdf->setPaper('A4');
        $dompdf->render();
        $path = "receipts/{$receipt->receipt_number}.pdf";
        Storage::disk('local')->put($path, $dompdf->output());
        $receipt->forceFill(['pdf_path' => $path])->save();

        return $receipt;
    }

    public function cancelForPayment(Payment $payment): void
    {
        $payment->receipt()->where('status', 'valid')->update(['status' => 'cancelled']);
    }
}
