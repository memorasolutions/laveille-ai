<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "L'IA en éducation au Québec : étudiants, TDAH et apprentissage - " . config('app.name'))
@section('meta_description', "L'IA peut aider les étudiants québécois, dont ceux avec un TDAH, à organiser, résumer et structurer leur travail. Outils, glossaire, guides (NotebookLM, TDAH) et veille.")
@push('head')
<meta property="og:title" content="L'IA en éducation au Québec : étudiants, TDAH et apprentissage">
@php
    $eduFaq = [
        ["Comment l'IA aide un étudiant avec un TDAH ?", "L'IA peut faciliter la prise de notes, la planification des tâches et la reformulation de contenus complexes, ce qui allège la charge cognitive et aide à rester concentré sur l'essentiel."],
        ["Est-ce de la triche d'utiliser l'IA pour étudier ?", "Cela dépend des règles de votre établissement. Utilisée comme soutien à la compréhension, et non pour remplacer votre propre travail, l'IA peut être un outil légitime d'apprentissage."],
        ["Quels outils gratuits pour commencer ?", "Plusieurs outils d'IA sont accessibles gratuitement, mais il est important de bien lire leurs conditions d'utilisation, de protéger vos données personnelles et de toujours valider l'information qu'ils génèrent."],
    ];
    $eduJsonLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($q) => ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]], $eduFaq)];
@endphp
<script type="application/ld+json">{!! json_encode($eduJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => "L'IA en éducation au Québec"])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container"><div class="row justify-content-center"><div class="col-lg-9">
        <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
            <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">L'IA en éducation au Québec : étudiants, TDAH et apprentissage</h1>
            <x-fronttheme::page-freshness updated="2026-06-19" />

            <p><strong>L'IA peut aider les étudiants québécois, y compris ceux ayant un TDAH ou un trouble d'apprentissage, à mieux organiser leurs idées, structurer leur travail et rendre l'apprentissage plus accessible.</strong> Des outils simples permettent de résumer des textes, reformuler des explications, classer des notes ou planifier des échéances — autant de tâches qui peuvent représenter des défis concrets, surtout en contexte d'attention variable ou de charge cognitive élevée.</p>
            <p>Il est toutefois essentiel d'utiliser ces outils avec prudence. L'IA ne remplace pas la compréhension personnelle ni le jugement critique. Il faut toujours vérifier l'exactitude de l'information produite, respecter les règles de son établissement, et s'assurer que l'outil sert à soutenir l'apprentissage, et non à faire le travail à sa place.</p>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Outils pour apprendre</h2>
            <p>Une sélection d'outils d'IA pour soutenir l'organisation, la lecture et la rédaction, selon les besoins variés des étudiants.</p>
            <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                <x-core::button :href="route('directory.index')" variant="primary">Explorer les outils</x-core::button>
                <x-core::button :href="route('directory.show', 'notebooklm')" variant="secondary" size="sm">NotebookLM</x-core::button>
            </p>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Comprendre les concepts</h2>
            <p>Un glossaire simple pour démystifier les termes liés à l'IA, sans jargon inutile.</p>
            <p style="margin:16px 0;"><x-core::button :href="route('dictionary.index')" variant="secondary">Consulter le glossaire IA</x-core::button></p>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Guides pratiques</h2>
            <p>Des articles concrets pour utiliser l'IA de façon éthique et efficace dans les études.</p>
            <ul>
                <li><a href="{{ route('guide.ia-etudier') }}" style="color: var(--sys-text-link, #064E5A);">L'IA pour étudier au Québec : usages, bénéfices et règles</a></li>
                <li><a href="{{ route('blog.show', 'la-plupart-des-etudiants-tdah-utilisent-mal-lia-voici-le-guide-complet-et-concret') }}" style="color: var(--sys-text-link, #064E5A);">La plupart des étudiants TDAH utilisent mal l'IA : le guide complet et concret</a></li>
                <li><a href="{{ route('blog.show', 'notebooklm-le-guide-complet-pour-maitriser-toutes-les-options-expliquees-simplement') }}" style="color: var(--sys-text-link, #064E5A);">Guide complet de NotebookLM expliqué simplement</a></li>
                <li><a href="{{ route('blog.index') }}" style="color: var(--sys-text-link, #064E5A);">Tous les guides du blogue</a></li>
            </ul>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Rester à jour</h2>
            <p>Recevez chaque semaine des ressources utiles et des trucs pratiques sur l'IA en éducation.</p>
            <div style="max-width:520px;margin:16px 0;"><x-fronttheme::newsletter-form source="pilier-ia-education" layout="inline" :show-note="true" /></div>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Questions fréquentes</h2>
            @foreach($eduFaq as $qa)
                <h3 style="font-family: var(--f-heading); font-size: 1.1rem; margin-top: 20px;">{{ $qa[0] }}</h3>
                <p>{{ $qa[1] }}</p>
            @endforeach
            @include('fronttheme::partials.pillars-related', ['current' => 'pillar.ia-education'])
        </article>
    </div></div></div>
</section>
@endsection
