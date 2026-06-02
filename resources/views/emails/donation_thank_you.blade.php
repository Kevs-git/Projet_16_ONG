<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Merci pour votre don</title>
</head>
<body>
    <h1>Merci, {{ $donorName }} !</h1>
    <p>Nous avons bien reçu votre don pour la campagne : <strong>{{ $campaignTitle }}</strong>.</p>
    <p>Montant : <strong>${{ number_format($amount, 2) }}</strong></p>
    <p>Votre soutien fait une grande différence. Merci pour votre générosité.</p>
</body>
</html>
