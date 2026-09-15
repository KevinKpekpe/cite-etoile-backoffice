<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Contracts\View\View;

class VerifyReceiptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $verificationCode): View
    {
        $receipt = Receipt::query()->where('verification_code', $verificationCode)->firstOrFail();

        return view('receipts.verify', compact('receipt'));
    }
}
