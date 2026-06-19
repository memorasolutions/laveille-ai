<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "IA générative : capacités, limites et bonnes pratiques - " . config('app.name'))
@section('meta_description', "Comprendre l'IA générative : les types (texte, image, audio, vidéo, code), comment ça fonctionne, les limites (hallucinations, biais) et les bonnes pratiques d'usage.")
@push('head')
<meta property="og:title" content="IA générative : capacités, limites et bonnes pratiques">
@php
$faq = [
    ["Qu'est-ce que l'IA générative ?", "L'IA générative crée du contenu nouveau (texte, image, son, vidéo, code) à partir d'instructions appelées « prompts ». Elle repose sur des modèles entraînés sur de grandes quantités de données, qui prédisent l'élément suivant le plus probable."],
    ["Pourquoi l'IA invente-t-elle parfois des informations ?", "C'est ce qu'on appelle une « hallucination ». Le modèle calcule la suite la plus plausible sans vérifier la véracité : il peut donc produire une information fausse mais formulée de façon convaincante. Il faut toujours recouper les faits."],
    ["Comment utiliser l'IA générative de façon responsable ?", "Vérifiez les faits avec des sources fiables, gardez un humain dans la boucle pour les contenus importants, ne soumettez pas de données confidentielles à des outils publics et restez prudent sur les droits d'auteur."]
];
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn ($q) => [
        '@type' => 'Question',
        'name' => $q[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]],
    ], $faq),
];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => "IA générative : capacités et limites", 'breadcrumbItems' => ["IA générative", "IA générative : capacités et limites"]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">IA générative : capacités, limites et bonnes pratiques</h1>
                    <x-fronttheme::page-freshness updated="2026-06-19" />

                    <p><strong>L'IA générative crée du contenu nouveau — texte, image, son, vidéo ou code — à partir d'instructions appelées « prompts ».</strong> Ces outils sont devenus largement accessibles, mais en tirer parti suppose d'en comprendre le fonctionnement et les limites. Bien utilisée, l'IA générative fait gagner du temps ; mal comprise, elle peut induire en erreur.</p>
                    <p>Ce dossier fait le tour de l'essentiel : les grands types d'outils, le principe de fonctionnement, les limites à connaître et les bonnes pratiques d'un usage responsable.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Les types d'IA générative</h2>
                    <p>On distingue généralement les outils selon la nature du contenu qu'ils produisent.</p>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li><strong>Texte</strong> : rédaction, résumés, dialogues (par exemple ChatGPT, Claude, Gemini).</li>
                        <li><strong>Image</strong> : illustrations et visuels à partir d'une description (par exemple DALL·E, Midjourney, Stable Diffusion).</li>
                        <li><strong>Audio et voix</strong> : synthèse vocale et génération musicale (par exemple ElevenLabs, Suno).</li>
                        <li><strong>Vidéo</strong> : génération de séquences à partir de texte ou d'images (par exemple Sora, Runway, Veo).</li>
                        <li><strong>Code</strong> : génération et complétion de code informatique (par exemple GitHub Copilot, Cursor).</li>
                    </ul>
                    <p>Certains modèles sont « multimodaux » : ils combinent plusieurs de ces formats (texte, image, audio) au sein d'un même système.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Comment ça fonctionne</h2>
                    <p>Un modèle d'IA générative est entraîné sur de très grandes quantités de données (textes, images, sons). Au cours de cet entraînement, il apprend des régularités statistiques : quels mots, pixels ou sons ont tendance à se suivre. À partir d'un prompt, il génère ensuite un contenu en prédisant, de façon itérative, l'élément suivant le plus probable.</p>
                    <p>Un point important pour bien l'utiliser : le modèle ne « comprend » pas au sens humain. Il ne vérifie pas la véracité de ce qu'il produit — il calcule la suite la plus plausible à partir de ce qu'il a appris. Cette nuance explique à la fois sa puissance et ses limites.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Les limites à connaître</h2>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li><strong>Hallucinations</strong> : l'outil peut produire des informations fausses, mais formulées de manière convaincante.</li>
                        <li><strong>Biais</strong> : il peut reproduire ou amplifier des biais présents dans ses données d'entraînement.</li>
                        <li><strong>Absence de compréhension réelle</strong> : il manipule des probabilités, sans saisir le sens ni valider les faits.</li>
                        <li><strong>Dépendance au prompt</strong> : la qualité du résultat dépend fortement de la façon dont la demande est formulée.</li>
                        <li><strong>Connaissances figées</strong> : les données d'entraînement s'arrêtent à une certaine date, d'où des informations parfois dépassées.</li>
                    </ul>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Bonnes pratiques</h2>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li>Vérifier les faits en les recoupant avec des sources fiables, surtout pour les usages sensibles.</li>
                        <li>Garder un humain dans la boucle pour valider les contenus importants.</li>
                        <li>Protéger les données personnelles : ne pas soumettre d'informations confidentielles à des outils publics.</li>
                        <li>Faire preuve de prudence sur les droits d'auteur et vérifier les conditions d'utilisation des outils.</li>
                        <li>Indiquer, selon le contexte, quand un contenu a été généré ou assisté par l'IA.</li>
                    </ul>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Pour aller plus loin</h2>
                    <p>Explorez les concepts clés dans le glossaire, comparez les outils dans l'annuaire, et approfondissez le sujet dans le dossier thématique.</p>
                    <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                        <x-core::button :href="route('pillar.ia-generative')" variant="primary">Retour au dossier : l'IA générative</x-core::button>
                        <x-core::button :href="route('dictionary.index')" variant="secondary" size="sm">Glossaire IA</x-core::button>
                        <x-core::button :href="route('directory.index')" variant="secondary" size="sm">Explorer les outils</x-core::button>
                    </p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Rester à jour</h2>
                    <div style="max-width:520px;margin:16px 0;">
                        <x-fronttheme::newsletter-form source="sous-article-ia-generative" layout="inline" :show-note="true" />
                    </div>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Questions fréquentes</h2>
                    @foreach($faq as $qa)
                        <h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">{{ $qa[0] }}</h3>
                        <p>{{ $qa[1] }}</p>
                    @endforeach

                    @include('fronttheme::partials.pillars-related', ['current' => 'pillar.ia-generative'])
                </article>
            </div>
        </div>
    </div>
</section>
@endsection
