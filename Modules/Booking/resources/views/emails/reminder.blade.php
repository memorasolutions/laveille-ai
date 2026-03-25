<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Rappel de rendez-vous

Bonjour {{ $customer_name }},

Nous vous rappelons votre rendez-vous pour **{{ $service_name }}** dans **{{ $hours_before }}**.

**Détails :**
- Date : {{ $date }}
- Horaire : {{ $time }}

@component('mail::button', ['url' => $manage_url, 'color' => 'primary'])
Voir mon rendez-vous
@endcomponent

À très bientôt,<br>
L'équipe {{ $brand_name }}
@endcomponent
