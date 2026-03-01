<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Bienvenue sur {{ config('app.name') }}

Bonjour {{ $user->name }},

Bienvenue sur **{{ config('app.name') }}** ! Votre compte a été créé avec succès.

Vous pouvez dès maintenant accéder à votre tableau de bord :

@component('mail::button', ['url' => url('/admin')])
Accéder au tableau de bord
@endcomponent

Si vous avez des questions, n'hésitez pas à nous contacter.

Cordialement,<br>
L'équipe {{ config('app.name') }}
@endcomponent
