@component('mail::message')
# Votre abonnement SunuShop a été bloqué.

Bonjour {{ $abonnement->user->prenom }},

Nous vous informons que votre abonnement au plan **{{ $plan->nom }}** sur SunuShop a expiré et votre période de grâce est terminée.

Votre boutique est maintenant hors ligne et vous ne pouvez plus accéder aux fonctionnalités de gestion.

Pour réactiver votre boutique et continuer à vendre, veuillez renouveler votre abonnement.

@component('mail::button', ['url' => url('/vendeur/abonnements')])
Réactiver mon abonnement
@endcomponent

Si vous avez des questions, n'hésitez pas à contacter notre support.

Merci de faire partie de la communauté SunuShop,
L'équipe SunuShop
@endcomponent
