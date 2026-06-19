<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "État de l'IA au Québec en 2026 : adoption, encadrement et perception - " . config('app.name'))
@section('meta_description', "Compilation sourcée des statistiques sur l'IA au Québec en 2026 : adoption par les entreprises (ISQ, Statistique Canada) et les travailleurs (KPMG), usage par la population (SOM, Protégez-Vous), encadrement public (arrêtés, Loi 25) et perception (CIRA).")
@push('head')
<meta property="og:title" content="État de l'IA au Québec en 2026 : adoption, encadrement et perception">
@php
    $pageUrl = url('/etat-ia-quebec-2026');

    // (a) JSON-LD Article - tableau PHP (jamais spatie/schema-org).
    $eiqArticle = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => "État de l'IA au Québec en 2026 : adoption, encadrement et perception",
        'description' => "Compilation de statistiques publiques sur l'adoption, l'encadrement et la perception de l'intelligence artificielle au Québec et au Canada en 2026, avec attribution des sources (ISQ, Statistique Canada, KPMG, CIRA, Protégez-Vous, SOM).",
        'datePublished' => '2026-06-19',
        'dateModified' => '2026-06-19',
        'inLanguage' => 'fr-CA',
        'author' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
        'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $pageUrl],
    ];

    // (b) JSON-LD Dataset - citation magnet pour LLM.
    $eiqDataset = [
        '@context' => 'https://schema.org',
        '@type' => 'Dataset',
        'name' => "État de l'IA au Québec en 2026 (compilation)",
        'description' => "Compilation de statistiques publiques sur l'adoption, l'encadrement et la perception de l'intelligence artificielle au Québec et au Canada en 2026.",
        'creator' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
        'dateModified' => '2026-06-19',
        'inLanguage' => 'fr-CA',
        'license' => 'https://creativecommons.org/licenses/by/4.0/',
        'variableMeasured' => [
            "Taux d'adoption de l'IA par les entreprises",
            "Usage de l'IA générative par les travailleurs",
            'Perception et confiance du public',
        ],
        'citation' => [
            'Institut de la statistique du Québec (ISQ)',
            'Statistique Canada',
            'KPMG',
            'CIRA',
            'Protégez-Vous',
            'SOM',
        ],
    ];

    // (c) JSON-LD FAQPage - reprend les 3 Q/R de la page.
    $eiqFaq = [
        [
            "Quelle est la proportion d'entreprises québécoises qui utilisent l'IA en 2026 ?",
            "Selon l'Institut de la statistique du Québec, 12,7 % des entreprises québécoises ont utilisé des applications d'IA à des fins de production au cours des 12 mois précédant le 2e trimestre 2025. Statistique Canada observe pour sa part que la part d'entreprises canadiennes utilisant l'IA pour produire des biens ou des services est passée d'environ 6 % en 2023-2024 à 12 % en 2024-2025.",
        ],
        [
            "Comment l'IA est-elle encadrée dans le secteur public québécois ?",
            "L'utilisation de l'IA générative par les organismes assujettis à la Loi sur la gouvernance et la gestion des ressources informationnelles (LGGRI) est encadrée par l'arrêté 2025-02 du ministre de la Cybersécurité et du Numérique (3 décembre 2025), qui s'articule avec la Loi 25. Le cadre impose une démarche de gestion des risques en 6 étapes et un principe de souveraineté des données.",
        ],
        [
            "Les Québécois font-ils confiance à l'IA ?",
            "La confiance reste prudente. Selon la CIRA, 51 % des Canadiens se disent préoccupés par l'IA générative contre 17 % enthousiastes. Selon SOM, le soutien au développement de l'IA chez les Québécois est passé de 39 % en 2023 à 47 % en 2025, mais 58 % croient que l'IA entraînera des pertes d'emplois.",
        ],
    ];
    $eiqFaqJsonLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($q) => ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]], $eiqFaq)];
