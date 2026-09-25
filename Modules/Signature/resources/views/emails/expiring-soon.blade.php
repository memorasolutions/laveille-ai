<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Ta signature de courriel - un petit rappel

Bonjour {{ $owner_name ?? '' }},

Ta signature de courriel n'a pas été modifiée depuis un moment. Ton compte reste actif, donc elle
reste bien en place&nbsp;: ce courriel est seulement un rappel, pas un avis de suppression.

Si tu veux la revoir, la mettre à jour ou vérifier qu'elle correspond toujours à tes coordonnées,
« Mes signatures » t'y amène directement.

@component('mail::button', ['url' => $manage_url, 'color' => 'primary'])
Ouvrir mes signatures
@endcomponent

Aucune action n'est requise si tout est encore à jour. Si tu n'en as plus besoin, tu peux la
supprimer toi-même depuis « Mes signatures » en tout temps.

À bientôt,<br>
L'équipe {{ $brand_name }}
@endcomponent
