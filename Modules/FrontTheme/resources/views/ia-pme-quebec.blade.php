<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "L'IA pour les PME québécoises : outils, concepts et guides - " . config('app.name'))
@section('meta_description', "L'IA aide les PME québécoises à gagner du temps et mieux servir leurs clients. Outils, glossaire, guides et veille hebdomadaire pour adopter l'IA sans équipe technique.")
@push('head')
<meta property="og:title" content="L'IA pour les PME québécoises">
@php
    $pmeFaq = [
        ['Par où commencer avec l\'IA dans une PME ?', "Identifiez d'abord une tâche répétitive ou chronophage où l'IA pourrait vous aider. Commencez par un outil simple, testez-le sur un petit périmètre, et mesurez les gains avant d'aller plus loin."],
        ['Combien ça coûte ?', "Les coûts varient beaucoup : de nombreux outils offrent des versions gratuites ou freemium. Pour une utilisation professionnelle régulière, prévoyez un budget modeste, qui dépendra de vos besoins et du volume d'utilisation."],
        ['L\'IA est-elle sécuritaire pour mes données ?', "Cela dépend de l'outil choisi. Vérifiez toujours où vos données sont stockées et traitées, et privilégiez les solutions qui respectent des normes claires en matière de confidentialité et de conformité."],
    ];
    $pmeJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($q) => [
            '@type' => 'Question',
            'name' => $q[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]],
        ], $pmeFaq),
    ];
@endphp
<script type="application/ld+json">{!! json_encode($pmeJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => "L'IA pour les PME québécoises"])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">L'intelligence artificielle pour les PME québécoises</h1>

                    <p><strong>L'IA aide les PME québécoises à gagner du temps, améliorer leurs processus internes et mieux servir leurs clients</strong> — sans nécessiter une expertise technique poussée. Il n'est pas nécessaire d'avoir une équipe dédiée pour tirer parti des outils d'IA accessibles aujourd'hui. L'enjeu n'est pas de tout automatiser, mais de cibler des tâches précises où l'IA apporte une réelle valeur : rédaction, analyse de données, service à la clientèle ou veille concurrentielle.</p>

                    <p>Pour bien commencer, partez d'un besoin concret plutôt que d'une solution à la mode. Testez d'abord des outils simples, souvent gratuits ou à faible coût, en gardant deux principes : protéger vos données sensibles et vérifier où elles sont traitées. La confidentialité et la conformité aux règles applicables au Québec doivent guider vos choix, surtout si vous manipulez des informations clients.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Explorer les outils</h2>
                    <p>Découvrez une sélection d'outils d'IA pratiques et accessibles, organisés par usage. Ces solutions sont conçues pour être utilisées sans compétences en programmation.</p>
                    <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                        <x-core::button :href="route('directory.index')" variant="primary">Explorer l'annuaire d'outils IA</x-core::button>
                        <x-core::button :href="route('directory.show', 'chatgpt')" variant="secondary" size="sm">ChatGPT</x-core::button>
                        <x-core::button :href="route('directory.show', 'claude')" variant="secondary" size="sm">Claude</x-core::button>
                        <x-core::button :href="route('directory.show', 'perplexity')" variant="secondary" size="sm">Perplexity</x-core::button>
                    </p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Comprendre les concepts</h2>
                    <p>L'IA regorge de termes techniques (modèles, apprentissage automatique, RAG…). Notre glossaire simplifié vous aide à y voir clair et à poser les bonnes questions, même sans bagage technique.</p>
                    <p style="margin:16px 0;"><x-core::button :href="route('dictionary.index')" variant="secondary">Consulter le glossaire IA</x-core::button></p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Guides et analyses</h2>
                    <p>Des articles concrets pour les décideurs de PME : cas d'usage, comparaisons, erreurs à éviter et réflexions stratégiques, sans jargon inutile.</p>
                    <ul>
                        <li><a href="{{ route('guide.adopter-ia-pme') }}" style="color: var(--sys-text-link, #064E5A);">Adopter l'IA dans une PME au Québec : chiffres, freins et aides financières</a></li>
                        <li><a href="{{ route('blog.show', 'cest-quoi-le-mcp-le-guide-simplifie-de-la-revolution-ia-au-quebec') }}" style="color: var(--sys-text-link, #064E5A);">C'est quoi le MCP ? Le guide simplifié de la révolution IA au Québec</a></li>
                        <li><a href="{{ route('blog.show', 'declaration-montreal-ia-responsable') }}" style="color: var(--sys-text-link, #064E5A);">La Déclaration de Montréal pour une IA responsable</a></li>
                        <li><a href="{{ route('blog.index') }}" style="color: var(--sys-text-link, #064E5A);">Tous les guides et analyses du blogue</a></li>
                    </ul>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Rester à jour</h2>
                    <p>L'IA évolue vite. Notre infolettre hebdomadaire propose une veille ciblée : nouveautés utiles, mises en garde et ressources pratiques, en quelques minutes de lecture.</p>
                    <div style="max-width:520px;margin:16px 0;">
                        <x-fronttheme::newsletter-form source="pilier-ia-pme" layout="inline" :show-note="true" />
                    </div>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 36px;">Questions fréquentes</h2>
                    @foreach($pmeFaq as $qa)
                        <h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">{{ $qa[0] }}</h3>
                        <p>{{ $qa[1] }}</p>
                    @endforeach

                    @include('fronttheme::partials.pillars-related', ['current' => 'pillar.ia-pme'])
                </article>
            </div>
        </div>
    </div>
</section>
@endsection
