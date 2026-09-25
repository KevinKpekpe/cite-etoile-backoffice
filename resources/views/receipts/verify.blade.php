<x-layouts.guest title="Vérification du reçu">
    <section class="receipt-verification" aria-labelledby="receipt-verification-title">
        <p class="auth-kicker">Vérification sécurisée</p>
        <h1 id="receipt-verification-title">{{ $receipt->receipt_number }}</h1>
        <strong class="receipt-verification__amount">{{ number_format((float) $receipt->amount, 2, ',', ' ') }} <small>USD</small></strong>
        <p class="receipt-verification__status {{ $receipt->status === 'valid' ? 'receipt-verification__status--valid' : 'receipt-verification__status--cancelled' }}">
            <i class="bi bi-{{ $receipt->status === 'valid' ? 'check-circle' : 'x-circle' }}" aria-hidden="true"></i>
            {{ $receipt->status === 'valid' ? 'Ce reçu est valide.' : 'Ce reçu a été annulé.' }}
        </p>
        <p class="receipt-verification__date">Émis le {{ $receipt->issued_at->format('d/m/Y à H:i') }}</p>
    </section>
</x-layouts.guest>
