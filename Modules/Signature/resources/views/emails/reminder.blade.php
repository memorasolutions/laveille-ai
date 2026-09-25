<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# Ta signature de courriel sur laveille.ai

Bonjour,

Tu as créé une signature de courriel gratuite sur laveille.ai et tu nous avais demandé un petit
rappel avant sa suppression automatique après 6 mois d'inactivité.

Si tu veux la conserver, il te suffit de revenir sur l'outil et d'ouvrir ta signature (ou d'en
créer une nouvelle si tu ne retrouves plus le lien reçu à la création).

@component('mail::button', ['url' => $assistant_url, 'color' => 'primary'])
Retourner sur l'outil de signature
@endcomponent

Ce courriel est un rappel unique&nbsp;: nous ne t'écrirons pas une seconde fois. Aucune donnée
autre que ton adresse courriel n'a été conservée pour cet envoi.

À bientôt,<br>
L'équipe {{ $brand_name }}
@endcomponent
