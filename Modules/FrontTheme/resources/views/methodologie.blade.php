<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Méthodologie & charte éditoriale') . ' - ' . config('app.name'))
@section('meta_description', __('Comment La veille évalue les outils, source les actualités et garantit son indépendance éditoriale. Méthodologie transparente, scoring rigoureux, fraîcheur des données.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Méthodologie')])
@endsection

@push('head')
@php
    $methodologieJsonLd = json_encode([
        chr(64).'context' => 'https://schema.org',
        chr(64).'type' => 'Article',
        'headline' => 'Méthodologie & charte éditoriale — La veille',
        'description' => 'Comment La veille évalue les outils IA, source les actualités et garantit son indépendance éditoriale.',
        'datePublished' => '2026-05-09',
        'dateModified' => now()->toIso8601String(),
        'inLanguage' => 'fr-CA',
        'author' => [
            chr(64).'type' => 'Person',
            'name' => 'Stéphane Lapointe',
            'url' => url('/auteur/stephane-lapointe'),
            'jobTitle' => 'Veille IA & Transformation numérique Québec',
        ],
        'publisher' => [
            chr(64).'type' => 'Organization',
            'name' => 'La veille',
            'url' => url('/'),
            'logo' => [
                chr(64).'type' => 'ImageObject',
                'url' => asset('images/logo-horizontal-white.svg'),
            ],
        ],
        'mainEntityOfPage' => [
            chr(64).'type' => 'WebPage',
            chr(64).'id' => url()->current(),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $methodologieJsonLd !!}</script>
@endpush

@section('content')
<section style="padding: 60px 0 40px; background: #fff;">
    <div class="container">
        <div style="max-width: 820px; margin: 0 auto;">

            <header style="margin-bottom: 36px;">
                <p style="text-transform: uppercase; letter-spacing: 1.5px; font-size: 12px; font-weight: 700; color: var(--c-primary, #064E5A); margin: 0 0 10px;">
                    {{ __('Charte éditoriale') }}
                </p>
                <h1 style="margin: 0 0 14px; font-weight: 800; font-size: clamp(28px, 4vw, 42px); line-height: 1.15; letter-spacing: -0.5px; color: var(--c-dark, #1a1d23);">
                    {{ __('Méthodologie') }} <span style="color: var(--c-primary, #064E5A);">&amp; transparence</span>
                </h1>
                <p style="font-size: 18px; color: #4b5563; margin: 0; line-height: 1.55;">
                    {{ __("Comment La veille sélectionne les outils, source les actualités et garde son indépendance. Voici les règles que je m'impose pour que ce site reste utile et crédible.") }}
                </p>
                <p style="font-size: 13px; color: var(--c-text-muted, #52586a); margin: 16px 0 0;">
                    📅 {{ __('Dernière mise à jour') }} : <time datetime="{{ now()->toDateString() }}">{{ now()->isoFormat('LL') }}</time>
                </p>
            </header>

            <div style="background: linear-gradient(135deg, #F0F4F8 0%, #ffffff 100%); border-left: 4px solid var(--c-primary, #064E5A); padding: 22px 26px; border-radius: 8px; margin-bottom: 40px;">
                <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #1f2937;">
                    <strong>{{ __('Promesse en une ligne') }} :</strong>
                    {{ __("La veille est dirigé par un humain (Stéphane Lapointe), qui en assume la responsabilité éditoriale. Les actualités sont composées avec l'aide de l'IA à partir des sources primaires, puis passées au crible de plusieurs modèles indépendants, dont l'un a pour seul mandat de les contredire. La mention « Vérifié par la rédaction » n'apparaît que sur les fiches qu'un humain a réellement relues. Financement : newsletter, affiliation signalée et boutique. Aucun outil ne paie pour figurer.") }}
                </p>
            </div>

            <article style="font-size: 16px; line-height: 1.7; color: #1f2937;">

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('1. Qui rédige') }}</h2>
                <p>
                    {{ __("Tous les contenus (articles, fiches outils, glossaire, tutoriels) sont sélectionnés et publiés par Stéphane Lapointe, fondateur de Memora Solutions, basé au Québec. Profil, expertise et historique de publication sont disponibles sur la page auteur.") }}
                </p>
                <p>
                    <a href="{{ url('/auteur/stephane-lapointe') }}" style="color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: underline;">→ {{ __('Voir le profil auteur') }}</a>
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('2. Comment les outils sont choisis') }}</h2>
                <p>{{ __("Un outil entre dans l'annuaire si au moins 3 critères sur 5 sont vérifiés :") }}</p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li>{{ __('Pertinence pour un public francophone (Québec, France, Belgique, Suisse) ou utilité reconnue mondialement.') }}</li>
                    <li>{{ __("Qualité de la documentation officielle (site, pricing, conditions d'utilisation accessibles).") }}</li>
                    <li>{{ __("Existence d'une version testable (essai gratuit, freemium ou démo) ou réputation marché établie.") }}</li>
                    <li>{{ __('Conformité minimale RGPD / Loi 25 Québec sur la protection des données.') }}</li>
                    <li>{{ __("Cas d'usage clair pour enseignants, créateurs, professionnels ou étudiants.") }}</li>
                </ul>
                <p>
                    {{ __("Les 282+ outils du répertoire ont été soit ajoutés manuellement, soit issus d'audits sectoriels (Top 50 EdTech, IA générative, accessibilité). Aucun ajout n'est rémunéré : les liens d'affiliation sont signalés et n'influencent pas la sélection.") }}
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('3. Comment les fiches sont notées') }}</h2>
                <p>{{ __("Chaque fiche outil comprend, lorsque l'information est disponible :") }}</p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li><strong>{{ __('Tarification éducation') }}</strong> {{ __('— vérifiée annuellement (gratuit, freemium, rabais ou vérification requise).') }}</li>
                    <li><strong>{{ __('Niveau de langue') }}</strong> {{ __('— interface FR / EN / multilingue, validé par test direct.') }}</li>
                    <li><strong>{{ __('Catégorie') }}</strong> {{ __('— assignée selon une taxonomie de 12 catégories majeures.') }}</li>
                    <li><strong>{{ __('Tutoriels associés') }}</strong> {{ __("— curatés depuis YouTube (chaînes francophones reconnues : École branchée, Alloprof, RÉCIT, Ludovic Nédélec, Le Prof Connecté), validés via YouTube oEmbed.") }}</li>
                    <li><strong>{{ __('Cycle de vie') }}</strong> {{ __('— actif, en déclin ou fermé (signalé visuellement).') }}</li>
                </ul>
                <p>
                    {{ __("Les avis utilisateurs visibles sur les fiches sont modérés : ils n'influencent pas la sélection mais éclairent le choix.") }}
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('4. Fraîcheur des données') }}</h2>
                <p>
                    {{ __("Les outils en évolution rapide (IA générative surtout) sont revérifiés régulièrement : URL, pricing, présence d'un programme éducation, statut du projet (actif / fermé / racheté). Les fiches affichent visuellement la date de dernière vérification et signalent les changements importants par un badge dédié.") }}
                </p>
                <p>
                    {{ __("La veille publie également un Concentré IA hebdomadaire (chaque lundi pour la semaine précédente) qui résume les nouveautés vérifiées. Les flux RSS permettent de suivre automatiquement les concentrés et les nouveautés du répertoire.") }}
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('5. Sources et références') }}</h2>
                <p>{{ __("Pour chaque actualité, La veille s'appuie sur :") }}</p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li>{{ __('Sources primaires : annonces officielles des fournisseurs, dépôts GitHub, documentation API.') }}</li>
                    <li>{{ __("Médias spécialisés reconnus : The Verge, TechCrunch, Le Devoir (volet techno), Radio-Canada, École branchée.") }}</li>
                    <li>{{ __("Recherche académique : Google Scholar, Semantic Scholar, arXiv pour les sujets de fond.") }}</li>
                    <li>{{ __("Pour les guides Québec : MEQ, RÉCIT, Université Laval, Université de Montréal, CDPDJ pour les cadres éthiques.") }}</li>
                </ul>
                <p>
                    {{ __("Les outils d'IA générative (ChatGPT, Claude, Perplexity) sont utilisés comme assistants de recherche et de rédaction, jamais comme source unique. Toute affirmation factuelle est vérifiée avant publication.") }}
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('6. Indépendance éditoriale') }}</h2>
                <p>{{ __('La veille fonctionne sans dépendance directe à un fournisseur. Le modèle de revenus est transparent :') }}</p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li>{{ __("Newsletter gratuite (modèle attention).") }}</li>
                    <li>{{ __("Liens d'affiliation signalés explicitement (mention en pied de page de chaque article concerné).") }}</li>
                    <li>{{ __("Boutique de produits dérivés (chandails, mugs, etc.).") }}</li>
                    <li>{{ __("Aucun publi-reportage déguisé. Tout contenu commandité serait clairement étiqueté « Sponsorisé ».") }}</li>
                </ul>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('7. Corrections et signalement') }}</h2>
                <p>
                    {{ __("Une erreur ou un outil obsolète ? Tout retour est bienvenu via la page Contact ou le bouton de signalement présent sur chaque fiche outil. Les corrections importantes sont datées dans une section de mise à jour visible.") }}
                </p>
                <p>
                    {{ __("Nous nous efforçons de traiter toute demande de correction d'erreur factuelle dans un délai indicatif de 7 jours ouvrables.") }}
                </p>
                <p>
                    <a href="{{ route('contact') }}" style="color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: underline;">→ {{ __('Signaler une erreur ou suggérer un outil') }}</a>
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('8. Accessibilité') }}</h2>
                <p>
                    {{ __("La veille vise le standard WCAG 2.2 niveau AAA pour la conformité d'accessibilité numérique : contrastes 7:1 minimum, cibles cliquables 44 px, navigation clavier complète, sous-titres pour les vidéos intégrées. Les écarts résiduels sont audités et corrigés en continu.") }}
                </p>

                {{-- Ajout additif (design doc SPEC-SIGNAL-HUMAIN, club des sages 93/100,
                     2026-08-20) - la page méthodologie existait déjà (audit du site 2026-08-20) et
                     couvrait sources/corrections, mais pas explicitement les niveaux de preuve
                     affichés sur les fiches ni le processus de vérification en deux couches. Section
                     ajoutée SANS toucher aux sept sections existantes ci-dessus. --}}
                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('9. Niveaux de preuve et vérification des actualités') }}</h2>
                <p>
                    {{ __("Chaque actualité affiche un indicateur de sa proximité avec la source d'origine :") }}
                </p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li><strong>{{ __('Fondée sur la source originale') }}</strong> {{ __('- la fiche s\'appuie directement sur l\'annonce, la publication ou le document primaire.') }}</li>
                    <li><strong>{{ __('Sources originale et média') }}</strong> {{ __('- la fiche croise l\'original et un média qui l\'a relayé ou commenté.') }}</li>
                    <li><strong>{{ __('D\'après un média relais') }}</strong> {{ __('- l\'original n\'était pas accessible directement; la fiche s\'appuie sur un média qui l\'a rapporté, toujours cité.') }}</li>
                </ul>
                <p>
                    {{ __('Ce niveau est déterminé au moment de la rédaction et n\'est jamais affiché sous son étiquette technique brute : la fiche traduit toujours ce niveau en français courant.') }}
                </p>
                <p>
                    {{ __('Chaque actualité passe ensuite par trois couches de vérification distinctes :') }}
                </p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li><strong>{{ __('1. Composition avec preuve') }}</strong> {{ __('- chaque affirmation publiée est reliée en coulisses à un extrait exact du texte source (fait) ou identifiée comme une analyse (interprétation) - aucune affirmation factuelle n\'est publiée sans cette attache. Cette attache est vérifiée par le code, pas au jugé : un extrait qui ne se retrouve pas mot pour mot dans la source bloque la publication.') }}</li>
                    <li><strong>{{ __('2. Contre-vérification par plusieurs modèles d\'IA') }}</strong> {{ __('- la fiche composée est soumise à des modèles indépendants les uns des autres, dont l\'un a pour seul mandat de la contredire : chiffre mal attribué, citation introuvable, nuance manquante, conclusion trop large. Ce qui résiste est publié, ce qui tombe est corrigé ou retiré. Cette couche ne remplace pas le jugement humain : elle le prépare.') }}</li>
                    <li><strong>{{ __('3. Relecture humaine, quand elle a eu lieu') }}</strong> {{ __('- la mention « Vérifié par la rédaction de laveille.ai le [date] » n\'est jamais posée par une routine. Elle atteste qu\'un être humain a relu la fiche et en assume la responsabilité éditoriale. Son absence sur une fiche signifie exactement ce qu\'elle dit : la fiche a été composée et contre-vérifiée, mais pas encore relue par une personne.') }}</li>
                </ul>
                <p>
                    {{ __('L\'attribution de la source d\'origine est systématique : la mention « D\'après [source] » figure en haut de chaque fiche à source unique, les sources primaires citées apparaissent en fin de fiche, et toute photo porte son crédit sous l\'image.') }}
                </p>

                {{-- Module « vérification » (2026-08-21) : la promesse ci-dessus reste creuse tant
                     qu'on ne peut pas la vérifier. Ce paragraphe pointe vers la liste réelle des
                     fiches qui examinent une affirmation - une preuve consultable, pas une
                     déclaration d'intention. --}}
                <h3 style="margin-top: 30px;">{{ __('Quand une affirmation circule et qu\'elle est fausse') }}</h3>
                <p>
                    {{ __('Certaines fiches n\'annoncent pas une nouvelle : elles examinent une affirmation qui circule déjà, souvent virale. Elles portent alors un verdict affiché en clair en haut de la fiche - contenu généré par une IA, citation inexacte, attribution erronée, présentation trompeuse ou contexte manquant - avec l\'affirmation examinée, mot pour mot, et le lien vers l\'endroit où elle circule.') }}
                </p>
                <p>
                    {{ __('Deux règles encadrent ces fiches. Le verdict qualifie toujours l\'affirmation, jamais la personne qui l\'a relayée : se tromper de bonne foi n\'est pas mentir. Et le mot juste prime sur le mot fort : la plupart de ces cas ne sont pas des « fausses nouvelles » au sens strict, mais des propos mal attribués ou sortis de leur contexte.') }}
                </p>
                <p>
                    {{ __('Un site qui juge les affirmations des autres doit accepter d\'être jugé à son tour.') }}
                    <a href="{{ route('contact') }}">{{ __('Si vous estimez qu\'une de ces vérifications est erronée, écrivez-nous avec vos éléments') }}</a>{{ __(' : une vérification qui se révèle fausse est corrigée, et la correction est dite sur la fiche plutôt qu\'effacée en silence.') }}
                </p>
                <p>
                    <a href="{{ route('news.verifications') }}" style="font-weight: 700; color: var(--c-primary, #064E5A);">{{ __('Consulter toutes les vérifications publiées') }} &rarr;</a>
                </p>

            </article>

            <div style="background: #F0F4F8; border-radius: 12px; padding: 26px 28px; margin-top: 44px; text-align: center;">
                <p style="margin: 0 0 14px; font-weight: 700; font-size: 17px; color: var(--c-dark);">
                    {{ __('Une question sur cette méthodologie ?') }}
                </p>
                <a href="{{ route('contact') }}" style="display: inline-block; background: var(--c-primary, #064E5A); color: #fff; font-weight: 700; padding: 12px 26px; border-radius: 50px; text-decoration: none; min-height: 44px; line-height: 20px;">
                    📩 {{ __('Me contacter') }}
                </a>
            </div>

        </div>
    </div>
</section>
@endsection
