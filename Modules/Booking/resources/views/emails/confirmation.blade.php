<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Confirmation de rendez-vous

Bonjour {{ $customer_name }},

Votre rendez-vous pour **{{ $service_name }}** a bien été enregistré.

**Détails :**
- Date : {{ $date }}
- Horaire : {{ $time }}

@component('mail::button', ['url' => $manage_url, 'color' => 'success'])
Gérer mon rendez-vous
@endcomponent

Vous pouvez modifier ou annuler votre rendez-vous en cliquant sur le bouton ci-dessus.

Merci de votre confiance,<br>
L'équipe {{ $brand_name }}
@endcomponent
