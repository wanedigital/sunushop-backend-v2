@component('mail::message')
# Demande de validation de profil

Bonjour,

Vous avez récemment créé une boutique sur notre plateforme.

Pour finaliser la création et activer votre profil de vendeur, veuillez cliquer sur le bouton ci-dessous :

@component('mail::button', ['url' => $validationUrl])
Activer mon profil Vendeur
@endcomponent

Ce lien est valide pendant 60 minutes.

Merci,<br>
L’équipe {{ config('app.name') }}
@endcomponent
