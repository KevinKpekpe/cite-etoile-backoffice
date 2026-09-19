<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page {
        margin: 8px 6px;
    }
    body {
        font-family: "DejaVu Sans Mono", "Courier New", Courier, monospace;
        color: #000000;
        font-size: 9px;
        line-height: 1.4;
        margin: 0;
        padding: 0;
        background-color: #ffffff;
    }

    /* ── Layout helpers ── */
    .center   { text-align: center; }
    .right    { text-align: right; }
    .bold     { font-weight: bold; }
    .upper    { text-transform: uppercase; }
    .small    { font-size: 7.5px; }
    .mt2      { margin-top: 2px; }
    .mt4      { margin-top: 4px; }
    .mt6      { margin-top: 6px; }

    /* ── Header ── */
    .shop-title {
        font-size: 13px;
        font-weight: bold;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .shop-subtitle {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ── Dividers ── */
    .sep-dash {
        font-size: 8px;
        letter-spacing: 0.5px;
        color: #000;
        margin: 4px 0;
        line-height: 1;
    }
    .sep-eq {
        font-size: 8px;
        letter-spacing: 0.5px;
        color: #000;
        margin: 4px 0;
        line-height: 1;
    }

    /* ── Meta info block ── */
    .meta-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5px;
    }
    .meta-table td {
        padding: 0.5px 0;
        vertical-align: top;
    }

    /* ── Item rows ── */
    .item-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5px;
    }
    .item-table td {
        padding: 1px 0;
        vertical-align: top;
    }
    .item-name  { width: 70%; }
    .item-price { width: 30%; text-align: right; }

    /* ── Totals ── */
    .total-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5px;
    }
    .total-table td {
        padding: 1px 0;
        vertical-align: top;
    }
    .total-row td {
        font-weight: bold;
        font-size: 9.5px;
    }

    /* ── Status stamp ── */
    .cancelled-stamp {
        font-size: 10px;
        font-weight: bold;
        text-align: center;
        border: 1.5px solid #000;
        padding: 2px 4px;
        margin: 4px 0;
        letter-spacing: 1px;
    }

    /* ── Footer ── */
    .footer {
        font-size: 8.5px;
        text-align: center;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-top: 2px;
        line-height: 1.6;
    }
    .security {
        font-size: 7px;
        word-break: break-all;
        text-align: center;
        margin-top: 4px;
    }
</style>
</head>
<body>

{{-- ══════════════ HEADER ══════════════ --}}
<div class="center">
    <div class="shop-title">{{ strtoupper($branding['company']) }}</div>
    <div class="shop-subtitle">{{ strtoupper($branding['project']) }}</div>
    @if($branding['phone'])
        <div class="shop-subtitle">Tél : {{ $branding['phone'] }}</div>
    @endif
</div>

<div class="sep-dash center">--------------------------------</div>

{{-- ══════════════ META ══════════════ --}}
<table class="meta-table">
    <tr>
        <td>REÇU</td>
        <td class="right">:{{ $receipt->receipt_number }}</td>
    </tr>
    <tr>
        <td>DATE</td>
        <td class="right">:{{ $receipt->issued_at->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td>CLIENT</td>
        <td class="right">:{{ strtoupper($receipt->customer->last_name) }} {{ $receipt->customer->first_name }}</td>
    </tr>
    <tr>
        <td>RÉF. CLIENT</td>
        <td class="right">:{{ $receipt->customer->customer_number }}</td>
    </tr>
    @if($receipt->issuedBy)
    <tr>
        <td>AGENT</td>
        <td class="right">:{{ strtoupper($receipt->issuedBy->last_name) }} {{ $receipt->issuedBy->first_name }}</td>
    </tr>
    @endif
</table>

@if($receipt->status !== 'valid')
<div class="cancelled-stamp">*** REÇU ANNULÉ ***</div>
@endif

<div class="sep-dash center">--------------------------------</div>

{{-- ══════════════ ITEMS ══════════════ --}}
<table class="item-table">
    <tr>
        <td class="item-name bold upper">Désignation</td>
        <td class="item-price bold upper">Montant</td>
    </tr>
</table>

<div class="sep-dash center">--------------------------------</div>

<table class="item-table">
    <tr>
        <td class="item-name">Versement parcelle</td>
        <td class="item-price bold">{{ number_format((float)$receipt->amount, 2) }} {{ $branding['currency'] }}</td>
    </tr>
    <tr>
        <td colspan="2" class="small mt2" style="padding-left:2px; color:#111;">
            Parcelle : {{ $receipt->subscription->plot->reference }}
            ({{ $receipt->subscription->plot->avenue->neighborhood->name }})<br>
            Formule  : {{ $receipt->subscription->paymentPlan->name }}<br>
            Réf. Pmt : {{ $receipt->payment->payment_reference }}
        </td>
    </tr>
</table>

<div class="sep-dash center">--------------------------------</div>

{{-- ══════════════ TOTAUX ══════════════ --}}
<table class="total-table">
    <tr>
        <td>Cumul versé</td>
        <td class="right">{{ number_format((float)$receipt->subscription->amount_paid, 2) }} {{ $branding['currency'] }}</td>
    </tr>
    <tr>
        <td>Solde restant</td>
        <td class="right">{{ number_format((float)$receipt->subscription->balance, 2) }} {{ $branding['currency'] }}</td>
    </tr>
    <tr class="total-row">
        <td>TOTAL ENCAISSÉ</td>
        <td class="right">{{ number_format((float)$receipt->amount, 2) }} {{ $branding['currency'] }}</td>
    </tr>
</table>

<div class="sep-dash center">--------------------------------</div>

{{-- ══════════════ PAIEMENT ══════════════ --}}
<table class="total-table">
    <tr>
        <td class="upper">{{ $receipt->payment->payment_method }}</td>
        <td class="right">{{ number_format((float)$receipt->amount, 2) }} {{ $branding['currency'] }}</td>
    </tr>
    @if($receipt->payment->transaction_reference)
    <tr>
        <td class="small" style="color:#333;">Réf. Trans.</td>
        <td class="right small" style="color:#333;">{{ $receipt->payment->transaction_reference }}</td>
    </tr>
    @endif
</table>

<div class="sep-dash center">--------------------------------</div>

{{-- ══════════════ PROCHAIN PAIEMENT ══════════════ --}}
@if($nextInstallment)
<table class="total-table">
    <tr>
        <td class="bold">PROCHAIN PAIEMENT :</td>
    </tr>
    <tr>
        <td>Échéance : {{ \Carbon\Carbon::parse($nextInstallment->due_date)->format('d/m/Y') }}</td>
        <td class="right bold">{{ number_format((float)$nextInstallment->amount_due, 2) }} {{ $branding['currency'] }}</td>
    </tr>
</table>
@else
<div class="center bold mt4">*** SOUSCRIPTION SOLDÉE ***</div>
@endif

<div class="sep-eq center">================================</div>

{{-- ══════════════ FOOTER ══════════════ --}}
<div class="security">
    Code : {{ substr($receipt->verification_code, 0, 20) }}...<br>
    Vérif : {{ $verificationUrl }}
</div>

<div class="sep-eq center">================================</div>

<div class="footer">
    M E R C I<br>
    B O N N E  J O U R N É E
</div>

</body>
</html>
