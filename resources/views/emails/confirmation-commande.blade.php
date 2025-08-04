<!DOCTYPE html>
<html>
<head>
    <title>Confirmation de commande</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #fff; border: 1px solid #dee2e6; }
        .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Confirmation de commande</h1>
        </div>
        
        <div class="content">
            <p>Bonjour,</p>
            <p>Votre commande a bien été enregistrée sur notre plateforme.</p>
            
            <h2>Détails de la commande</h2>
            <p><strong>Numéro de commande:</strong> {{ $commande->numeroCommande }}</p>
            <p><strong>Date:</strong> {{ $commande->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Total:</strong> {{ number_format($commande->total, 0, ',', ' ') }} XOF</p>
            
            <h3>Articles commandés:</h3>
            <ul>
                @foreach($commande->detailCommandes as $item)
                    <li>{{ $item->quantite }} x {{ $item->produit->libelle }} - {{ number_format($item->produit->prix, 0, ',', ' ') }} XOF</li>
                @endforeach
            </ul>
            
            <p>Vous pouvez suivre l'état de votre commande en utilisant ce numéro sur notre site.</p>
            <p>Merci pour votre confiance !</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>