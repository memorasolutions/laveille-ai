<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "IA générative : créer du texte, des images et des vidéos - " . config('app.name'))
@section('meta_description', "L'IA générative crée du texte, des images, de l'audio et des vidéos à partir de prompts. Outils, glossaire, bonnes pratiques (vérification, droits) et veille hebdomadaire.")
@push('head')
<meta property="og:title" content="IA générative : créer du texte, des images et des vidéos">
@php
    $gFaq = [
        ["Qu'est-ce que l'IA générative ?", "L'IA générative désigne des systèmes capables de produire du contenu nouveau (texte, image, son, vidéo) à partir de modèles entraînés sur de grandes quantités de données existantes."],
        ["Qui détient les droits sur un contenu généré par IA ?", "La situation juridique évolue et varie selon les pays, les outils et les contextes d'usage. Il est prudent de consulter les conditions d'utilisation de chaque plateforme et les lois applicables, sans présumer de la titularité des droits."],
        ["Comment éviter les hallucinations ?", "Vérifiez toujours les faits avec des sources fiables, croisez l'information et faites relire le contenu par un humain. L'IA ne remplace pas la validation humaine."],
    ];
    $gJsonLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($q) => ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]], $gFaq)];
@endphp
<script type="application/ld+json">{!! json_encode($gJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => "IA générative"])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container"><div class="row justify-content-center"><div class="col-lg-9">
        <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
            <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">IA générative : créer du texte, des images et des vidéos</h1>

            <p><strong>L'IA générative permet de créer du texte, des images, de l'audio et des vidéos à partir d'instructions simples, appelées « prompts ».</strong> Elle sert à rédiger des courriels, générer des visuels originaux, prototyper des idées ou produire du contenu multimédia. Ces outils, accessibles à tous, transforment la façon de concevoir et de produire, dans les sphères éducatives, professionnelles et créatives.</p>
            <p>Ces systèmes ne sont toutefois pas infaillibles : ils peuvent produire des contenus inexacts (les « hallucinations »), de qualité inégale ou biaisés. Il est essentiel de vérifier les informations, de conserver un regard critique et humain, et de respecter les droits d'auteur et les conditions d'utilisation des plateformes. L'IA est un outil d'assistance, non une source d'autorité.</p>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Explorer les outils</h2>
            <p>Une sélection d'outils d'IA générative pour le texte, les images et la vidéo, par type d'usage.</p>
            <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                <x-core::button :href="route('directory.index')" variant="primary">Explorer les outils</x-core::button>
                <x-core::button :href="route('directory.show', 'midjourney')" variant="secondary" size="sm">Midjourney</x-core::button>
                <x-core::button :href="route('directory.show', 'dall-e')" variant="secondary" size="sm">DALL-E</x-core::button>
                <x-core::button :href="route('directory.show', 'jasper-ai')" variant="secondary" size="sm">Jasper</x-core::button>
                <x-core::button :href="route('directory.show', 'runway')" variant="secondary" size="sm">Runway</x-core::button>
            </p>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Comprendre les concepts</h2>
            <p>Clarifiez les termes comme « prompt », « IA générative » ou « hallucination ».</p>
            <ul>
                <li><a href="{{ route('dictionary.show', 'ia-generative') }}" style="color: var(--sys-text-link, #064E5A);">IA générative</a></li>
                <li><a href="{{ route('dictionary.show', 'prompt') }}" style="color: var(--sys-text-link, #064E5A);">Prompt</a></li>
                <li><a href="{{ route('dictionary.show', 'hallucination-ia') }}" style="color: var(--sys-text-link, #064E5A);">Hallucination</a></li>
            </ul>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Bonnes pratiques</h2>
            <p>Utilisez l'IA de façon éthique : vérifiez toujours les faits, respectez les droits d'auteur et gardez un contrôle humain sur le contenu final.</p>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Rester à jour</h2>
            <p>Recevez des mises à jour fiables sur les évolutions de l'IA générative.</p>
            <div style="max-width:520px;margin:16px 0;"><x-fronttheme::newsletter-form source="pilier-ia-generative" layout="inline" :show-note="true" /></div>

            <h2 style="font-family: var(--f-heading); margin-top: 36px;">Questions fréquentes</h2>
            @foreach($gFaq as $qa)
                <h3 style="font-family: var(--f-heading); font-size: 1.1rem; margin-top: 20px;">{{ $qa[0] }}</h3>
                <p>{{ $qa[1] }}</p>
            @endforeach
        </article>
    </div></div></div>
</section>
@endsection
