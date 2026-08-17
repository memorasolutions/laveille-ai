<?php

declare(strict_types=1);

return [
    'name' => 'News',

    /*
    |--------------------------------------------------------------------------
    | Commentaires sur les actualités
    |--------------------------------------------------------------------------
    | 2026-05-27 #312 — désactivés par défaut (décision user). Les actualités
    | sont des news auto-syndiquées (450+ depuis 23 sources RSS), pas du
    | contenu éditorial original — les commentaires créent du bruit et de la
    | modération sans bénéfice clair. Conservé sur articles blog éditoriaux.
    | Pour réactiver : .env NEWS_COMMENTS_ENABLED=true OU passer default true.
    */
    'comments_enabled' => env('NEWS_COMMENTS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Déduplication cross-source
    |--------------------------------------------------------------------------
    | Évite le résumé IA sur des articles en doublon cross-source.
    | Désactiver temporairement si la déduplication est trop agressive.
    */
    'dedup_skip_enabled' => (bool) env('NEWS_DEDUP_SKIP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Publication automatique des fiches collectees
    |--------------------------------------------------------------------------
    | 2026-08-14 - decision du fondateur : le site cesse de publier automatiquement une fiche
    | d'actualite (erreurs factuelles, risque juridique, refus publicitaire pour contenu a
    | faible valeur). La COLLECTE continue sans interruption (scoring, resume IA, porte de
    | qualite, fusion, deduplication) ; seule la publication finale est suspendue. Les fiches
    | qui auraient ete publiees deviennent des brouillons (is_published=false) alimentant la
    | future file de propositions du courriel de veille quotidien.
    | DEFAUT VOLONTAIREMENT FALSE : un oubli de configuration (env absent, mauvaise valeur) ne
    | doit jamais pouvoir remettre la publication automatique en marche par accident - il faut
    | une action explicite (NEWS_AUTOPUBLISH_ENABLED=true) pour la reactiver.
    | Resolu par FetchNewsCommand::resolvePublicationState(), lu une seule fois en debut de
    | handle(). Distinct du kill switch Pennant 'cron.news-fetch' (qui coupe TOUTE la commande,
    | y compris la collecte) - ce drapeau ne coupe que l'ecriture is_published.
    */
    'autopublish' => [
        'enabled' => (bool) env('NEWS_AUTOPUBLISH_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generation machine des resumes a la collecte
    |--------------------------------------------------------------------------
    | 2026-08-17 - decision du fondateur (verbatim : « supprime l'automatisation qu'on
    | utilisait pour les anciennes actus, on ne l'utilisera plus »). La generation MACHINE du
    | resume structure (AiSummaryService::scoreAndSummarize/scoreAndSummarizeGroup) a la collecte
    | est abandonnee : le contenu des fiches vient desormais exclusivement du flux /actu2
    | (composition IA supervisee). Reversible par configuration (doctrine modules
    | desactivables) - jamais une suppression de code seche. La COLLECTE elle-meme (titres,
    | liens, dedup, evaluation de pertinence par mots-cles) CONTINUE sans interruption : elle
    | alimente le selecteur de l'ecran de composition et le courriel de veille de 7h15.
    | Effet conjoint : drapeau eteint = plus AUCUN texte d'article n'est envoye au fournisseur
    | de modele pendant la collecte (point de vigilance Loi 25 de la cloture Actus 2.0, regle
    | par extinction).
    | DEFAUT VOLONTAIREMENT FALSE, meme doctrine qu'autopublish ci-dessus : un oubli de
    | configuration (env absent, mauvaise valeur) ne doit jamais pouvoir relancer la generation
    | machine ni l'envoi des textes au fournisseur par accident - il faut une action explicite
    | (NEWS_MACHINE_SUMMARY_ENABLED=true) pour la reactiver.
    | Consomme par FetchNewsCommand (3 points d'appel : chemin non-fusion, fusion singleton,
    | fusion groupe), ReprocessArticlesCommand et AdminNewsController::rescoreArticle - chaque
    | point d'appel refuse explicitement (sortie/console/flash) plutot que d'echouer en
    | silence.
    */
    'machine_summary' => [
        'enabled' => (bool) env('NEWS_MACHINE_SUMMARY_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Courriel de veille quotidien (brouillons non publies)
    |--------------------------------------------------------------------------
    | 2026-08-16 - demande du fondateur : depuis l'arret de la publication automatique
    | (autopublish ci-dessus), les fiches collectees s'accumulent en brouillon
    | (is_published=false) sans que personne ne soit averti. NotifyNewsDigestCommand
    | (news:notify-digest) envoie un resume quotidien groupe des nouveaux brouillons a
    | l'administrateur (config app.superadmin_email), avec lien direct vers l'ecran de
    | composition pour chacun. DEFAUT ACTIF (contrairement a autopublish) : ce courriel ne
    | publie rien tout seul, il informe seulement - aucun risque a le laisser actif par defaut.
    | max_items borne la taille du courriel si un gros arriere s'est accumule (ex. premier envoi
    | apres plusieurs jours sans consultation) ; le reste reste consultable dans l'administration.
    */
    'digest' => [
        'enabled' => (bool) env('NEWS_DIGEST_ENABLED', true),
        'max_items' => (int) env('NEWS_DIGEST_MAX_ITEMS', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fenêtre de traitement des articles récupérés
    |--------------------------------------------------------------------------
    | news:fetch ne (re)traite que les articles créés dans cette fenêtre. Sans
    | cette borne, les articles sautés par quota s'accumulent indéfiniment dans
    | la file (12 436 mesurés le 2026-08-09, ~43 Mo de texte) et le cron horaire
    | meurt en épuisement mémoire (128 Mo CLI) à chaque exécution.
    */
    'fetch_backlog_hours' => (int) env('NEWS_FETCH_BACKLOG_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Repli d'affichage (cascade "summary, sinon accroche, sinon repli")
    |--------------------------------------------------------------------------
    | Design doc "Actus - zero copie du texte source" (2026-08-13), section 4.5. Phrase de
    | dernier recours quand une fiche n'a ni resume court ni resume structure exploitable -
    | jamais affichee vide. :category et :date sont remplaces par NewsArticle::displayExcerpt().
    */
    'display_fallback' => [
        'with_category' => env('NEWS_DISPLAY_FALLBACK_WITH_CATEGORY', ':category - :date'),
        'generic' => env('NEWS_DISPLAY_FALLBACK_GENERIC', 'Actualité en cours de traitement.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Porte de qualite avant persistance d'un resume IA
    |--------------------------------------------------------------------------
    | Design doc "Actus - zero copie du texte source" (2026-08-13), section 4.2. Controles
    | executes par Modules\News\Services\SummaryQualityGate, dans l'ordre : structure, vacuite,
    | langue, longueurs, non-copie, coherence des annees, non-invention d'entites (2 derniers
    | ajoutes le 2026-08-13 suite a une mesure sur 47 fiches reelles : 27,7 % deformees/inventees).
    | En cas de refus : relance sur le modele suivant de la cascade
    | (AiSummaryService::callModelCascade) ; cascade epuisee = aucune fiche creee. Chaque rejet
    | est journalise avec son motif precis sur le canal dedie 'quality_gate' (config/logging.php),
    | independant de LOG_LEVEL.
    */
    'quality_gate' => [
        'enabled' => (bool) env('NEWS_QUALITY_GATE_ENABLED', true),

        // Champs obligatoires (structure + vacuite). Recalibre le 2026-08-13 : le couple
        // score+hook d'origine etait volontairement lache (choisi pour ne pas faire echouer des
        // fixtures de test minimales), pas pour refleter le contrat reel du gabarit. Liste
        // extraite du prompt AiSummaryService (scoreAndSummarize + scoreAndSummarizeGroup,
        // intersection des deux contrats) : uniquement les champs que le prompt decrit comme
        // TOUJOURS produits (jamais qualifies "ou null"/"sinon null"). Volontairement exclus :
        // quote, key_stat, expert_name, expert_role (explicitement nullables au contrat) et les
        // champs propres au chemin groupe (sources, divergences, archive_context, angle_qc_ca)
        // qui casseraient le chemin singleton, qui ne les produit jamais.
        'required_fields' => explode(',', (string) env(
            'NEWS_QUALITY_GATE_REQUIRED_FIELDS',
            'score,score_justification,category,impact,tldr,hook,key_points,why_important,audience,seo_title,meta_description,faq_question,faq_answer'
        )),

        // Champs "contenu produit" balayes par les controles de coherence des annees et de
        // non-invention d'entites (SummaryQualityGate::checkYearCoherence/checkEntityInvention).
        'content_fields' => explode(',', (string) env(
            'NEWS_QUALITY_GATE_CONTENT_FIELDS',
            'tldr,hook,why_important,key_points,faq_answer,faq_question,quote,key_stat,expert_name,expert_role,seo_title,meta_description,score_justification'
        )),

        // Coherence des annees (2026-08-13, motif de rejet le plus frequent mesure : millesime
        // hallucine, ex. "2024" dans une fiche sur un article d'aout 2026). Une annee de 4
        // chiffres doit apparaitre dans le texte source OU tomber dans +-year_tolerance an(s)
        // autour de la date de publication de l'article.
        'year_check_enabled' => (bool) env('NEWS_QUALITY_GATE_YEAR_CHECK_ENABLED', true),
        'year_tolerance' => (int) env('NEWS_QUALITY_GATE_YEAR_TOLERANCE', 1),

        // Non-invention d'entites (2026-08-13, 2e motif de rejet le plus frequent mesure :
        // entite/organisation citee mais jamais nommee dans la source). Controle conservateur :
        // ne rejette qu'un candidat (sequence d'au moins entity_min_capitalized_words mots a
        // majuscule initiale, comptant au moins entity_min_significant_words mots significatifs
        // de entity_min_word_length caracteres ou plus une fois les mots-outils ecartes)
        // entierement absent de la source (aucun mot significatif partiellement retrouve,
        // casse/accents ignores). Le seuil entity_min_significant_words evite qu'une paire
        // "mot + sigle court" (ex. acronyme de 3 lettres accole a un mot capitalise) soit jugee
        // a tort "absente" faute de matiere suffisante a comparer.
        'entity_check_enabled' => (bool) env('NEWS_QUALITY_GATE_ENTITY_CHECK_ENABLED', true),
        'entity_min_capitalized_words' => (int) env('NEWS_QUALITY_GATE_ENTITY_MIN_CAPITALIZED_WORDS', 2),
        'entity_min_word_length' => (int) env('NEWS_QUALITY_GATE_ENTITY_MIN_WORD_LENGTH', 4),
        'entity_min_significant_words' => (int) env('NEWS_QUALITY_GATE_ENTITY_MIN_SIGNIFICANT_WORDS', 2),

        'hook_min_words' => (int) env('NEWS_QUALITY_GATE_HOOK_MIN_WORDS', 3),
        'hook_max_words' => (int) env('NEWS_QUALITY_GATE_HOOK_MAX_WORDS', 200),
        'seo_title_max_chars' => (int) env('NEWS_QUALITY_GATE_SEO_TITLE_MAX_CHARS', 90),
        'meta_description_max_chars' => (int) env('NEWS_QUALITY_GATE_META_DESCRIPTION_MAX_CHARS', 200),
        'min_key_points' => (int) env('NEWS_QUALITY_GATE_MIN_KEY_POINTS', 1),

        // Detection anglais : refuse seulement si l'anglais domine nettement (jamais un faux
        // positif sur un nom propre ou un acronyme technique isole - OpenAI, ChatGPT, GPT...).
        'min_english_hits_to_flag' => (int) env('NEWS_QUALITY_GATE_MIN_ENGLISH_HITS', 3),

        // Anti-copie : longueur (en mots) au-dela de laquelle une suite verbatim commune entre
        // le resume et le texte source est consideree comme une copie. Le champ "quote" est
        // volontairement exclu du controle (citation verbatim assumee par le contrat du prompt).
        'copy_max_words' => (int) env('NEWS_QUALITY_GATE_COPY_MAX_WORDS', 12),

        'french_stopwords' => [
            'le', 'la', 'les', 'de', 'des', 'du', 'un', 'une', 'et', 'est', 'sont', 'dans',
            'pour', 'avec', 'que', 'qui', 'ne', 'pas', 'sur', 'en', 'ce', 'cette', 'ces', 'au',
            'aux', 'plus', 'son', 'sa', 'ses', 'leur', 'leurs', 'par', 'ou', 'mais', 'donc',
            'il', 'elle', 'ils', 'elles', 'nous', 'vous', 'être', 'avoir', 'fait', 'faire',
            'comme', 'aussi', 'après', 'entre', 'sans', 'selon', 'ainsi',
        ],
        'english_stopwords' => [
            'the', 'and', 'of', 'is', 'in', 'to', 'for', 'with', 'that', 'this', 'are', 'was',
            'were', 'has', 'have', 'on', 'at', 'by', 'from', 'as', 'be', 'it', 'an', 'or', 'but',
            'not', 'their', 'its', 'we', 'you', 'will', 'would', 'about', 'into', 'than', 'these',
        ],
    ],
];
