<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $receipts = $request->user()->customer()->firstOrFail()->receipts()
            ->with(['payment', 'subscription.plot'])->latest('issued_at')->paginate(20);

        return view('portal.receipts.index', compact('receipts'));
    }

    public function download(Request $request, int $receipt): StreamedResponse
    {
        $receipt = $request->user()->customer()->firstOrFail()->receipts()->findOrFail($receipt);
        abort_if($receipt->pdf_path === null || ! Storage::disk('local')->exists($receipt->pdf_path), 404);
        AuditLog::query()->create([
            'user_id' => $request->user()->id, 'action' => 'portal.receipt.downloaded', 'entity_type' => $receipt::class,
            'entity_id' => $receipt->id, 'new_values' => ['receipt_number' => $receipt->receipt_number],
            'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);

        return Storage::disk('local')->download($receipt->pdf_path, $receipt->receipt_number.'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
