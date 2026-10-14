{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Courriel du code de connexion sans mot de passe (OTP) - version de repli.
    Vue Markdown dédiée (plutôt que l'API fluide greeting()/line()) afin que la
    partie TEXTE BRUT soit propre : pas de titre « # », pas de « ** » autour du
    code, pas de commentaire HTML. Les espaces insécables sont écrits en
    caractère U+00A0 littéral (jamais l'entité &nbsp;, qui s'afficherait telle
    quelle dans un lecteur texte seul).
--}}
<x-mail::message>
Bonjour,

Votre code de connexion pour {{ $appName }} :

<x-mail::panel>
{{ $token }}
</x-mail::panel>

Ce code expire dans {{ $expire }} minutes.

💡 Astuce : ajoutez info@laveille.ai à vos contacts (Gmail) ou à vos expéditeurs fiables (Outlook/Microsoft) pour ne jamais manquer un code dans vos pourriels.

Si vous n'avez pas demandé ce code, ignorez ce courriel.

L'équipe
</x-mail::message>
