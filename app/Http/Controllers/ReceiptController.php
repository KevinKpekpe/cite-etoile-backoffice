<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt): View
    {
        $receipt->load(['payment', 'customer', 'subscription.plot.avenue.neighborhood', 'subscription.paymentPlan', 'issuedBy']);

        return view('receipts.show', compact('receipt'));
    }

    public function download(Request $request, Receipt $receipt): StreamedResponse
    {
        abort_if($receipt->pdf_path === null || ! Storage::disk('local')->exists($receipt->pdf_path), 404);
        AuditLog::query()->create(['user_id' => $request->user()?->id, 'action' => 'receipt.reprinted', 'entity_type' => Receipt::class, 'entity_id' => $receipt->id, 'new_values' => ['receipt_number' => $receipt->receipt_number], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return Storage::disk('local')->download($receipt->pdf_path, $receipt->receipt_number.'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
