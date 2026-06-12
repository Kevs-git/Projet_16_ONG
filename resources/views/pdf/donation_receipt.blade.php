<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #222; }
        .header { text-align: center; margin-bottom: 30px; }
        .receipt-number { background: #f3f4f6; padding: 8px 12px; display: inline-block; margin-top: 10px; }
        .details { margin-top: 20px; }
        .details td { padding: 8px; }
        .footer { margin-top: 30px; font-size: 12px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reçu fiscal</h1>
        <p class="receipt-number">Numéro de reçu : {{ $donation->receipt_number }}</p>
    </div>

    <table class="details" width="100%">
        <tr>
            <td><strong>Campagne</strong></td>
            <td>{{ $donation->campaign?->title }}</td>
        </tr>
        <tr>
            <td><strong>Donateur</strong></td>
            <td>{{ $donation->donor?->name }} ({{ $donation->donor?->email }})</td>
        </tr>
        <tr>
            <td><strong>Montant</strong></td>
            <td>{{ number_format($donation->amount, 2, ',', ' ') }} €</td>
        </tr>
        <tr>
            <td><strong>Date</strong></td>
            <td>{{ $donation->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="footer">
        <p>Merci pour votre soutien à notre ONG. Conservez ce document comme justificatif fiscal.</p>
    </div>
</body>
</html>
