<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Annulation de rendez-vous

Bonjour {{ $customer_name }},

Votre rendez-vous pour **{{ $service_name }}** prevu le **{{ $date }}** a **{{ $time }}** a ete annule.

@if($cancel_reason)
**Raison :** {{ $cancel_reason }}
@endif

@component('mail::button', ['url' => $rebooking_url, 'color' => 'primary'])
Prendre un nouveau rendez-vous
@endcomponent

Nous esperons vous revoir prochainement.

Cordialement,<br>
L'equipe {{ $brand_name }}
@endcomponent
