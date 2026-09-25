<?php

namespace App\Console\Commands;

use App\Models\Receipt;
use App\Services\SettingService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegenerateReceiptPdfs extends Command
{
    protected $signature = 'receipts:regenerate {--ids= : Comma-separated list of receipt IDs to regenerate (default: all)}';

    protected $description = 'Regenerate all receipt PDFs with the current template';

    public function handle(SettingService $settings): int
    {
        $query = Receipt::query()->with([
            'payment',
            'customer',
            'subscription.plot.avenue.neighborhood',
            'subscription.paymentPlan',
            'subscription.installments',
            'issuedBy',
        ]);

        if ($this->option('ids')) {
            $ids = collect(explode(',', $this->option('ids')))->map('trim')->filter()->all();
            $query->whereIn('id', $ids);
        }

        $receipts = $query->get();

        if ($receipts->isEmpty()) {
            $this->warn('No receipts found.');

            return self::SUCCESS;
        }

        $this->info("Regenerating {$receipts->count()} receipt PDF(s)...");

        $branding = [
            'company' => $settings->value('company', 'name', 'MJIC IMMOBILIER SARL'),
            'project' => $settings->value('project', 'name', 'Cité Étoile du Monde'),
            'currency' => $settings->value('finance', 'currency', 'USD'),
            'phone' => $settings->value('company', 'phone', ''),
        ];

        $bar = $this->output->createProgressBar($receipts->count());
        $bar->start();

        $regenerated = 0;
        $failed = 0;

        foreach ($receipts as $receipt) {
            try {
                $nextInstallment = $receipt->subscription?->installments
                    ->whereIn('status', ['overdue', 'due', 'upcoming'])
                    ->sortBy('due_date')
                    ->first();

                $verificationUrl = route('receipts.verify', $receipt->verification_code);

                $options = new Options;
                $options->set('isRemoteEnabled', false);
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml(view('receipts.pdf', compact('receipt', 'verificationUrl', 'branding', 'nextInstallment'))->render());
                $dompdf->setPaper([0, 0, 226.77, 680], 'portrait');
                $dompdf->render();

                $path = "receipts/{$receipt->receipt_number}.pdf";
                Storage::disk('local')->put($path, $dompdf->output());

                if ($receipt->pdf_path !== $path) {
                    $receipt->forceFill(['pdf_path' => $path])->save();
                }

                $regenerated++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("Failed to regenerate {$receipt->receipt_number}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done — {$regenerated} regenerated, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
