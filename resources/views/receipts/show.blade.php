<x-layouts.app :title="'Reçu '.$receipt->receipt_number">
    <div class="mx-auto max-w-sm py-6">

        {{-- Actions rapides --}}
        <div class="mb-6 flex items-center justify-between gap-3 no-print">
            <a href="{{ route('payments.show', $receipt->payment) }}" class="btn btn-outline-secondary btn-sm">
                ← Retour au paiement
            </a>
            <div class="flex gap-2">
                <button onclick="window.print()" type="button" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-printer me-1"></i> Imprimer
                </button>
                @can('receipts.download')
                    <a href="{{ route('receipts.download', $receipt) }}" class="btn btn-app-primary btn-sm">
                        <i class="bi bi-download me-1"></i> PDF Ticket
                    </a>
                @endcan
            </div>
        </div>

        {{-- Ticket de caisse --}}
        <div class="printable-ticket"
             style="font-family:'Courier New',Courier,monospace; font-size:11px; line-height:1.5; color:#111; background:#fafaf8; border:1px solid #ccc; padding:20px 18px; max-width:320px; margin:0 auto; box-shadow:0 4px 24px rgba(0,0,0,0.12);">

            {{-- ═══ HEADER ═══ --}}
            <div style="text-align:center; margin-bottom:6px;">
                <div style="font-size:14px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;">
                    {{ strtoupper($branding['company']) }}
                </div>
                <div style="font-size:10px; text-transform:uppercase; letter-spacing:1px; margin-top:2px;">
                    {{ strtoupper($branding['project']) }}
                </div>
                @if($branding['phone'])
                    <div style="font-size:10px; margin-top:1px;">Tél : {{ $branding['phone'] }}</div>
                @endif
            </div>

            <div style="text-align:center; font-size:9px; margin:5px 0;">--------------------------------</div>

            {{-- ═══ META ═══ --}}
            <table style="width:100%; font-size:10px; border-collapse:collapse;">
                <tr>
                    <td style="padding:1px 0;">REÇU</td>
                    <td style="text-align:right; padding:1px 0;">:{{ $receipt->receipt_number }}</td>
                </tr>
                <tr>
                    <td style="padding:1px 0;">DATE</td>
                    <td style="text-align:right; padding:1px 0;">:{{ $receipt->issued_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding:1px 0;">CLIENT</td>
                    <td style="text-align:right; padding:1px 0; font-weight:bold;">:{{ strtoupper($receipt->customer->last_name) }} {{ $receipt->customer->first_name }}</td>
                </tr>
                <tr>
                    <td style="padding:1px 0;">RÉF. CLIENT</td>
                    <td style="text-align:right; padding:1px 0;">:{{ $receipt->customer->customer_number }}</td>
                </tr>
                @if($receipt->issuedBy)
                <tr>
                    <td style="padding:1px 0;">AGENT</td>
                    <td style="text-align:right; padding:1px 0;">:{{ strtoupper($receipt->issuedBy->last_name) }} {{ $receipt->issuedBy->first_name }}</td>
                </tr>
                @endif
            </table>

            @if($receipt->status !== 'valid')
                <div style="text-align:center; border:1.5px solid #000; font-weight:bold; padding:3px; margin:6px 0; letter-spacing:1px; font-size:11px;">
                    *** REÇU ANNULÉ ***
                </div>
            @endif

            <div style="text-align:center; font-size:9px; margin:5px 0;">--------------------------------</div>

            {{-- ═══ DÉSIGNATION ═══ --}}
            <table style="width:100%; font-size:10px; border-collapse:collapse;">
                <tr>
                    <td style="font-weight:bold; text-transform:uppercase; padding:1px 0;">Désignation</td>
                    <td style="text-align:right; font-weight:bold; text-transform:uppercase; padding:1px 0;">Montant</td>
                </tr>
            </table>

            <div style="text-align:center; font-size:9px; margin:5px 0;">--------------------------------</div>

            <table style="width:100%; font-size:10px; border-collapse:collapse;">
                <tr>
                    <td style="padding:1px 0;">Versement parcelle</td>
                    <td style="text-align:right; font-weight:bold; padding:1px 0;">{{ number_format((float)$receipt->amount, 2) }} {{ $branding['currency'] }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size:9px; color:#444; padding:3px 0 1px 4px;">
                        Parcelle : {{ $receipt->subscription->plot->reference }}
                        ({{ $receipt->subscription->plot->avenue->neighborhood->name }})<br>
                        Formule  : {{ $receipt->subscription->paymentPlan->name }}<br>
                        Réf. Pmt : {{ $receipt->payment->payment_reference }}
                    </td>
                </tr>
            </table>

            <div style="text-align:center; font-size:9px; margin:5px 0;">--------------------------------</div>

            {{-- ═══ TOTAUX ═══ --}}
            <table style="width:100%; font-size:10px; border-collapse:collapse;">
                <tr>
                    <td style="padding:1px 0;">Cumul versé</td>
                    <td style="text-align:right; padding:1px 0;">{{ number_format((float)$receipt->subscription->amount_paid, 2) }} {{ $branding['currency'] }}</td>
                </tr>
                <tr>
                    <td style="padding:1px 0;">Solde restant</td>
                    <td style="text-align:right; padding:1px 0;">{{ number_format((float)$receipt->subscription->balance, 2) }} {{ $branding['currency'] }}</td>
                </tr>
                <tr style="font-weight:bold; font-size:12px;">
                    <td style="padding:2px 0;">TOTAL ENCAISSÉ</td>
                    <td style="text-align:right; padding:2px 0;">{{ number_format((float)$receipt->amount, 2) }} {{ $branding['currency'] }}</td>
                </tr>
            </table>

            <div style="text-align:center; font-size:9px; margin:5px 0;">--------------------------------</div>

            {{-- ═══ MODE DE RÈGLEMENT ═══ --}}
            <table style="width:100%; font-size:10px; border-collapse:collapse;">
                <tr>
                    <td style="text-transform:uppercase; font-weight:bold; padding:1px 0;">{{ $receipt->payment->payment_method }}</td>
                    <td style="text-align:right; padding:1px 0;">{{ number_format((float)$receipt->amount, 2) }} {{ $branding['currency'] }}</td>
                </tr>
                @if($receipt->payment->transaction_reference)
                <tr>
                    <td style="font-size:9px; color:#555; padding:1px 0;">Réf. Trans.</td>
                    <td style="text-align:right; font-size:9px; color:#555; padding:1px 0;">{{ $receipt->payment->transaction_reference }}</td>
                </tr>
                @endif
            </table>

            <div style="text-align:center; font-size:9px; margin:5px 0;">--------------------------------</div>

            {{-- ═══ PROCHAIN PAIEMENT ═══ --}}
            @php
                $nextInstallment = $receipt->subscription?->installments
                    ->whereIn('status', ['overdue', 'due', 'upcoming'])
                    ->sortBy('due_date')
                    ->first();
            @endphp

            @if($nextInstallment && (float)$receipt->subscription->balance > 0)
                <table style="width:100%; font-size:10px; border-collapse:collapse;">
                    <tr>
                        <td colspan="2" style="font-weight:bold; padding:1px 0;">PROCHAIN PAIEMENT :</td>
                    </tr>
                    <tr>
                        <td style="padding:1px 0;">Échéance : {{ \Carbon\Carbon::parse($nextInstallment->due_date)->format('d/m/Y') }}</td>
                        <td style="text-align:right; font-weight:bold; padding:1px 0;">{{ number_format((float)$nextInstallment->amount_due, 2) }} {{ $branding['currency'] }}</td>
                    </tr>
                </table>
            @else
                <div style="text-align:center; font-weight:bold; font-size:10px; margin:4px 0;">
                    *** SOUSCRIPTION SOLDÉE ***
                </div>
            @endif

            <div style="text-align:center; font-size:9px; margin:6px 0;">================================</div>

            {{-- ═══ SÉCURITÉ ═══ --}}
            <div style="text-align:center; font-size:8px; word-break:break-all; color:#666; margin:4px 0; line-height:1.4;">
                Code : {{ substr($receipt->verification_code, 0, 20) }}...<br>
                Vérif : {{ route('receipts.verify', $receipt->verification_code) }}
            </div>

            <div style="text-align:center; font-size:9px; margin:5px 0;">================================</div>

            {{-- ═══ FOOTER ═══ --}}
            <div style="text-align:center; font-size:10px; letter-spacing:3px; text-transform:uppercase; margin-top:6px; line-height:2;">
                M E R C I<br>
                B O N N E &nbsp; J O U R N É E
            </div>

        </div>
    </div>

    <style>
        @media print {
            .no-print, header, nav, footer, aside { display: none !important; }
            body { background: white !important; }
            .printable-ticket {
                box-shadow: none !important;
                border: none !important;
                max-width: 80mm !important;
                padding: 4px !important;
                margin: 0 auto !important;
            }
        }
    </style>
</x-layouts.app>
