{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Bouton "Envoyer par courriel" DRY : ouvre le client courriel de L'UTILISATEUR (mailto:) avec
     sujet/corps pré-remplis. Zéro envoi serveur, zéro donnée collectée (aucun email stocké côté
     plateforme) - à privilégier sur tout envoi transactionnel serveur tant que le volume ne
     justifie pas l'infrastructure (voir mémoire projet decido-mailto-share-2026-07-19). --}}
@props(['subject', 'body', 'label' => 'Envoyer par courriel'])

<a href="mailto:?subject={{ rawurlencode($subject) }}&body={{ rawurlencode($body) }}"
   {{ $attributes->merge(['class' => 'ct-btn ct-btn-outline ct-btn-sm']) }}
   aria-label="{{ $label }}">
    {{ $label }}
</a>
