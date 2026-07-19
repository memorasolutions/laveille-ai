<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Ton sondage sera bientôt supprimé

Bonjour {{ $creator_name ?? '' }},

Ton sondage **{{ $poll_title }}** sera automatiquement supprimé le **{{ $deletion_date }}**, conformément à notre politique de conservation des données.

Si tu souhaites le conserver plus longtemps, tu peux le prolonger de 3 mois depuis sa page de gestion.

@component('mail::button', ['url' => $manage_url, 'color' => 'primary'])
Prolonger de 3 mois
@endcomponent

Aucune action n'est requise si tu n'as plus besoin de ce sondage : il sera supprimé automatiquement à la date indiquée, sans autre avertissement.

À bientôt,<br>
L'équipe {{ $brand_name }}
@endcomponent
