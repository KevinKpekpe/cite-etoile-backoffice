<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\AncillaryFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AncillaryFeeController extends Controller
{
    public function realizeSurvey(Request $request, Subscription $subscription, AncillaryFeeService $fees): RedirectResponse
    {
        $fees->realizeSurvey($subscription, $request->user());

        return back()->with('status', __('Bornage enregistré comme réalisé. Un frais de 50 USD a été créé.'));
    }
}
