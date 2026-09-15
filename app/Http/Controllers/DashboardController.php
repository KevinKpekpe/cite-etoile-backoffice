<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Services\DashboardMetricsService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DashboardRequest $request, DashboardMetricsService $metricsService): View
    {
        $period = $request->string('period', 'month')->toString();

        return view('dashboard', $metricsService->metrics($period, $request->user()->can('reports.view')));
    }
}
