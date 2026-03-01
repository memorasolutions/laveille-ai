<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Vérification de votre adresse email

Veuillez cliquer sur le bouton ci-dessous pour vérifier votre adresse email.

@component('mail::button', ['url' => $url])
Vérifier mon adresse email
@endcomponent

Si vous n'avez pas créé de compte, aucune action n'est requise.

Cordialement,<br>
{{ config('app.name') }}
@endcomponent
