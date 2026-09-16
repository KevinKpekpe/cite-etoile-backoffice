<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\User;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportRequest $request, ReportService $reports): View
    {
        $filters = $request->validated();

        return view('reports.index', [
            ...$reports->generate($filters), 'filters' => $filters,
            'plans' => PaymentPlan::query()->orderBy('name')->get(['id', 'name']),
            'agents' => User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'cashier', 'finance_manager']))->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function export(ReportRequest $request, ReportService $reports, string $report): StreamedResponse
    {
        abort_unless(in_array($report, ['customers', 'plots', 'payments', 'overdue'], true), 404);
        $rows = $reports->rows($reports->generate($request->validated()), $report);

        return response()->streamDownload(function () use ($rows, $report): void {
            $stream = fopen('php://output', 'w');
            abort_if($stream === false, 500);
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $this->headings($report), ';');
            foreach ($rows as $row) {
                fputcsv($stream, $this->csvRow($report, $row), ';');
            }
            fclose($stream);
        }, "rapport-{$report}-".now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<int, string> */
    private function headings(string $report): array
    {
        return match ($report) {
            'customers' => ['Numéro', 'Nom', 'Téléphone', 'Statut', 'Créé le'],
            'plots' => ['Référence', 'Quartier', 'Avenue', 'Surface', 'Statut'],
            'payments' => ['Référence', 'Date', 'Client', 'Formule', 'Agent', 'Montant', 'Devise'],
            default => ['Client', 'Parcelle', 'Échéance', 'Jours de retard', 'Solde'],
        };
    }

    /** @return array<int, int|string|null> */
    private function csvRow(string $report, Customer|Plot|Payment|Installment $row): array
    {
        return match (true) {
            $report === 'customers' && $row instanceof Customer => [$row->customer_number, "{$row->first_name} {$row->last_name}", $row->phone, $row->status, $row->created_at?->format('d/m/Y')],
            $report === 'plots' && $row instanceof Plot => [$row->reference, $row->avenue->neighborhood->name, $row->avenue->name, $row->surface_area, $row->commercial_status],
            $report === 'payments' && $row instanceof Payment => [$row->payment_reference, CarbonImmutable::parse($row->payment_date)->format('d/m/Y'), "{$row->customer->first_name} {$row->customer->last_name}", $row->subscription->paymentPlan->name, $row->receivedBy ? "{$row->receivedBy->first_name} {$row->receivedBy->last_name}" : '', $row->amount, $row->currency],
            $report === 'overdue' && $row instanceof Installment => ["{$row->subscription->customer->first_name} {$row->subscription->customer->last_name}", $row->subscription->plot->reference, CarbonImmutable::parse($row->due_date)->format('d/m/Y'), CarbonImmutable::parse($row->due_date)->diffInDays(now()), $row->balance],
            default => throw new LogicException('Type de rapport incohérent.'),
        };
    }
}
