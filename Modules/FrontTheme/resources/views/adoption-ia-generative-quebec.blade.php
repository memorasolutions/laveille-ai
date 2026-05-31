<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "L'adoption de l'IA générative au Québec : les chiffres - " . config('app.name'))
@section('meta_description', "En 2025, 52 % des adultes québécois ont utilisé l'IA générative (vs 33 % en 2024). Évolution, outils les plus utilisés, profils d'âge et usages. Données NETendances.")
@push('head')
<meta property="og:title" content="L'adoption de l'IA générative au Québec : les chiffres">
@php
$faq = [
    ["Combien de Québécois utilisent l'IA générative ?", "En 2025, 52 % des adultes québécois déclaraient avoir utilisé l'IA générative, contre 33 % en 2024 (Académie de la transformation numérique, Université Laval). Les données antérieures situaient ce taux autour de 16 % en 2023."],
    ["Quel est l'outil le plus utilisé au Québec ?", "ChatGPT domine nettement : environ 84 % des personnes utilisant l'IA générative y ont recours en 2025 (Académie de la transformation numérique). D'autres outils comme Copilot et Gemini suivent plus loin."],
    ["Qui utilise le plus l'IA générative ?", "Les 18-34 ans sont les plus grands utilisateurs, avec des taux supérieurs à la moyenne, tandis que l'adoption diminue avec l'âge (Académie de la transformation numérique, 2025)."]
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
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => "L'adoption de l'IA générative au Québec", 'breadcrumbItems' => ["Faire sa veille IA", "L'adoption de l'IA générative au Québec"]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">L'adoption de l'IA générative au Québec : les chiffres</h1>

                    <p><strong>En 2025, 52 % des adultes québécois déclaraient avoir utilisé l'IA générative, contre 33 % un an plus tôt (Académie de la transformation numérique, Université Laval).</strong> Cette progression rapide témoigne d'une appropriation croissante de ces outils dans la vie quotidienne, professionnelle et scolaire. Comprendre qui les utilise, avec quels outils et dans quels buts permet de mieux saisir les transformations en cours.</p>
                    <p>Ce dossier présente l'évolution de l'adoption de l'IA générative au Québec, les outils les plus populaires, les profils d'utilisateurs et les usages les plus fréquents — et ce que cela implique pour une veille éclairée.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Une adoption en forte progression</h2>
                    <p>En 2025, <strong>52 %</strong> des adultes québécois ont utilisé l'IA générative, contre <strong>33 %</strong> en 2024 (Académie de la transformation numérique, Université Laval). Les données antérieures situaient ce taux autour de <strong>16 %</strong> en 2023, ce qui illustre une accélération rapide en deux ans. Cette croissance soutenue reflète une intégration progressive de ces technologies dans les habitudes des Québécois, à mesure que les outils deviennent plus accessibles et mieux connus.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Quels outils ?</h2>
                    <p>ChatGPT domine nettement : environ <strong>84 %</strong> des personnes utilisant l'IA générative y ont recours en 2025 (Académie de la transformation numérique). D'autres outils comme Copilot et Gemini suivent plus loin. Cette concentration autour d'un acteur principal illustre la notoriété acquise par certaines plateformes, même si l'offre se diversifie progressivement.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Qui utilise l'IA générative ?</h2>
                    <p>Les <strong>18-34 ans</strong> sont les plus grands utilisateurs, avec des taux supérieurs à la moyenne, tandis que l'adoption diminue avec l'âge, les <strong>55 ans et plus</strong> affichant les taux les plus faibles (Académie de la transformation numérique, 2025). Cet écart générationnel souligne des différences d'aisance et d'exposition aux outils numériques selon les groupes d'âge, un facteur à considérer dans toute démarche de sensibilisation ou de formation.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Pour quels usages ?</h2>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li>Recherche d'information.</li>
                        <li>Rédaction et révision de textes.</li>
                        <li>Aide au travail ou aux études.</li>
                        <li>Création de contenu.</li>
                    </ul>
                    <p>Ces usages reflètent une utilisation surtout pratique et fonctionnelle de l'IA générative, orientée vers le gain de temps et le soutien aux tâches courantes.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Ce que ça implique pour votre veille</h2>
                    <p>Dans un contexte d'adoption rapide, une veille structurée devient précieuse : choisir des sources fiables, filtrer le bruit ambiant et conserver un esprit critique permettent de distinguer les usages réellement utiles des simples effets de mode. Suivre l'évolution des outils et des pratiques aide à faire des choix éclairés, sans céder à la précipitation.</p>
                    <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                        <x-core::button :href="route('pillar.veille-ia')" variant="primary">Retour au dossier : faire sa veille IA</x-core::button>
                        <x-core::button :href="route('dictionary.index')" variant="secondary" size="sm">Glossaire IA</x-core::button>
                        <x-core::button :href="route('directory.index')" variant="secondary" size="sm">Explorer les outils</x-core::button>
                    </p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Rester à jour</h2>
                    <div style="max-width:520px;margin:16px 0;">
                        <x-fronttheme::newsletter-form source="sous-article-veille-adoption" layout="inline" :show-note="true" />
                    </div>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Questions fréquentes</h2>
                    @foreach($faq as $qa)
                        <h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">{{ $qa[0] }}</h3>
                        <p>{{ $qa[1] }}</p>
                    @endforeach

                    @include('fronttheme::partials.pillars-related', ['current' => 'pillar.veille-ia'])
                </article>
            </div>
        </div>
    </div>
</section>
@endsection
