<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Actualités à trier

Bonjour,

{{ $total_count }} {{ $total_count > 1 ? 'nouvelles actualités ont été collectées' : 'nouvelle actualité a été collectée' }} depuis ton dernier courriel de veille.
@if($more_count > 0)
Les {{ $shown_count }} plus pertinentes sont listées ci-dessous ; {{ $more_count }} {{ $more_count > 1 ? 'autres attendent' : 'autre attend' }} dans l'administration.
@endif

@foreach($items as $item)
---

### {{ $item['title'] }}

{{ $item['excerpt'] }}

@if($item['source'])
**Source :** {{ $item['source'] }}
@endif
@if(!is_null($item['score']))
**Score de pertinence :** {{ $item['score'] }}/100
@endif

@component('mail::button', ['url' => $item['compose_url'], 'color' => 'primary'])
Composer cette actualité
@endcomponent
@endforeach

---

@component('mail::button', ['url' => $composition_index_url, 'color' => 'success'])
Voir toutes les actualités en attente
@endcomponent

Ce résumé est envoyé au plus une fois par jour, seulement s'il y a du nouveau depuis le dernier envoi.

À bientôt,<br>
L'équipe {{ $brand_name }}
@endcomponent
