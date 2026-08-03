<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{--
    Réécriture complète (2026-08-02, étape 4 de .outils/PLAN-CONSTRUCTEUR-PROMPTS-ULTRA-2026-08-02.md,
    « page blanche » : rien de l'ancien fichier 1296 lignes n'est repris). Écran unique, deux états :
    (A) 9 cartes de sélection ; (B) une fois une carte choisie, elle se réduit en pastille en tête de
    la phrase à trous, sur le MÊME écran. Les 9 cartes restent des <input type="radio"> natifs dans un
    <fieldset>/<legend>, jamais retirées du DOM (accessibilité clavier/lecteur d'écran, section 4 du
    plan). Détail des 9 gabarits : .outils/GABARITS-CONSTRUCTEUR-PROMPTS-2026-08-02.md.
--}}
@extends(fronttheme_layout())
@section('no_ads', '1') {{-- Aucune pub : l'outil peut contenir des données personnelles (posture Loi 25) --}}
@php
    $shareData = $tool->getShareData();

    // 9 gabarits - SOURCE UNIQUE DE VÉRITÉ (DRY strict) : rendus côté serveur pour l'état A (SEO/AEO,
    // protection de la page la plus visitée du site, plan section 5) ET transmis tels quels à Alpine
    // (@js ci-dessous) pour piloter l'état B. Trous CANONIQUES partagés entre gabarits (sujet, contexte,
    // public, ton, longueur, format) : la migration du contenu au changement de carte n'a besoin
    // d'aucune logique dédiée, `values` est un objet JS PARTAGÉ entre les 9 gabarits, clé par nom de
    // trou (voir constructeur-prompts-core.js).
    $gabarits = [
        [
            'key' => 'rediger', 'label' => __('Rédiger'), 'icon' => '✍️',
            'example' => __('Ex. : un courriel aux parents'),
            'skeleton' => 'Rédige {sujet}, sur un ton {ton}, en {longueur}, destiné à {public}.',
            'qualitySuffix' => true,
            'fields' => [
                ['slot' => 'sujet', 'type' => 'text', 'label' => __('Sujet'),
                    'placeholder' => __('un courriel aux parents pour annoncer la sortie scolaire de vendredi au Biodôme')],
                ['slot' => 'ton', 'type' => 'select', 'label' => __('Ton'), 'options' => [
                    ['value' => 'chaleureux', 'label' => __('chaleureux')],
                    ['value' => 'formel', 'label' => __('formel')],
                    ['value' => 'neutre', 'label' => __('neutre')],
                    ['value' => 'enthousiaste', 'label' => __('enthousiaste')],
                    ['value' => 'ferme', 'label' => __('ferme')],
                ]],
                ['slot' => 'longueur', 'type' => 'select', 'label' => __('Longueur'), 'options' => [
                    ['value' => '1_paragraphe', 'label' => __('1 paragraphe')],
                    ['value' => '3_paragraphes', 'label' => __('3 paragraphes')],
                    ['value' => '1_page', 'label' => __('1 page')],
                ]],
                ['slot' => 'public', 'type' => 'select', 'label' => __('Destiné à'), 'options' => [
                    ['value' => 'parents', 'label' => __('des parents')],
                    ['value' => 'eleves', 'label' => __('des élèves')],
                    ['value' => 'collegues', 'label' => __('des collègues')],
                    ['value' => 'direction', 'label' => __('la direction')],
                    ['value' => 'parents_eleves', 'label' => __('des parents et des élèves')],
                ]],
            ],
        ],
        [
            'key' => 'resumer', 'label' => __('Résumer un document'), 'icon' => '📄',
            'example' => __('Ex. : un texte de 3 pages en 3 points clés'),
            'skeleton' => 'Résume le texte suivant {longueur}, sous forme de {format}, {public} : {contexte}',
            'qualitySuffix' => true,
            'fields' => [
                ['slot' => 'contexte', 'type' => 'textarea', 'label' => __('Texte à résumer'),
                    'placeholder' => __('collez ici le texte à résumer, ou décrivez-le')],
                ['slot' => 'longueur', 'type' => 'select', 'label' => __('Longueur'), 'options' => [
                    ['value' => '3_points', 'label' => __('en 3 points clés')],
                    ['value' => 'un_paragraphe', 'label' => __('en un paragraphe')],
                    ['value' => 'une_page', 'label' => __('en une page')],
                ]],
                ['slot' => 'public', 'type' => 'select', 'label' => __('Pour qui'), 'options' => [
                    ['value' => 'moi', 'label' => __('pour moi')],
                    ['value' => 'eleves', 'label' => __('pour mes élèves')],
                    ['value' => 'parents', 'label' => __('pour les parents')],
                ]],
                ['slot' => 'format', 'type' => 'select', 'label' => __('Format'), 'options' => [
                    ['value' => 'liste', 'label' => __('liste à puces')],
                    ['value' => 'paragraphe', 'label' => __('paragraphe suivi')],
                    ['value' => 'tableau', 'label' => __('tableau')],
                ]],
            ],
        ],
        [
            'key' => 'corriger', 'label' => __('Corriger et améliorer un texte'), 'icon' => '🔧',
            'example' => __('Ex. : un mot aux parents écrit rapidement'),
            'skeleton' => "Voici un texte à corriger et améliorer : {contexte}. D'abord, identifie les erreurs de grammaire et d'orthographe. Ensuite, propose une version corrigée avec un ton {ton}, en appliquant : {niveau_correction}.",
            'qualitySuffix' => true,
            'fields' => [
                ['slot' => 'contexte', 'type' => 'textarea', 'label' => __('Texte à corriger'),
                    'placeholder' => __('collez ici le texte à corriger, ex. un mot aux parents rédigé rapidement')],
                ['slot' => 'ton', 'type' => 'select', 'label' => __('Ton'), 'options' => [
                    ['value' => 'professionnel', 'label' => __('professionnel')],
                    ['value' => 'chaleureux', 'label' => __('chaleureux')],
                    ['value' => 'neutre', 'label' => __('neutre')],
                    ['value' => 'pro_chaleureux', 'label' => __('professionnel et chaleureux')],
                ]],
                ['slot' => 'niveau_correction', 'type' => 'select', 'label' => __('Niveau de correction'), 'options' => [
                    ['value' => 'ortho', 'label' => __('orthographe et grammaire seulement')],
                    ['value' => 'style', 'label' => __('améliorer aussi le style')],
                    ['value' => 'reformuler', 'label' => __('reformuler complètement')],
                ]],
            ],
        ],
        [
            'key' => 'analyser', 'label' => __('Analyser ou comparer'), 'icon' => '⚖️',
            'example' => __("Ex. : deux méthodes d'enseignement"),
            'skeleton' => 'Analyse et compare les éléments suivants selon {sujet} : {contexte}. Réfléchis critère par critère avant de conclure. Présente le résultat sous forme de {format}.',
            'qualitySuffix' => true,
            'fields' => [
                ['slot' => 'contexte', 'type' => 'textarea', 'label' => __('Éléments à comparer'),
                    'placeholder' => __('décrivez ou collez les éléments à comparer')],
                ['slot' => 'sujet', 'type' => 'text', 'label' => __('Selon quel critère'),
                    'placeholder' => __('leur efficacité pour des élèves en difficulté')],
                ['slot' => 'format', 'type' => 'select', 'label' => __('Format'),
                    'boosts' => [
                        'forts_faibles' => __('Présente deux listes vraiment distinctes (points forts, puis points faibles) pour chaque élément comparé, pas un tableau déguisé.'),
                    ],
                    'options' => [
                        ['value' => 'tableau', 'label' => __('tableau comparatif')],
                        ['value' => 'forts_faibles', 'label' => __('liste de points forts et de points faibles')],
                        ['value' => 'synthese', 'label' => __('synthèse en paragraphe')],
                    ]],
            ],
        ],
        [
            'key' => 'expliquer', 'label' => __('Expliquer simplement'), 'icon' => '💡',
            'example' => __('Ex. : la photosynthèse à un enfant de 6 ans'),
            'skeleton' => 'Explique {sujet} de façon simple, pour {public}, en {longueur}. Utilise des analogies concrètes.',
            'qualitySuffix' => true,
            'fields' => [
                ['slot' => 'sujet', 'type' => 'text', 'label' => __('Sujet à expliquer'), 'placeholder' => __('la photosynthèse')],
                ['slot' => 'public', 'type' => 'select', 'label' => __('Pour qui'), 'options' => [
                    ['value' => 'enfant6', 'label' => __('un enfant de 6 ans')],
                    ['value' => 'secondaire', 'label' => __('un élève du secondaire')],
                    ['value' => 'collegue', 'label' => __('un collègue non-spécialiste')],
                    ['value' => 'parent', 'label' => __('un parent')],
                ]],
                ['slot' => 'longueur', 'type' => 'select', 'label' => __('Longueur'), 'options' => [
                    ['value' => 'phrases', 'label' => __('quelques phrases')],
                    ['value' => 'paragraphe', 'label' => __('un paragraphe')],
                    ['value' => 'page', 'label' => __('une page')],
                ]],
            ],
        ],
        [
            'key' => 'idees', 'label' => __('Trouver des idées'), 'icon' => '🧠',
            'example' => __('Ex. : des activités brise-glace'),
            'skeleton' => 'Propose {nombre} pour {sujet}, en tenant compte de : {contexte}.',
            'qualitySuffix' => true,
            'fields' => [
                ['slot' => 'sujet', 'type' => 'text', 'label' => __('Sujet'),
                    'placeholder' => __("des activités brise-glace pour le premier cours de l'année")],
                ['slot' => 'contexte', 'type' => 'textarea', 'label' => __('Contexte'),
                    'placeholder' => __('temps disponible, matériel, taille du groupe')],
                ['slot' => 'nombre', 'type' => 'select', 'label' => __("Nombre d'idées"), 'options' => [
                    ['value' => '5', 'label' => __('5 idées')],
                    ['value' => '10', 'label' => __('10 idées')],
                    ['value' => 'max', 'label' => __("le plus d'idées possible")],
                ]],
            ],
        ],
        [
            'key' => 'preparer', 'label' => __('Préparer une activité ou un questionnaire'), 'icon' => '📋',
            'example' => __('Ex. : un questionnaire sur les fractions'),
            'skeleton' => "Prépare {format} sur {sujet}, pour {public}. Contexte : {contexte}. Réfléchis d'abord aux objectifs d'apprentissage, puis structure le déroulement étape par étape.",
            'qualitySuffix' => true,
            'fields' => [
                ['slot' => 'sujet', 'type' => 'text', 'label' => __('Sujet'), 'placeholder' => __('les fractions équivalentes')],
                ['slot' => 'public', 'type' => 'select', 'label' => __('Niveau scolaire'), 'options' => [
                    ['value' => 'maternelle', 'label' => __('la maternelle')],
                    ['value' => 'p1', 'label' => __('la 1re année du primaire')],
                    ['value' => 'p2', 'label' => __('la 2e année du primaire')],
                    ['value' => 'p3', 'label' => __('la 3e année du primaire')],
                    ['value' => 'p4', 'label' => __('la 4e année du primaire')],
                    ['value' => 'p5', 'label' => __('la 5e année du primaire')],
                    ['value' => 'p6', 'label' => __('la 6e année du primaire')],
                    ['value' => 's1', 'label' => __('le secondaire 1')],
                    ['value' => 's2', 'label' => __('le secondaire 2')],
                    ['value' => 's3', 'label' => __('le secondaire 3')],
                    ['value' => 's4', 'label' => __('le secondaire 4')],
                    ['value' => 's5', 'label' => __('le secondaire 5')],
                ]],
                ['slot' => 'contexte', 'type' => 'textarea', 'label' => __('Contexte'),
                    'placeholder' => __('durée, matériel disponible, taille du groupe')],
                ['slot' => 'format', 'type' => 'select', 'label' => __('Type de résultat'), 'options' => [
                    ['value' => 'plan', 'label' => __('un plan de leçon avec évaluation')],
                    ['value' => 'qcm', 'label' => __('un questionnaire à choix multiples')],
                    ['value' => 'activite', 'label' => __('une activité pratique')],
                    ['value' => 'quiz', 'label' => __('un quiz')],
                ]],
            ],
        ],
        [
            'key' => 'traduire', 'label' => __('Traduire'), 'icon' => '🌐',
            'example' => __('Ex. : une invitation en anglais'),
            'skeleton' => 'Traduis le texte suivant en {langue_cible}, avec un ton {ton} : {contexte}',
            'qualitySuffix' => false, // la sortie n'est justement pas censée être en français
            'fields' => [
                ['slot' => 'contexte', 'type' => 'textarea', 'label' => __('Texte à traduire'), 'placeholder' => __('collez ici le texte à traduire')],
                ['slot' => 'langue_cible', 'type' => 'select', 'label' => __('Langue cible'), 'options' => [
                    ['value' => 'anglais', 'label' => __('anglais')],
                    ['value' => 'espagnol', 'label' => __('espagnol')],
                    ['value' => 'francais', 'label' => __('français')],
                ]],
                ['slot' => 'ton', 'type' => 'select', 'label' => __('Ton'), 'options' => [
                    ['value' => 'formel', 'label' => __('formel')],
                    ['value' => 'neutre', 'label' => __('neutre')],
                    ['value' => 'chaleureux', 'label' => __('chaleureux et accueillant')],
                ]],
            ],
        ],
        [
            'key' => 'autre', 'label' => __('Autre chose'), 'icon' => '✨',
            'example' => __("Ex. : une tâche qui ne correspond à aucune carte ci-dessus"),
            'skeleton' => 'Agis en tant que {role}. {sujet}, destiné à {public}, sous forme de {format}.',
            'qualitySuffix' => true,
            'fields' => [
                ['slot' => 'role', 'type' => 'text', 'label' => __("Rôle de l'IA"), 'placeholder' => __('conseiller pédagogique')],
                ['slot' => 'sujet', 'type' => 'text', 'label' => __('Tâche à accomplir'),
                    'placeholder' => __("expliquer aux parents le nouveau système d'évaluation")],
                ['slot' => 'public', 'type' => 'select', 'label' => __('Destiné à'), 'options' => [
                    ['value' => 'parents', 'label' => __('des parents')],
                    ['value' => 'eleves', 'label' => __('des élèves')],
                    ['value' => 'collegues', 'label' => __('des collègues')],
                    ['value' => 'direction', 'label' => __('la direction')],
                    ['value' => 'public', 'label' => __('le grand public')],
                ]],
                ['slot' => 'format', 'type' => 'select', 'label' => __('Format'), 'options' => [
                    ['value' => 'liste', 'label' => __('liste à puces')],
                    ['value' => 'paragraphe', 'label' => __('paragraphe suivi')],
                    ['value' => 'tableau', 'label' => __('tableau')],
                ]],
            ],
        ],
    ];

    $anonymizerUrl = route('tools.show', ['slug' => 'anonymiseur']);
@endphp
@section('meta_description', $shareData['meta_description'])
@section('og_type', $shareData['og_type'])
@section('og_image', $shareData['og_image'])
@section('share_text', $shareData['share_text'])
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5"
                         x-data="promptBuilder(@js($gabarits), @js($anonymizerUrl))"
                         x-init="init()">

                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                                <p class="text-muted mb-0">{{ $tool->description }}</p>
                            </div>
                            <div class="d-flex gap-1" style="flex-shrink:0;">
                                @include('tools::partials.fullscreen-btn')
                                @include('tools::partials.share-btn', ['tool' => $tool])
                            </div>
                        </div>

                        @include('tools::public.partials.tool-geo')

                        <style>
                        /* ===== Constructeur de prompts - réécriture 2026-08-02 (préfixe cp- dédié) ===== */

                        /* Groupe des 9 cartes - état A : grille visible ; état B : fieldset masqué
                           (x-show) au profit de .cp-selected-pill, seul indicateur visuel restant.
                           Les 9 <input type="radio"> restent TOUJOURS dans le DOM (jamais retirés,
                           x-show plutôt que x-if - préserve l'état Alpine et évite un remount qui
                           perdrait le focus pour un lecteur d'écran). */
                        .cp-cards { border: none; padding: 0; margin: 0 0 1rem; }
                        .cp-cards legend { font-weight: 700; color: var(--c-dark); margin-bottom: .6rem; padding: 0; font-size: 1rem; }
                        .cp-cards--grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
                        @media (min-width: 380px) { .cp-cards--grid { grid-template-columns: repeat(3, 1fr); } }

                        .cp-card { position: relative; display: flex; flex-direction: column; gap: 2px; min-width: 0; min-height: 76px; padding: .7rem .8rem; border: 2px solid #d1d5db; border-radius: 12px; background: #fff; color: var(--c-dark); cursor: pointer; transition: border-color .15s ease, background .15s ease; }
                        .cp-card__title, .cp-card__example { overflow-wrap: break-word; word-break: break-word; }
                        /* Hover/focus volontairement DISTINCTS de la sélection (fond teal plein,
                           .is-checked) : ombre douce + bordure neutre, jamais le même traitement teal
                           qu'une carte sélectionnée (audit ergonomie 2026-08-02, point 3). */
                        .cp-card:hover { border-color: var(--c-text-muted); box-shadow: 0 3px 10px rgba(6,78,90,.15); }
                        .cp-card.is-checked { background: var(--c-primary); border-color: var(--c-primary); color: #fff; }
                        .cp-card.is-checked .cp-card__example { color: rgba(255,255,255,.85); }
                        .cp-card:focus-within { outline: 3px solid var(--c-accent); outline-offset: 2px; }
                        .cp-card__input, .cp-sr-announce { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
                        .cp-card__body { display: flex; flex-direction: column; gap: 2px; }
                        .cp-card__icon { font-size: 1.1rem; line-height: 1; }
                        .cp-card__title { font-weight: 700; font-size: .92rem; }
                        .cp-card__example { font-size: .76rem; color: var(--c-text-muted); }

                        /* Divulgation progressive sur mobile (<640px) : les 5 dernières cartes
                           (5e label et suivants, comptés hors <legend> via :nth-of-type) restent
                           dans le DOM mais sont masquées tant que showAllCards est faux. Sur
                           desktop (≥640px), aucun effet : les 9 cartes restent visibles. */
                        @media (max-width: 639.98px) {
                            .cp-cards--peek > label:nth-of-type(n+5) { display: none; }
                        }
                        .cp-cards__more-btn { display: none; margin: -.2rem 0 1rem; }
                        @media (max-width: 639.98px) {
                            .cp-cards__more-btn { display: inline-flex; }
                        }

                        .cp-selected-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .7rem; margin-bottom: .6rem; border-radius: 9999px; background: var(--c-primary-light); border: 1px solid rgba(6,78,90,0.25); color: var(--c-primary); font-weight: 700; font-size: .88rem; }
                        .cp-selected-pill__reset { min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; border: none; background: transparent; color: var(--c-primary); font-size: 1rem; cursor: pointer; border-radius: 6px; }
                        .cp-selected-pill__reset:hover { background: rgba(6,78,90,0.12); }
                        .cp-selected-pill__reset:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 1px; }

                        .cp-kept-note { font-size: .8rem; color: var(--c-primary); margin: 0 0 .5rem; }

                        .cp-intro-note { font-size: .88rem; color: var(--c-text-secondary); margin: 0 0 .7rem; }

                        /* Sur desktop (≥1024px), la phrase à trous (gauche) et l'aperçu + le
                           vérificateur (droite) sont visibles côte à côte, sans défiler. Sous
                           1024px, empilement vertical inchangé (chaque bloc garde son propre
                           x-show, la grille n'ajoute qu'une mise en page). */
                        @media (min-width: 1024px) {
                            .cp-builder-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; align-items: start; }
                            .cp-builder-grid .cp-phrase { margin-bottom: 0; height: 100%; }
                        }

                        .cp-phrase { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .35rem .5rem; padding: .9rem 1rem; background: var(--c-surface); border-radius: 12px; margin-bottom: .75rem; }
                        .cp-slot { display: inline-flex; flex-direction: column; gap: 2px; }
                        .cp-slot__label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--c-text-muted); display: inline-flex; align-items: center; }
                        .cp-slot__empty-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--c-accent); margin-left: 4px; flex-shrink: 0; }
                        .cp-slot__input { min-height: 40px; padding: .35rem .55rem; border: 2px solid #d1d5db; border-radius: 8px; font-family: var(--f-body); font-size: .9rem; color: var(--c-dark); background: #fff; }
                        .cp-slot__input:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 1px; border-color: var(--c-primary); }
                        /* Repère visuel PERMANENT (pas seulement au clic Copier) sur un champ vide -
                           s'appuie sur l'attribut data-cp-empty déjà réactif (Alpine), aucun nouvel
                           état JS nécessaire. */
                        .cp-slot__input[data-cp-empty="true"] { border-color: var(--c-accent); }
                        .cp-slot__textarea { min-width: 240px; max-width: 100%; resize: vertical; field-sizing: content; min-height: 40px; max-height: 320px; }
                        input.cp-slot__input { min-width: 160px; }
                        select.cp-slot__select { min-width: 140px; }
                        @keyframes cpFlashPulse { 0%, 100% { background-color: #fff; box-shadow: none; } 30%, 70% { background-color: var(--c-accent-light); box-shadow: 0 0 0 2px var(--c-accent) inset; } }
                        .cp-slot--flash .cp-slot__input { animation: cpFlashPulse 1.2s ease; }

                        .cp-preview { padding: .9rem 1rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: .6rem; }
                        .cp-preview__label { font-size: .78rem; font-weight: 700; color: var(--c-text-muted); margin: 0 0 .4rem; text-transform: uppercase; letter-spacing: .03em; }
                        .cp-preview__text { margin: 0 0 .5rem; line-height: 1.6; font-size: .96rem; white-space: pre-wrap; }
                        .cp-preview__literal { color: var(--c-primary); }
                        .cp-preview__user { color: var(--c-accent); font-weight: 600; }
                        .cp-preview__placeholder { color: var(--c-primary); font-style: italic; opacity: .75; }
                        .cp-preview__legend { display: flex; align-items: center; gap: .4rem; font-size: .74rem; color: var(--c-text-muted); margin: 0; flex-wrap: wrap; }
                        .cp-preview__legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-left: .5rem; }
                        .cp-preview__legend-dot:first-child { margin-left: 0; }
                        .cp-preview__legend-dot--user { background: var(--c-accent); }
                        .cp-preview__legend-dot--tool { background: var(--c-primary); }

                        .cp-verifier { padding: .7rem .9rem; background: #F3F4F6; border-radius: 10px; margin-bottom: .8rem; }
                        /* Statut simple en premier (rassurant si rien n'est repéré, chiffré sinon) -
                           les détails (caption + liste d'entités) suivent en plus petit et en
                           couleur atténuée, secondaires à la lecture. */
                        .cp-verifier__status { font-size: .95rem; font-weight: 700; margin: 0 0 .35rem; }
                        .cp-verifier__status.cp-verifier__status--ok { color: var(--c-primary); }
                        .cp-verifier__status.cp-verifier__status--warning { color: var(--c-accent); }
                        .cp-verifier__caption { font-size: .74rem; color: var(--c-text-muted); margin: 0 0 .25rem; }
                        .cp-verifier__result { font-size: .76rem; color: var(--c-text-muted); margin: 0; font-weight: 500; }

                        .cp-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-bottom: .5rem; }
                        .cp-open-group { display: inline-flex; align-items: center; gap: .4rem; }
                        .cp-ai-select { min-height: 44px; padding: .35rem .5rem; border: 2px solid #d1d5db; border-radius: 8px; font-size: .88rem; }

                        .cp-warning-note { font-size: .8rem; color: var(--c-text-muted); margin: 0 0 .8rem; }
                        .cp-open-instruction { font-size: .85rem; color: var(--c-primary); background: var(--c-primary-light); border-radius: 8px; padding: .5rem .7rem; margin-bottom: .6rem; }
                        .cp-open-fallback { font-size: .85rem; color: #5b4a1f; background: #FEF3C7; border-left: 3px solid #B7791F; border-radius: 8px; padding: .5rem .7rem; margin-bottom: .6rem; }
                        .cp-open-fallback a { color: #5b4a1f; font-weight: 700; text-decoration: underline; }

                        .cp-anon-btn { margin-bottom: 1rem; }

                        .cp-history { border-top: 1px solid #e5e7eb; padding-top: .8rem; margin-top: .4rem; }
                        .cp-history__toggle-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; }
                        .cp-history__toggle-label { display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: var(--c-text-secondary); cursor: pointer; }
                        .cp-history__list { list-style: none; padding: 0; margin: .6rem 0 0; display: flex; flex-direction: column; gap: .4rem; }
                        .cp-history__item { padding: .5rem .7rem; background: var(--c-surface); border-radius: 8px; font-size: .82rem; }
                        .cp-history__item-card { display: block; font-weight: 700; color: var(--c-primary); font-size: .74rem; text-transform: uppercase; letter-spacing: .02em; }
                        .cp-history__item-text { display: block; color: var(--c-dark); margin-top: 2px; white-space: pre-wrap; }
                        .cp-history__hint { font-size: .8rem; color: var(--c-text-muted); margin: .5rem 0 0; }
                        </style>

                        {{-- ===== État A / B : 9 cartes, radios natifs, jamais retirées du DOM ===== --}}
                        <fieldset class="cp-cards"
                                  x-show="!selectedCard"
                                  x-cloak
                                  :class="showAllCards ? 'cp-cards--grid' : 'cp-cards--grid cp-cards--peek'">
                            <legend>{{ __('Qu\'est-ce que vous voulez faire aujourd\'hui ?') }}</legend>
                            @foreach ($gabarits as $g)
                                <label class="cp-card" :class="{ 'is-checked': selectedCard === '{{ $g['key'] }}' }">
                                    <input type="radio"
                                           name="cpCard"
                                           class="cp-card__input"
                                           value="{{ $g['key'] }}"
                                           x-model="selectedCard"
                                           @change="onCardSelected()">
                                    <span class="cp-card__body">
                                        <span class="cp-card__icon" aria-hidden="true">{{ $g['icon'] }}</span>
                                        <span class="cp-card__title">{{ $g['label'] }}</span>
                                        <span class="cp-card__example">{{ $g['example'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </fieldset>

                        {{-- Annonce vocale (lecteur d'écran) du passage à l'écran du formulaire lors
                             d'une sélection de carte - TOUJOURS dans le DOM (jamais x-show) pour que la
                             région aria-live soit déjà enregistrée avant le premier changement de texte.
                             Visuellement masquée via le même procédé que .cp-card__input ci-dessus
                             (audit ergonomie 2026-08-02, point 4). --}}
                        <div class="cp-sr-announce" role="status" aria-live="polite" x-text="cardAnnouncement"></div>

                        {{-- Divulgation progressive mobile (<640px, section CSS .cp-cards--peek) :
                             les 5 dernières cartes restent des radios natifs dans le DOM, seulement
                             masquées en CSS - ce bouton ne fait que lever le masquage. --}}
                        <button type="button"
                                class="ct-btn ct-btn-outline cp-cards__more-btn"
                                style="min-height:44px;"
                                x-show="!selectedCard && !showAllCards"
                                x-cloak
                                @click="showAllCards = true"
                                :aria-expanded="showAllCards ? 'true' : 'false'"
                                aria-label="{{ __('Afficher les 5 options supplémentaires : Expliquer simplement, Trouver des idées, Préparer une activité ou un questionnaire, Traduire, Autre chose') }}">
                            {{ __('Voir toutes les options (5)') }}
                        </button>

                        {{-- Pastille de la carte choisie, en tête de la phrase - permet de revenir à
                             l'écran des 9 choix sans perdre le texte déjà saisi (trous canoniques
                             persistés dans `values`, section 4 du plan). --}}
                        <div class="cp-selected-pill" x-show="selectedCard" x-cloak>
                            <span x-text="currentTemplate() ? (currentTemplate().icon + ' ' + currentTemplate().label) : ''"></span>
                            <button type="button" class="cp-selected-pill__reset" @click="resetSelection()" aria-label="{{ __('Changer, revenir aux 9 choix') }}">✕</button>
                        </div>

                        <p class="cp-kept-note" x-show="notifyKept" x-cloak role="status" aria-live="polite">{{ __('Votre texte a été conservé.') }}</p>

                        <p class="cp-intro-note" x-show="selectedCard" x-cloak>
                            {{ __('Répondez à ces quelques questions : votre prompt se construira automatiquement ci-dessous.') }}
                        </p>

                        {{-- Sur desktop (≥1024px), formulaire et aperçu+vérificateur sont côte à
                             côte pour voir l'aperçu se mettre à jour sans défiler. Sous 1024px,
                             empilement vertical inchangé. --}}
                        <div class="cp-builder-grid" x-show="selectedCard" x-cloak>
                            <div class="cp-builder-col-form">
                                {{-- ===== Phrase à trous (état B) ===== --}}
                                <div class="cp-phrase" x-ref="phraseArea">
                                    <template x-for="f in (currentTemplate() ? currentTemplate().fields : [])" :key="f.slot">
                                        <span class="cp-slot" :class="{ 'cp-slot--flash': flashSlots[f.slot] }">
                                            <label :for="'cpField-' + f.slot" class="cp-slot__label">
                                                <span x-text="f.label"></span>
                                                <span class="cp-slot__empty-dot" x-show="!values[f.slot]" aria-hidden="true"></span>
                                            </label>

                                            <template x-if="f.type === 'text'">
                                                <input type="text"
                                                       :id="'cpField-' + f.slot"
                                                       class="cp-slot__input"
                                                       x-model.trim="values[f.slot]"
                                                       :placeholder="f.placeholder"
                                                       :data-cp-empty="(!values[f.slot]) ? 'true' : 'false'">
                                            </template>

                                            <template x-if="f.type === 'textarea'">
                                                <textarea :id="'cpField-' + f.slot"
                                                          class="cp-slot__input cp-slot__textarea"
                                                          x-model.trim="values[f.slot]"
                                                          :placeholder="f.placeholder"
                                                          rows="1"
                                                          @input="autoGrow($event.target)"
                                                          :data-cp-empty="(!values[f.slot]) ? 'true' : 'false'"></textarea>
                                            </template>

                                            <template x-if="f.type === 'select'">
                                                <select :id="'cpField-' + f.slot"
                                                        class="cp-slot__input cp-slot__select"
                                                        x-model="values[f.slot]"
                                                        :data-cp-empty="(!values[f.slot]) ? 'true' : 'false'">
                                                    <option value="">{{ __('Choisir...') }}</option>
                                                    <template x-for="opt in f.options" :key="opt.value">
                                                        <option :value="opt.value" x-text="opt.label"></option>
                                                    </template>
                                                </select>
                                            </template>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <div class="cp-builder-col-preview">
                                {{-- ===== Aperçu du prompt en direct ===== --}}
                                <div class="cp-preview" aria-live="polite">
                                    <p class="cp-preview__label">{{ __('Aperçu du prompt (se construit en direct)') }}</p>
                                    <p class="cp-preview__text">
                                        <template x-for="(seg, i) in previewSegments()" :key="i">
                                            <span :class="{
                                                    'cp-preview__literal': seg.type === 'literal',
                                                    'cp-preview__user': seg.type === 'user',
                                                    'cp-preview__placeholder': seg.type === 'placeholder'
                                                  }"
                                                  x-text="seg.text"></span>
                                        </template>
                                    </p>
                                    <p class="cp-preview__legend">
                                        <span><span class="cp-preview__legend-dot cp-preview__legend-dot--user" aria-hidden="true"></span>{{ __('vos mots') }}</span>
                                        <span><span class="cp-preview__legend-dot cp-preview__legend-dot--tool" aria-hidden="true"></span>{{ __("ajouté par l'outil") }}</span>
                                    </p>
                                </div>

                                {{-- ===== Vérificateur déterministe local - statut simple d'abord,
                                     détails ensuite - jamais un état "propre"/vert sur les détails ===== --}}
                                <div class="cp-verifier" role="status" aria-live="polite">
                                    <p class="cp-verifier__status"
                                       :class="verifierHasFindings() ? 'cp-verifier__status--warning' : 'cp-verifier__status--ok'"
                                       x-text="verifierStatusText()"></p>
                                    <p class="cp-verifier__caption" x-text="verifierCaption()"></p>
                                    <p class="cp-verifier__result" x-text="verifierResultText()"></p>
                                </div>
                            </div>
                        </div>

                        {{-- ===== Actions : copier / ouvrir dans une IA ===== --}}
                        <div class="cp-actions" x-show="selectedCard" x-cloak>
                            <button type="button" class="ct-btn ct-btn-primary" style="min-height:44px;" @click="copyPrompt()">
                                <span x-text="copyFeedback ? '{{ __('Copié !') }}' : (copyError ? '{{ __('Échec - copiez manuellement') }}' : '{{ __('Copier') }}')"></span>
                            </button>

                            <span class="cp-open-group">
                                <select class="cp-ai-select" x-model="targetAiKey" aria-label="{{ __("Choisir l'IA cible") }}">
                                    <template x-for="ai in aiTargets" :key="ai.key">
                                        <option :value="ai.key" x-text="ai.label"></option>
                                    </template>
                                </select>
                                <button type="button" class="ct-btn ct-btn-outline" style="min-height:44px;" @click="openInAI()">
                                    <span x-text="'{{ __('Ouvrir dans') }} ' + currentAiLabel()"></span>
                                </button>
                            </span>
                        </div>

                        <p class="cp-warning-note" x-show="selectedCard" x-cloak>
                            {{ __("Votre prompt sera envoyé à un service hébergé à l'étranger. Masquez les données personnelles avant.") }}
                        </p>

                        <div class="cp-open-instruction" x-show="openInstructionVisible" x-cloak role="status" aria-live="polite">
                            <span x-show="!openCopyFailed" x-cloak>{{ __("Le prompt est copié - dans l'autre onglet : appui long puis Coller (ou Ctrl+V / Cmd+V).") }}</span>
                            <span x-show="openCopyFailed" x-cloak>{{ __("La copie automatique a échoué - dans l'autre onglet, copiez le texte affiché ici et collez-le manuellement.") }}</span>
                        </div>
                        <div class="cp-open-fallback" x-show="openFallbackUrl" x-cloak role="alert">
                            {{ __('La fenêtre a été bloquée par votre navigateur.') }}
                            <a :href="openFallbackUrl" target="_blank" rel="noopener noreferrer" x-text="openFallbackUrl"></a>
                        </div>

                        {{-- ===== Pont vers l'anonymiseur - bouton visible, jamais un lien texte discret ===== --}}
                        <button type="button" class="ct-btn ct-btn-outline cp-anon-btn" style="min-height:44px;" x-show="selectedCard" x-cloak @click="goToAnonymizer()">
                            <span aria-hidden="true">🔒</span>
                            {{ __('Masquer mes informations personnelles (mode manuel + restauration de réponse IA)') }}
                        </button>

                        {{-- ===== Historique local (localStorage, désactivé par défaut, 7 jours) ===== --}}
                        <div class="cp-history" x-show="selectedCard" x-cloak>
                            <div class="cp-history__toggle-row">
                                <label class="cp-history__toggle-label">
                                    <input type="checkbox" x-model="historyEnabled" @change="toggleHistoryEnabled()" style="width:20px;height:20px;accent-color:var(--c-primary);margin:0;flex-shrink:0;">
                                    {{ __('Activer un historique local sur cet appareil (7 jours, jamais activé par défaut)') }}
                                </label>
                                <button type="button" class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px;" @click="clearHistory()">
                                    {{ __("Effacer l'historique") }}
                                </button>
                            </div>
                            <template x-if="historyEnabled && historyItems.length">
                                <ul class="cp-history__list">
                                    <template x-for="item in historyItems" :key="item.ts">
                                        <li class="cp-history__item">
                                            <span class="cp-history__item-card" x-text="item.card"></span>
                                            <span class="cp-history__item-text" x-text="item.text"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                            <p class="cp-history__hint" x-show="historyEnabled && !historyItems.length" x-cloak>
                                {{ __("Rien pour le moment - une entrée s'ajoute quand vous copiez ou ouvrez un prompt.") }}
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'constructeur-prompts'])
@endsection

@push('scripts')
<script src="{{ asset('assets/tools/constructeur-prompts/prompt-verifier-rules.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/constructeur-prompts/constructeur-prompts-core.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
