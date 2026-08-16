<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Du nouveau sur ton sondage

Bonjour {{ $creator_name ?? '' }},

Ton sondage **{{ $poll_title }}** a reçu de nouvelles réponses depuis ton dernier résumé :

@if($new_voters > 0)
- **{{ $new_voters }}** {{ $new_voters > 1 ? 'nouvelles réponses (ou réponses modifiées)' : 'nouvelle réponse (ou réponse modifiée)' }}
@endif
@if($new_declines > 0)
- **{{ $new_declines }}** {{ $new_declines > 1 ? 'personnes ont indiqué' : 'personne a indiqué' }} qu'aucune date ne leur convenait
@endif
@if($new_comments > 0)
- **{{ $new_comments }}** {{ $new_comments > 1 ? 'nouveaux commentaires' : 'nouveau commentaire' }}
@endif

@component('mail::button', ['url' => $manage_url, 'color' => 'primary'])
Voir les résultats
@endcomponent

Ce résumé est envoyé au plus une fois par jour, seulement s'il y a du nouveau - jamais un courriel par réponse. Tu peux le désactiver à tout moment depuis la page de gestion du sondage.

À bientôt,<br>
L'équipe {{ $brand_name }}
@endcomponent
