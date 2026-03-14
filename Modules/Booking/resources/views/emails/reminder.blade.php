<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Rappel de rendez-vous

Bonjour {{ $customer_name }},

Nous vous rappelons votre rendez-vous pour **{{ $service_name }}** dans **{{ $hours_before }}**.

**Details :**
- Date : {{ $date }}
- Horaire : {{ $time }}

@component('mail::button', ['url' => $manage_url, 'color' => 'primary'])
Voir mon rendez-vous
@endcomponent

A tres bientot,<br>
L'equipe {{ $brand_name }}
@endcomponent
