<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerStatementController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Customer $customer): Response
    {
        $customer->load([
            'subscriptions' => fn ($query) => $query->with(['plot.avenue.neighborhood', 'paymentPlan', 'installments'])->latest(),
            'payments' => fn ($query) => $query->where('status', 'validated')->latest('payment_date'),
        ]);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.customer-statement', compact('customer'))->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="situation-'.$customer->customer_number.'.pdf"',
        ]);
    }
}
