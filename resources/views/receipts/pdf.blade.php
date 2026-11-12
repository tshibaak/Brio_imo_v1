<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;color:#172033;font-size:12px}h1{color:#0f766e}table{width:100%;border-collapse:collapse;margin-top:24px}td{padding:10px;border-bottom:1px solid #dbe3eb}.label{color:#64748b;width:35%}.muted{color:#64748b;font-size:10px;margin-top:30px}</style></head>
<body>
    <h1>BRIO-IMMO — Reçu de paiement</h1>
    <p><strong>{{ $receipt->number }}</strong> · Émis le {{ $receipt->issued_at->format('d/m/Y') }}</p>
    <table>
        <tr><td class="label">Bailleur</td><td>{{ $contract->landlord->name }} · {{ $contract->landlord->email }}</td></tr>
        <tr><td class="label">Locataire</td><td>{{ $contract->tenant->name }} · {{ $contract->tenant->email }}</td></tr>
        <tr><td class="label">Bien</td><td>{{ $contract->property->name }} · {{ $contract->property->address }}</td></tr>
        <tr><td class="label">Contrat</td><td>{{ $contract->reference }}</td></tr>
        <tr><td class="label">Facture</td><td>{{ $payment->invoice->number }}</td></tr>
        <tr><td class="label">Date du paiement validé</td><td>{{ $payment->reviewed_at?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Montant</td><td><strong>{{ number_format((float) $payment->amount, 2) }} {{ $payment->invoice->currency }}</strong></td></tr>
        <tr><td class="label">Moyen</td><td>{{ str_replace('_', ' ', $payment->method) }}</td></tr>
        <tr><td class="label">Référence de transaction</td><td>{{ $payment->transaction_reference ?: '—' }}</td></tr>
    </table>
    <p class="muted">Ce reçu confirme uniquement le paiement indiqué, après validation par le bailleur.</p>
</body></html>