@endphp
<script type="application/ld+json">{!! json_encode($eiqArticle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($eiqDataset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($eiqFaqJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('breadcrumb') @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => "État de l'IA au Québec en 2026"]) @endsection
@section('content')
<section class="wpo-blog-single-section section-padding"><div class="container"><div class="row justify-content-center"><div class="col-lg-9"><article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

<h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">État de l'IA au Québec en 2026 : adoption, encadrement et perception</h1>
<x-fronttheme::page-freshness updated="2026-06-19" />

<p>Où en est l'intelligence artificielle (IA) au Québec en 2026 ? Cette page rassemble les chiffres publics les plus récents sur trois dimensions : l'adoption (par les entreprises, les travailleurs et la population), l'encadrement (les règles du secteur public et la Loi 25) et la perception (confiance, préoccupations et compréhension). Chaque donnée est attribuée à sa source : données officielles (Institut de la statistique du Québec, Statistique Canada), sondages privés (KPMG, SOM, CIRA, Protégez-Vous, Microsoft) et observations internes de La veille de Stef. Aucune donnée n'est inventée.</p>

<x-core::answer-box summary="En 2026, l'IA progresse au Québec mais de façon inégale : environ une entreprise sur huit l'utilise en production, alors qu'une majorité de travailleurs canadiens s'en servent déjà au quotidien. L'encadrement public s'est renforcé (arrêté 2025-02 du ministère de la Cybersécurité et du Numérique, articulé avec la Loi 25). La confiance reste prudente : davantage de gens se disent préoccupés qu'enthousiastes." :points="['~13 % des entreprises québécoises utilisent l\'IA en production (ISQ, 2025).','~51 % des travailleurs canadiens utilisent l\'IA générative (KPMG, 2025).','53 % des internautes québécois utilisent ChatGPT (SOM, 2025).','Encadrement renforcé du secteur public (arrêté 2025-02, MCN) articulé avec la Loi 25.','51 % des Canadiens préoccupés contre 17 % enthousiastes (CIRA).']" />

<h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">L'adoption de l'IA : entreprises, travailleurs, population</h2>

<h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">Les entreprises</h3>
<p>Selon l'Institut de la statistique du Québec (ISQ), 12,7 % des entreprises québécoises ont utilisé des applications d'IA à des fins de production au cours des 12 mois précédant le 2e trimestre 2025.</p>
<p>À l'échelle canadienne, Statistique Canada observe que la part d'entreprises utilisant l'IA pour produire des biens ou des services est passée d'environ 6 % en 2023-2024 à 12 % en 2024-2025. Plus tôt, au 1er trimestre 2024, Statistique Canada indiquait que 9,3 % des entreprises canadiennes utilisaient déjà l'IA générative, que 4,6 % prévoyaient de l'adopter et que 72,7 % ne prévoyaient pas l'utiliser. L'adoption variait fortement selon le secteur : 24,1 % dans l'information et la culture, 18,8 % dans les services professionnels, scientifiques et techniques, et 14,7 % chez les entreprises de 100 employés et plus.</p>

<h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">Les travailleurs</h3>
<p>Selon l'Indice d'adoption de l'IA générative 2025 de KPMG, 51 % des employés canadiens utilisent l'IA générative au travail, contre 46 % en 2024 et 22 % en 2023. Selon le rapport canadien 2026 de l'Indice des tendances du travail de Microsoft, 54 % des utilisateurs d'IA au Canada affirment produire un travail qu'ils n'auraient pas pu réaliser un an plus tôt.</p>

<h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">La population québécoise</h3>
<p>Selon SOM (2025), 53 % des internautes québécois utilisent ChatGPT, une proportion qui grimpe à 92 % chez les 18-24 ans. Selon Protégez-Vous, environ 33 % des adultes internautes québécois utilisaient l'IA générative au début de 2024 (58 % chez les 18-34 ans), et près de 60 % des utilisateurs y recourent au moins une fois par mois.</p>

<h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">L'encadrement de l'IA au Québec</h2>
<p>L'arrêté 2025-02 du ministre de la Cybersécurité et du Numérique (3 décembre 2025), intitulé « Mesures applicables lors de l'utilisation de l'intelligence artificielle générative », encadre l'usage de l'IA générative par les organismes assujettis à la Loi sur la gouvernance et la gestion des ressources informationnelles (LGGRI). Il s'ajoute à l'arrêté 2024-05 (12 décembre 2024), qui définit un modèle de classification de sécurité des données numériques gouvernementales (Protégé C, B, A et Non classifié).</p>
<p>Selon le gouvernement du Québec, ce cadre impose une démarche de gestion des risques en 6 étapes (à l'aide des outils ARP et ÉFVP-R), un principe de souveraineté des données (hébergées au Québec ou dans une juridiction équivalente) et s'articule avec la Loi 25 sur la protection des renseignements personnels.</p>
<p style="margin:16px 0;"><x-core::button :href="url('/ia-secteur-public-quebec')" variant="primary">L'IA dans le secteur public québécois</x-core::button></p>

<h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Perception et confiance</h2>
<p>Selon la CIRA, 51 % des Canadiens se disent préoccupés par l'IA générative, contre 17 % enthousiastes. Parmi les personnes préoccupées, 69 % citent les hypertrucages, 67 % la mésinformation et 65 % l'insuffisance de la réglementation.</p>
<p>Selon Protégez-Vous, seulement 10 % des internautes québécois estiment bien comprendre l'IA, 56 % ont de la difficulté à reconnaître quand une technologie utilise l'IA et 49 % citent la protection des données comme préoccupation majeure.</p>
<p>Selon SOM, le soutien au développement de l'IA chez les Québécois est passé de 39 % en 2023 à 47 % en 2025. Toutefois, 58 % croient que l'IA entraînera des pertes d'emplois, et 44 % perçoivent un impact positif sur la vie quotidienne contre 24 % qui le perçoivent comme négatif.</p>

<h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Ce que consulte le public de La veille</h2>
<p>Du côté de nos propres données (Google Analytics, 90 derniers jours), les contenus les plus consultés sur laveille.ai sont les outils pratiques. Le constructeur de prompts arrive en tête, suivi de l'annuaire d'outils, de l'anonymiseur et du glossaire. Les durées de consultation s'étalent sur plusieurs minutes, ce qui suggère un public davantage en quête d'outils concrets et de vulgarisation que de théorie. Ces observations sont internes et qualitatives : elles décrivent une tendance d'usage, pas une statistique représentative de l'ensemble de la population.</p>
<p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
    <x-core::button :href="url('/outils/constructeur-prompts')" variant="primary">Constructeur de prompts</x-core::button>
    <x-core::button :href="url('/annuaire')" variant="secondary" size="sm">Annuaire d'outils IA</x-core::button>
    <x-core::button :href="url('/glossaire')" variant="secondary" size="sm">Glossaire IA</x-core::button>
</p>

<h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Questions fréquentes</h2>
@foreach($eiqFaq as $qa)
    <h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">{{ $qa[0] }}</h3>
    <p>{{ $qa[1] }}</p>
@endforeach

<h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Sources</h2>
<ul>
    <li><a href="https://statistique.quebec.ca/fr/document/intelligence-artificielle-entreprises-quebec" rel="noopener" target="_blank" style="color: var(--sys-text-link, #064E5A);">Institut de la statistique du Québec (ISQ)</a></li>
    <li><a href="https://www.statcan.gc.ca/o1/fr/plus/5847-quelles-entreprises-canadiennes-utilisent-lintelligence-artificielle-generative-et" rel="noopener" target="_blank" style="color: var(--sys-text-link, #064E5A);">Statistique Canada</a></li>
    <li><a href="https://kpmg.com/ca/fr/services/digital/ai-services/generative-ai-adoption-index.html" rel="noopener" target="_blank" style="color: var(--sys-text-link, #064E5A);">KPMG (Indice d'adoption de l'IA générative 2025)</a></li>
    <li><a href="https://news.microsoft.com/source/canada/2026/05/28/rapport-canadien-2026-de-lindice-des-tendances-du-travail/?lang=fr" rel="noopener" target="_blank" style="color: var(--sys-text-link, #064E5A);">Microsoft (Indice des tendances du travail, rapport canadien 2026)</a></li>
    <li><a href="https://blogue.som.ca/chatgpt-de-la-curiosite-a-lomnipresence/" rel="noopener" target="_blank" style="color: var(--sys-text-link, #064E5A);">SOM</a></li>
    <li><a href="https://www.protegez-vous.ca/technologie/enchiffres-ia-craintes" rel="noopener" target="_blank" style="color: var(--sys-text-link, #064E5A);">Protégez-Vous</a></li>
    <li><a href="https://www.cira.ca/fr/ressources/nouvelles/etat-de-linternet/un-nouveau-sondage-revele-que-la-moitie-des-canadien%C2%B7nes-est-preoccupee-par-lintelligence-artificielle-generative-et-la-propagation-de-mesinformation/" rel="noopener" target="_blank" style="color: var(--sys-text-link, #064E5A);">CIRA</a></li>
    <li><a href="https://www.quebec.ca/gouvernement/services-organisations-publiques/services-transformation-numerique/accompagnement-des-organismes-publics/accompagner-les-organismes-publics-en-intelligence-artificielle/obligations-et-encadrement-de-lintelligence-artificielle" rel="noopener" target="_blank" style="color: var(--sys-text-link, #064E5A);">Gouvernement du Québec (encadrement de l'IA)</a></li>
</ul>

<h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Rester à jour</h2>
<p>Recevez chaque semaine une veille fiable sur l'IA au Québec : nouvelles données, outils vérifiés et bonnes pratiques, sans le superflu.</p>
<div style="max-width:520px;margin:16px 0;">
    <x-fronttheme::newsletter-form source="etat-ia-quebec-2026" layout="inline" :show-note="true" />
</div>

@include('fronttheme::partials.pillars-related', ['current' => 'pillar.ia-secteur-public'])
</article></div></div></div></section>
@endsection
