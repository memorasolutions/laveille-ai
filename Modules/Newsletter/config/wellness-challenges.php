<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Défis hebdomadaires de la newsletter, orientés IA et numérique (et bien-être).
 * Recadrage 2.2 : le bandeau « Défi bien-être » devient « Défi IA et numérique »
 * (titre + intro on-promise dans digest-weekly.blade.php). Les défis ci-dessous
 * restent compatibles : title + hook + steps suffisent.
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

    // --- Défis IA et numérique (recadrage 2.2, on-promise) -----------------------
    // Pas de week_iso : ils entrent dans la rotation modulo. Structure identique.
    // Champ « score » omis volontairement (aucune statistique de satisfaction inventée).

    [
        'title' => 'Résume un long texte en 30 secondes avec l\'IA',
        'subtitle' => 'Transforme un pavé en cinq points clés que tu retiens vraiment.',
        'hook' => 'On reçoit tous des courriels, des comptes rendus ou des articles trop longs. Cette semaine, laisse l\'IA faire le tri : tu gardes l\'essentiel sans tout relire.',
        'steps' => [
            '<strong>Choisis un texte.</strong> Un courriel long, un compte rendu de réunion ou un article que tu as mis de côté.',
            '<strong>Retire le sensible.</strong> Avant de coller, enlève les noms, les montants et les renseignements personnels que tu ne veux pas partager.',
            '<strong>Demande un résumé.</strong> Colle le texte dans ChatGPT, Claude ou Gemini avec : « Résume ce texte en 5 points clés, puis donne-moi la prochaine action à faire. »',
            '<strong>Garde ton esprit critique.</strong> Compare le résumé au texte d\'origine : l\'IA peut se tromper ou oublier un détail important.',
        ],
        'privacy' => 'Le texte que tu colles est envoyé au service d\'IA choisi. Pour respecter la Loi 25, ne colle jamais de renseignements personnels de tes clients ou collègues sans leur accord.',
        'bonus' => 'Demande ensuite : <em>« Reformule ce résumé pour quelqu\'un qui n\'y connaît rien. »</em> Un bon test pour vérifier que tu as vraiment compris.',
        'cta_url' => 'https://www.laveille.ai/outils/constructeur-prompts',
        'cta_label' => 'Construire mon prompt de résumé',
    ],

    [
        'title' => 'Active la double authentification sur un compte important',
        'subtitle' => 'Cinq minutes aujourd\'hui pour éviter un piratage demain.',
        'hook' => 'Un mot de passe seul ne suffit plus en 2026. La double authentification (2FA) ajoute une deuxième barrière : même si quelqu\'un vole ton mot de passe, il ne peut pas entrer.',
        'steps' => [
            '<strong>Choisis un compte clé.</strong> Ton courriel principal, ta banque, tes réseaux sociaux ou ton infonuagique : commence par le plus important.',
            '<strong>Ouvre les réglages de sécurité.</strong> Cherche « Authentification à deux facteurs » ou « Vérification en deux étapes ».',
            '<strong>Privilégie une application.</strong> Une appli d\'authentification (Google Authenticator, Microsoft Authenticator) est plus sûre que le code reçu par texto.',
            '<strong>Note tes codes de secours.</strong> Range-les dans un endroit sûr au cas où tu perdrais ton téléphone.',
        ],
        'privacy' => 'La 2FA protège tes données personnelles et celles de tes contacts. C\'est une mesure de sécurité concrète, dans l\'esprit de la Loi 25.',
        'bonus' => 'Profite de ton élan : active aussi la 2FA sur un deuxième compte cette semaine. L\'habitude se prend vite.',
        'cta_url' => 'https://www.laveille.ai/glossaire',
        'cta_label' => 'Comprendre les termes de sécurité',
    ],

    [
        'title' => 'Repère une fausse image générée par IA',
        'subtitle' => 'Aiguise ton œil avant de partager.',
        'hook' => 'Les images générées par IA sont partout, et certaines servent à désinformer. Cette semaine, entraîne ton œil à reconnaître les indices qui trahissent une image artificielle.',
        'steps' => [
            '<strong>Observe les détails qui clochent.</strong> Mains à six doigts, textes illisibles, bijoux ou dents asymétriques, arrière-plans qui se déforment.',
            '<strong>Vérifie la source.</strong> D\'où vient l\'image ? Un compte fiable ou un profil anonyme créé hier ?',
            '<strong>Fais une recherche inversée.</strong> Colle l\'image dans Google Images ou TinEye pour voir où elle est apparue en premier.',
            '<strong>Doute avant de partager.</strong> Dans le doute, ne partage pas : une fausse image se propage en quelques clics.',
        ],
        'bonus' => 'Demande à une IA : <em>« Quels sont les signes typiques d\'une image générée par IA en 2026 ? »</em> et compare avec ta propre liste.',
        'cta_url' => 'https://www.laveille.ai/glossaire',
        'cta_label' => 'Explorer le glossaire IA',
    ],

    // À enrichir : autres défis IA, numériques et bien-être. Structure identique.

];
