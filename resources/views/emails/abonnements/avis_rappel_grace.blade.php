@component('mail::message')
# Rappel : Votre abonnement SunuShop expire bientôt !

Bonjour {{ $abonnement->user->prenom }},

Ceci est un rappel amical que votre abonnement au plan **{{ $plan->nom }}** sur SunuShop expirera le **{{ $dateFin }}**.

Vous êtes toujours en période de grâce. Pour éviter toute interruption de service et continuer à profiter de toutes les fonctionnalités de votre plan, veuillez renouveler votre abonnement dès que possible.

@component('mail::button', ['url' => url('/vendeur/abonnements')])
Renouveler mon abonnement
@endcomponent

Si vous avez des questions, n'hésitez pas à contacter notre support.

Merci de faire partie de la communauté SunuShop,
L'équipe SunuShop
@endcomponent
