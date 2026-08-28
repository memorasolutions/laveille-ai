<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "IA et Loi 25 : protéger les renseignements personnels dans le secteur public - " . config('app.name'))
@section('meta_description', "Pourquoi et comment anonymiser les données avant de les confier à une IA, conformément à la Loi 25 sur la protection des renseignements personnels dans le secteur public québécois.")
@push('head')
<meta property="og:title" content="IA et Loi 25 : protéger les renseignements personnels dans le secteur public">
@php
$faq = [
    ["Peut-on mettre des renseignements personnels dans ChatGPT ?", "Non. Les outils d'IA externes comme ChatGPT ne devraient jamais recevoir de renseignements personnels sans anonymisation préalable, car ces données peuvent être stockées, analysées ou transférées hors de votre contrôle, ce qui contrevient aux exigences de la Loi 25."],
    ["Qu'est-ce qu'anonymiser un texte ?", "Anonymiser un texte consiste à retirer ou remplacer toutes les informations permettant d'identifier une personne : noms, adresses, numéros d'assurance sociale, numéros de dossier, données médicales, etc., afin que le texte ne puisse plus être rattaché à un individu."],
    ["L'anonymiseur de laveille.ai envoie-t-il mes données quelque part ?", "Non. L'anonymiseur de laveille.ai fonctionne entièrement dans votre navigateur (100 % local). Vos données ne sont ni envoyées, ni stockées, ni traitées sur un serveur externe."],
];
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($q) => [
        '@type' => 'Question',
        'name' => $q[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]],
    ], $faq),
];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('breadcrumb')
@include('fronttheme::partials.breadcrumb', [
    'breadcrumbTitle' => "IA et Loi 25 dans le secteur public",
    'breadcrumbItems' => ["L'IA dans le secteur public", "IA et Loi 25"],
])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">IA et Loi 25 : protéger les renseignements personnels dans le secteur public</h1>
                    <x-fronttheme::page-freshness updated="2026-06-19" />

                    <p>La Loi 25 (Loi modernisant des dispositions législatives en matière de protection des renseignements personnels) impose aux organismes publics québécois de protéger rigoureusement les renseignements personnels qu'ils détiennent. Cela inclut l'obligation de ne jamais transmettre de tels renseignements à des outils d'intelligence artificielle externes sans précautions strictes.</p>

                    <p>Confier du texte contenant des informations personnelles à une IA non encadrée – comme un agent conversationnel commercial – peut exposer ces données à des traitements non autorisés, souvent hors du Québec. L'anonymisation préalable est donc essentielle.</p>

                    <x-core::answer-box
                        summary="Avant d'utiliser une IA, anonymisez toujours vos textes, vérifiez où les données sont traitées, et validez humainement les résultats. La Loi 25 exige une vigilance constante sur les renseignements personnels."
                        :points="[
                            'Ne jamais coller de données personnelles dans une IA externe sans anonymisation préalable.',
                            'Privilégier des outils locaux ou dont la conformité est vérifiée.',
                            'Remplacer les éléments identifiants par des marqueurs neutres (ex. [NOM], [ADRESSE]).',
                            'Toujours relire et valider la sortie de l\'IA avant toute utilisation officielle.',
                        ]"
                    />

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Ce qu'exige la Loi 25</h2>
                    <p>La Loi 25 renforce trois piliers : la protection des renseignements personnels, la transparence dans leur traitement et la responsabilité des organismes. Elle s'applique aux ministères, municipalités, établissements de santé et autres entités publiques du Québec. À cela s'ajoute l'encadrement du ministère de la Cybersécurité et du Numérique, qui précise des règles pour l'usage des technologies, y compris l'IA.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Pourquoi anonymiser avant d'utiliser une IA</h2>
                    <p>Lorsqu'un texte est collé dans une IA externe, il peut être traité, enregistré ou utilisé pour améliorer des modèles, souvent sans que vous puissiez le contrôler. Cela représente un risque de fuite ou d'usage inapproprié de données sensibles. L'anonymisation retire les éléments identifiants (noms, adresses, numéro d'assurance sociale, numéros de dossier, données médicales), transformant le texte en une version utilisable sans porter atteinte à la vie privée.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Comment faire concrètement</h2>
                    <p>Utilisez un outil d'anonymisation local : le texte reste dans votre navigateur et n'est jamais envoyé à un serveur. Remplacez les données sensibles par des marqueurs génériques (ex. [PATIENT], [DOSSIER]), soumettez ce texte à l'IA, puis réinsérez vous-même les vraies informations dans la réponse obtenue. <a href="{{ url('/outils/anonymiseur') }}" style="color: var(--sys-text-link, #064E5A);">L'anonymiseur de laveille.ai</a> fonctionne à 100 % localement, sans transfert de données.</p>

                    <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                        <x-core::button :href="url('/outils/anonymiseur')" variant="primary">Utiliser l'anonymiseur (100 % local)</x-core::button>
                        <x-core::button :href="route('pillar.ia-secteur-public')" variant="secondary" size="sm">Retour au dossier</x-core::button>
                        <x-core::button :href="route('dictionary.index')" variant="secondary" size="sm">Glossaire Techno</x-core::button>
                    </p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Rester à jour</h2>
                    <div style="max-width:520px;margin:16px 0;">
                        <x-fronttheme::newsletter-form source="sous-article-sp-loi25" layout="inline" :show-note="true" />
                    </div>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Questions fréquentes</h2>
                    @foreach($faq as $qa)
                        <h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">{{ $qa[0] }}</h3>
                        <p>{{ $qa[1] }}</p>
                    @endforeach

                    @include('fronttheme::partials.pillars-related', ['current' => 'pillar.ia-secteur-public'])
                </article>
            </div>
        </div>
    </div>
</section>
@endsection
