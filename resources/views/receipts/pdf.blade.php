<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#0f172a;font-size:12px}
.header{text-align:center;border-bottom:2px solid #d97706;padding-bottom:18px}
.title{font-size:22px;font-weight:bold}
.subtitle{font-size:13px;color:#92400e;margin-top:4px}
.amount-box{text-align:center;margin:22px 0;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:16px}
.amount-label{font-size:11px;color:#92400e;text-transform:uppercase;letter-spacing:1px}
.amount-value{font-size:30px;font-weight:bold;margin-top:4px}
.grid{width:100%;margin-top:18px;border-collapse:collapse}
.grid td{padding:7px 8px;border-bottom:1px solid #e2e8f0}
.grid tr:last-child td{border-bottom:none}
.label{color:#64748b;width:38%}
.next-box{margin-top:20px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:12px}
.next-title{font-weight:bold;color:#1e40af;margin-bottom:6px}
.footer{margin-top:28px;font-size:9px;color:#475569;word-break:break-all;border-top:1px solid #e2e8f0;padding-top:12px}
</style></head><body>

<div class="header">
    <strong>{{ $branding['company'] }}</strong>
    <div class="title">{{ $branding['project'] }}</div>
    <div class="subtitle">Reçu de paiement N° {{ $receipt->receipt_number }}</div>
</div>

<div class="amount-box">
    <div class="amount-label">Montant encaissé</div>
    <div class="amount-value">{{ $receipt->amount }} {{ $branding['currency'] }}</div>
</div>

<table class="grid">
    <tr><td class="label">Date &amp; heure</td><td>{{ $receipt->issued_at->format('d/m/Y H:i:s') }}</td></tr>
    <tr><td class="label">Client</td><td>{{ $receipt->customer->first_name }} {{ $receipt->customer->last_name }} ({{ $receipt->customer->customer_number }})</td></tr>
    <tr><td class="label">Parcelle</td><td>{{ $receipt->subscription->plot->reference }} · {{ $receipt->subscription->plot->avenue->neighborhood->name }}</td></tr>
    <tr><td class="label">Formule</td><td>{{ $receipt->subscription->paymentPlan->name }}</td></tr>
    <tr><td class="label">Mode de paiement</td><td>{{ $receipt->payment->payment_method }}</td></tr>
    <tr><td class="label">Cumul total versé</td><td><strong>{{ $receipt->subscription->amount_paid }} {{ $branding['currency'] }}</strong></td></tr>
    <tr><td class="label">Solde restant</td><td><strong>{{ $receipt->subscription->balance }} {{ $branding['currency'] }}</strong></td></tr>
    <tr><td class="label">Agent émetteur</td><td>{{ $receipt->issuedBy?->first_name }} {{ $receipt->issuedBy?->last_name }}</td></tr>
</table>

@if($nextInstallment)
<div class="next-box">
    <div class="next-title">Prochain paiement</div>
    <table style="width:100%;border-collapse:collapse;font-size:11px">
        <tr><td style="color:#1e40af;width:40%">Date d'échéance</td><td><strong>{{ \Carbon\Carbon::parse($nextInstallment->due_date)->format('d/m/Y') }}</strong></td></tr>
        <tr><td style="color:#1e40af">Montant minimum</td><td><strong>{{ $nextInstallment->amount_due }} {{ $branding['currency'] }}</strong></td></tr>
    </table>
</div>
@else
<div class="next-box" style="background:#f0fdf4;border-color:#86efac">
    <div style="color:#15803d;font-weight:bold">✔ Souscription intégralement soldée</div>
</div>
@endif

<div class="footer">
    <p>Code de vérification : {{ $receipt->verification_code }}</p>
    <p>Vérifier ce reçu : {{ $verificationUrl }}</p>
    <p>Statut : {{ $receipt->status }}</p>
</div>

</body></html>
