@component('mail::message')
# Réinitialisation du mot de passe

Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.

@component('mail::button', ['url' => $url])
Réinitialiser le mot de passe
@endcomponent

Ce lien de réinitialisation expirera dans {{ $count }} minutes.

Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action n'est requise.

Cordialement,<br>
{{ config('app.name') }}
@endcomponent
