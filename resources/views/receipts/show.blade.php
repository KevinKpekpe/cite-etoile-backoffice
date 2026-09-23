<x-layouts.app :title="'Reçu '.$receipt->receipt_number">
    @php
        $nextInstallment = $receipt->subscription?->installments
            ->whereIn('status', ['overdue', 'due', 'upcoming'])
            ->sortBy('due_date')
            ->first();
    @endphp

    <div class="receipt-page">
        <header class="resource-heading no-print">
            <div>
                <p class="app-kicker">Justificatif d’encaissement</p>
                <h1 class="resource-heading__title">Reçu {{ $receipt->receipt_number }}</h1>
                <p class="resource-heading__description">Émis le {{ $receipt->issued_at->format('d/m/Y à H:i') }} pour {{ $receipt->customer->first_name }} {{ $receipt->customer->last_name }}.</p>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('payments.show', $receipt->payment) }}" class="btn btn-outline-secondary resource-button">Retour au paiement</a>
                <button onclick="window.print()" type="button" class="btn btn-outline-secondary resource-button"><i class="bi bi-printer me-1"></i> Imprimer</button>
                @can('receipts.download')
                    <a href="{{ route('receipts.download', $receipt) }}" class="btn btn-app-primary resource-button"><i class="bi bi-download me-1"></i> Télécharger le PDF</a>
                @endcan
            </div>
        </header>

        <article class="receipt-document printable-ticket">
            <header class="receipt-document__header">
                <p class="receipt-document__company">{{ strtoupper($branding['company']) }}</p>
                <p class="receipt-document__project">{{ strtoupper($branding['project']) }}</p>
                @if($branding['phone'])<p>{{ $branding['phone'] }}</p>@endif
            </header>

            <div class="receipt-document__title">
                <span>Reçu de paiement</span>
                <strong>{{ $receipt->receipt_number }}</strong>
            </div>

            @if($receipt->status !== 'valid')
                <div class="receipt-document__cancelled">Reçu annulé</div>
            @endif

            <section class="receipt-document__section">
                <h2>Informations générales</h2>
                <dl class="receipt-document__list">
                    <div><dt>Date d’émission</dt><dd>{{ $receipt->issued_at->format('d/m/Y H:i') }}</dd></div>
                    <div><dt>Client</dt><dd>{{ $receipt->customer->last_name }} {{ $receipt->customer->first_name }}</dd></div>
                    <div><dt>Référence client</dt><dd>{{ $receipt->customer->customer_number }}</dd></div>
                    @if($receipt->issuedBy)
                        <div><dt>Agent</dt><dd>{{ $receipt->issuedBy->last_name }} {{ $receipt->issuedBy->first_name }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="receipt-document__section">
                <h2>Opération</h2>
                <dl class="receipt-document__list">
                    <div><dt>Référence paiement</dt><dd>{{ $receipt->payment->payment_reference }}</dd></div>
                    <div><dt>Parcelle</dt><dd>{{ $receipt->subscription->plot->reference }} · {{ $receipt->subscription->plot->avenue->neighborhood->name }}</dd></div>
                    <div><dt>Formule</dt><dd>{{ $receipt->subscription->paymentPlan->name }}</dd></div>
                    <div><dt>Mode de règlement</dt><dd>{{ ucfirst(str_replace('_', ' ', $receipt->payment->payment_method)) }}</dd></div>
                    @if($receipt->payment->transaction_reference)
                        <div><dt>Référence externe</dt><dd>{{ $receipt->payment->transaction_reference }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="receipt-document__amount">
                <span>Montant encaissé</span>
                <strong>{{ number_format((float) $receipt->amount, 2, ',', ' ') }} {{ $branding['currency'] }}</strong>
            </section>

            <section class="receipt-document__section">
                <h2>Situation de la souscription</h2>
                <dl class="receipt-document__list">
                    <div><dt>Cumul versé</dt><dd>{{ number_format((float) $receipt->subscription->amount_paid, 2, ',', ' ') }} {{ $branding['currency'] }}</dd></div>
                    <div><dt>Solde restant</dt><dd>{{ number_format((float) $receipt->subscription->balance, 2, ',', ' ') }} {{ $branding['currency'] }}</dd></div>
                    @if($nextInstallment && (float) $receipt->subscription->balance > 0)
                        <div><dt>Prochaine échéance</dt><dd>{{ $nextInstallment->due_date->format('d/m/Y') }} · {{ number_format((float) $nextInstallment->amount_due, 2, ',', ' ') }} {{ $branding['currency'] }}</dd></div>
                    @else
                        <div><dt>Situation</dt><dd>Souscription soldée</dd></div>
                    @endif
                </dl>
            </section>

            <footer class="receipt-document__footer">
                <p>Code de vérification</p>
                <strong>{{ $receipt->verification_code }}</strong>
                <span>{{ route('receipts.verify', $receipt->verification_code) }}</span>
                <small>Merci pour votre confiance.</small>
            </footer>
        </article>
    </div>
</x-layouts.app>
