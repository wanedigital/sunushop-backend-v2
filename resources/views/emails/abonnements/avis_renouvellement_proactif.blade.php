@component('mail::message')
# Votre abonnement SunuShop arrive à échéance bientôt !

Bonjour {{ $abonnement->user->prenom }},

Votre abonnement au plan **{{ $plan->nom }}** sur SunuShop arrivera à échéance le **{{ $dateFin }}**.

Pour assurer la continuité de votre service et éviter toute interruption, nous vous invitons à renouveler votre abonnement dès maintenant.

@component('mail::button', ['url' => url('/vendeur/abonnements')])
Renouveler mon abonnement
@endcomponent

Si vous avez des questions, n'hésitez pas à contacter notre support.

Merci de faire partie de la communauté SunuShop,
L'équipe SunuShop
@endcomponent
