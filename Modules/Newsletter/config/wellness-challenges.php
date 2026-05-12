<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Défis bien-être hebdomadaires pour la newsletter dimanche.
 *
 * Structure d'un défi :
 * - week_iso        : numéro ISO de semaine assigné (optionnel — si absent, rotation auto)
 * - score           : pourcentage satisfaction abonnés (badge ★ dans email)
 * - title           : titre court accrocheur
 * - subtitle        : sous-titre italique (optionnel)
 * - hook            : intro 1-2 phrases (HTML autorisé : <strong>, <em>)
 * - steps           : tableau de 3-5 étapes courtes (HTML autorisé)
 * - privacy         : encart 🔒 Loi 25 (optionnel)
 * - tools[]         : { profile, description } — outils par profil (optionnel)
 * - bonus           : encart 💡 boucle 24h (optionnel)
 * - cta_url + cta_label : bouton final (optionnel — laissé vide en mode JIT avec prompt)
 * - linked_prompt   : { intro, parts:[ { label, prefix, content }, ... ], outro }
 *                     remplace le weeklyPrompt généré → synergie défi + prompt IA.
 *                     Si absent, le weeklyPrompt classique est généré depuis aiTerm.
 *
 * Pour ajouter un défi : push une nouvelle entrée. La rotation utilise le numéro de
 * semaine ISO modulo count($challenges). Si tu veux fixer un défi à une semaine précise,
 * définis week_iso (priorité absolue sur la rotation).
 */

return [

    [
        'week_iso' => 20, // 2026 W20 = lundi 11 mai - dimanche 17 mai 2026
        'score' => 86,
        'title' => 'Brain Dump 2026 — 10 min de papier + 30 sec d\'IA',
        'subtitle' => 'Vide ton mental + transforme le brouillon en actions concrètes.',
        'hook' => 'Quand ta tête déborde, l\'écriture manuscrite libère 23&nbsp;% de capacité cognitive (Mueller &amp; Oppenheimer, 2014). En 2026, l\'IA OCR transforme ton brouillon en plan d\'action en 30 secondes.',
        'steps' => [
            '<strong>Papier + stylo, 10 minutes.</strong> Vide tout ce qui occupe ton esprit. Pas de jugement, pas d\'ordre.',
            '<strong>Biffe le sensible.</strong> Avant la photo, biffe ou plie les passages sensibles (santé, finances, conflits, données tiers).',
            '<strong>Photo téléphone.</strong> Cadrage net de la page. Ton IA va lire l\'écriture manuscrite.',
            '<strong>Lance le prompt IA</strong> (Étape 2 ci-dessous) — colle la Partie&nbsp;1 (transcription), puis la Partie&nbsp;2 (analyse) dans la même conversation.',
            '<strong>Sauvegarde + relis 24h plus tard.</strong> Les vrais insights émergent à J+1.',
        ],
        'privacy' => 'Aucune donnée n\'est envoyée à laveille.ai. Le prompt va directement dans ton IA préférée. Pour la protection Loi 25 maximale, biffe les passages sensibles et privilégie un OCR <em>on-device</em> (Apple Intelligence Notes).',
        'tools' => [
            ['profile' => '🍎 Apple / Mac', 'description' => 'Notes natif + Apple Intelligence Scribble. <strong>OCR on-device, zéro cloud.</strong>'],
            ['profile' => '📓 Notion', 'description' => 'Notion AI + base « Brain dumps » datée, requêtes par catégorie.'],
            ['profile' => '🧠 Obsidian', 'description' => 'Plugin OCR + Dataview pour suivi long-terme et détection de patterns.'],
            ['profile' => '🟡 Google', 'description' => 'Keep + side panel Gemini pour synthèse immédiate.'],
            ['profile' => '🔒 Anti-cloud strict', 'description' => 'LLaVa local via Ollama. Aucun envoi externe.'],
        ],
        'bonus' => 'Relis ton dump 24 h plus tard et demande à ton IA : <em>« Quels 3 insights ressortent ? Quel pattern récurrent vs la semaine dernière ? »</em>',
        'cta_url' => 'https://www.laveille.ai/outils/brain-dump',
        'cta_label' => 'Voir l\'outil Brain Dump 2026',
        'linked_prompt' => [
            'intro' => 'Deux blocs à copier-coller dans la <strong>même conversation</strong> avec ton IA préférée, l\'un après l\'autre.',
            'parts' => [
                [
                    'label' => 'Partie 1 — Transcription',
                    'pre_note' =>'Colle ce premier bloc + joins ta photo à la même conversation :',
                    'content' => "Tu es un transcripteur OCR précis. La photo que je viens de joindre à ce message est un brain dump manuscrit que je viens de faire. Transcris-le fidèlement en texte simple, ligne par ligne, en respectant l'ordre. Ne reformule pas, ne corrige pas l'orthographe — donne-moi le brut. Si une ligne est illisible, indique [???] et continue.",
                    'post_note' =>'Valide ou corrige la transcription. Quand elle est bonne, passe à la Partie 2.',
                ],
                [
                    'label' => 'Partie 2 — Analyse',
                    'pre_note' =>'Dans la même conversation, colle ensuite ce second bloc :',
                    'content' => "Tu es un coach en clarté mentale. À partir de la transcription validée juste au-dessus, réfléchis étape par étape avant de répondre. Pour chaque catégorisation, justifie ton choix en 1 phrase brève.\n\n1. Classe chaque ligne en 4 catégories : 🎯 ACTION · 💡 IDÉE · ❤️ ÉMOTION · ❓ QUESTION.\n   Format : « ligne → catégorie — justification (5-10 mots) ».\n2. Identifie les 3 patterns récurrents (thèmes qui reviennent même formulés différemment).\n3. Propose 1 insight non-évident — quelque chose que je n'ai probablement pas vu en l'écrivant. Justifie en 1-2 phrases.\n4. Donne-moi 3 actions concrètes prioritaires pour [REMPLACER : aujourd'hui / cette semaine / ce mois]. Pour chaque action : verbe d'action + résultat attendu en 1 ligne.\n\nTon : direct, pas de fioriture corporate.",
                    'post_note' =>'L\'analyse s\'appuie sur ton texte validé + justifie chaque étape — chain-of-thought 2026 best practice.',
                ],
            ],
            'outro' => 'Astuce : commence par <strong>copier la Partie 1</strong>, puis clique sur ton IA préférée — colle (Ctrl/Cmd + V) et joins ta photo.',
        ],
    ],

    // À enrichir : 19 autres défis bien-être + IA depuis le doc Google user.
    // Structure identique. Champs minimum requis : title + hook + steps.

];
