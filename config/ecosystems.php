<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

// Mapping "écosystème d'entreprise" pour l'annuaire d'outils (Modules/Directory, colonne
// directory_tools.ecosystem_tag). Permet de regrouper visuellement les outils par éditeur
// (ex. ChatGPT + Sora + DALL-E sous "OpenAI") sans travail manuel répétitif.
//
// Résolution : Modules\Directory\Services\EcosystemResolverService::resolve($url) extrait le
// domaine RACINE réel de l'URL (via jeremykendall/php-domain-parser + Public Suffix List, donc
// compatible .co.uk etc.) puis cherche une correspondance EXACTE (jamais str_contains/substring)
// dans le tableau 'domains' ci-dessous.
//
// Pour ajouter un écosystème : ajouter le(s) domaine(s) racine du produit dans 'domains' (clé =
// domaine, valeur = tag), et si le tag est nouveau, ajouter son libellé affichable dans 'labels'.
// Sources de vérification (2026-07-23, recherche Perplexity) : domaines confirmés à cette date,
// à revalider périodiquement (les entreprises IA changent parfois de domaine produit).

return [

    // Domaine racine exact => tag d'écosystème. Un même tag peut regrouper plusieurs domaines
    // (ex. openai.com + chatgpt.com + sora.com => 'openai').
    'domains' => [
        // OpenAI
        'openai.com' => 'openai',
        'chatgpt.com' => 'openai',
        'sora.com' => 'openai',

        // Google / Alphabet (Gemini est servi sur gemini.google.com => domaine racine google.com)
        'google.com' => 'google',
        'deepmind.com' => 'google',
        'deepmind.google' => 'google',

        // Anthropic
        'anthropic.com' => 'anthropic',
        'claude.ai' => 'anthropic',
        'claude.com' => 'anthropic',

        // Midjourney
        'midjourney.com' => 'midjourney',

        // Perplexity
        'perplexity.ai' => 'perplexity',

        // Mistral AI
        'mistral.ai' => 'mistral',

        // Runway
        'runwayml.com' => 'runway',

        // ElevenLabs
        'elevenlabs.io' => 'elevenlabs',

        // Notion
        'notion.so' => 'notion',
        'notion.com' => 'notion',

        // Jasper AI
        'jasper.ai' => 'jasper',

        // Stability AI
        'stability.ai' => 'stability-ai',

        // Adobe (Firefly, etc.)
        'adobe.com' => 'adobe',

        // Microsoft (Copilot, Bing)
        'microsoft.com' => 'microsoft',
        'bing.com' => 'microsoft',

        // Meta (Meta AI / Llama)
        'meta.ai' => 'meta',
        'meta.com' => 'meta',

        // DeepSeek
        'deepseek.com' => 'deepseek',

        // xAI (Grok)
        'x.ai' => 'xai',
        'grok.com' => 'xai',

        // Canva
        'canva.com' => 'canva',
    ],

    // Tag d'écosystème => libellé affichable (badge frontend).
    'labels' => [
        'openai' => 'OpenAI',
        'google' => 'Google',
        'anthropic' => 'Anthropic',
        'midjourney' => 'Midjourney',
        'perplexity' => 'Perplexity',
        'mistral' => 'Mistral AI',
        'runway' => 'Runway',
        'elevenlabs' => 'ElevenLabs',
        'notion' => 'Notion',
        'jasper' => 'Jasper AI',
        'stability-ai' => 'Stability AI',
        'adobe' => 'Adobe',
        'microsoft' => 'Microsoft',
        'meta' => 'Meta',
        'deepseek' => 'DeepSeek',
        'xai' => 'xAI',
        'canva' => 'Canva',
    ],

];
