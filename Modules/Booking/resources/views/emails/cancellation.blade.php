<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Annulation de rendez-vous

Bonjour {{ $customer_name }},

Votre rendez-vous pour **{{ $service_name }}** prévu le **{{ $date }}** à **{{ $time }}** a été annulé.

@if($cancel_reason)
**Raison :** {{ $cancel_reason }}
@endif

@component('mail::button', ['url' => $rebooking_url, 'color' => 'primary'])
Prendre un nouveau rendez-vous
@endcomponent

Nous espérons vous revoir prochainement.

Cordialement,<br>
L'équipe {{ $brand_name }}
@endcomponent
