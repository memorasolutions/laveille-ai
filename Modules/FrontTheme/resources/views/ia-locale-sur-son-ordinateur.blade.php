<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "L'IA locale : exécuter un modèle de langage sur son ordinateur - " . config('app.name'))
@section('meta_description', "Faire tourner un LLM en local : outils (Ollama, LM Studio), modèles ouverts, matériel requis (RAM/VRAM par taille), avantages (confidentialité, coût, hors-ligne) et limites.")
@push('head')
<meta property="og:title" content="L'IA locale : exécuter un modèle de langage sur son ordinateur">
@php
$faq = [
    ["Quel matériel faut-il pour l'IA locale ?", "Pour un modèle de 7 à 8 milliards de paramètres quantifié en 4 bits, un ordinateur avec 16 Go de RAM et un GPU de 8 Go de mémoire vidéo suffit généralement. Les modèles de 70 milliards exigent plutôt 40 Go de VRAM ou plusieurs cartes – hors de portée de la plupart des portables."],
    ["L'IA locale est-elle aussi performante que ChatGPT ?", "Pas toujours. Les modèles exécutables localement sont souvent moins performants que les grands modèles cloud comme GPT-4 ou Claude, et la vitesse dépend de votre matériel. En revanche, vos données restent sur votre machine."],
    ["Quels logiciels pour débuter ?", "Ollama (en ligne de commande, avec une API) et LM Studio (interface graphique type « ChatGPT hors-ligne ») sont parmi les plus accessibles. GPT4All et Jan visent aussi les débutants."]
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
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => "L'IA locale sur son ordinateur", 'breadcrumbItems' => ["L'IA pour les développeurs", "L'IA locale sur son ordinateur"]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">L'IA locale : faire tourner un modèle de langage sur son ordinateur</h1>
                    <x-fronttheme::page-freshness updated="2026-06-19" />

                    <p><strong>On peut aujourd'hui exécuter des modèles de langage (LLM) directement sur son propre ordinateur, sans passer par le cloud.</strong> Un bon portable suffit pour des modèles de 7 à 14 milliards de paramètres ; les très gros modèles exigent une machine de bureau puissante. L'IA locale séduit par la confidentialité, le coût et le fonctionnement hors-ligne – au prix de quelques compromis.</p>
                    <p>Pour les développeurs et les PME sensibles à la protection des données, exécuter un modèle localement permet de garder l'information sur place. Le choix dépend de vos besoins, de votre matériel et du niveau de performance attendu.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Pourquoi exécuter l'IA en local ?</h2>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li>Confidentialité : vos données ne quittent pas votre machine.</li>
                        <li>Coût : une fois le matériel et les logiciels en place, l'inférence locale n'entraîne pas de frais par requête.</li>
                        <li>Hors-ligne : aucun accès Internet requis pour générer une réponse.</li>
                        <li>Contrôle : vous choisissez le modèle, sa version et ses réglages.</li>
                    </ul>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Les outils pour commencer</h2>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li><strong>Ollama</strong> : outil libre (Mac, Windows, Linux) qui télécharge et sert des modèles via une ligne de commande et une API compatible « style OpenAI ». Souvent recommandé comme choix par défaut côté développeurs.</li>
                        <li><strong>LM Studio</strong> : application de bureau avec interface graphique, façon « ChatGPT hors-ligne » ; expose aussi une API locale.</li>
                        <li><strong>llama.cpp</strong> : moteur C++ optimisé (format GGUF), très flexible et bas niveau, idéal pour intégrer l'IA dans ses propres services.</li>
                        <li><strong>GPT4All</strong> et <strong>Jan</strong> : applications tout-en-un orientées débutants et confidentialité.</li>
                    </ul>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Quels modèles peut-on exécuter ?</h2>
                    <p>Plusieurs familles de modèles ouverts (« open weights ») s'exécutent localement : <strong>Llama</strong> (Meta), <strong>Mistral / Mixtral</strong>, <strong>Qwen</strong>, <strong>Gemma</strong> (Google), <strong>DeepSeek</strong> et <strong>Phi</strong>, dans des tailles allant de quelques centaines de millions à plusieurs dizaines de milliards de paramètres. Les modèles « mixture-of-experts » (Mixtral, DeepSeek) n'activent qu'une partie de leurs paramètres, ce qui réduit les besoins en mémoire.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Quel matériel ? (repères)</h2>
                    <p>La <strong>quantification</strong> réduit la mémoire nécessaire : un modèle en 16 bits occupe environ 2 Go par milliard de paramètres ; en 4 bits, environ 0,5 Go. Un modèle de 70 milliards passe ainsi d'environ 140 Go à environ 35 Go. Repères réalistes pour une expérience fluide en 4 bits (format GGUF) :</p>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li>Modèle ~7-8 milliards : environ 8 Go de VRAM + 16 Go de RAM (très jouable sur un portable récent).</li>
                        <li>Modèle ~13-14 milliards : environ 12 à 16 Go de VRAM + 32 Go de RAM (bon compromis local aujourd'hui).</li>
                        <li>Modèle ~70 milliards : environ 40 Go de VRAM ou plus (ou plusieurs GPU), 128 Go de RAM et plus si une partie reste sur le processeur – hors de portée de la plupart des portables.</li>
                    </ul>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Les limites à connaître</h2>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li>Performance : les modèles locaux sont souvent moins performants que les grands modèles cloud (GPT-4, Claude).</li>
                        <li>Vitesse : la rapidité de génération dépend directement de votre matériel.</li>
                        <li>Maintenance : les mises à jour de modèles se font manuellement.</li>
                        <li>Taille : les très grands modèles restent difficiles, voire impossibles, à exécuter sur un ordinateur grand public.</li>
                    </ul>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Par où commencer ?</h2>
                    <p>Commencez par un modèle de 7 à 8 milliards de paramètres avec un outil simple comme Ollama ou LM Studio, testez-le sur vos cas d'usage réels, puis ajustez la taille du modèle selon votre matériel et vos besoins.</p>
                    <ul>
                        <li><a href="{{ route('blog.show', 'jai-cree-mon-ia-en-local-sur-mon-mac-partie-3') }}" style="color: var(--sys-text-link, #064E5A);">J'ai créé mon IA en local sur mon Mac (partie 3)</a></li>
                    </ul>
                    <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                        <x-core::button :href="route('pillar.ia-dev')" variant="primary">Retour au dossier : l'IA pour les développeurs</x-core::button>
                        <x-core::button :href="route('dictionary.index')" variant="secondary" size="sm">Glossaire Techno</x-core::button>
                        <x-core::button :href="route('directory.index')" variant="secondary" size="sm">Explorer les outils</x-core::button>
                    </p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Rester à jour</h2>
                    <div style="max-width:520px;margin:16px 0;">
                        <x-fronttheme::newsletter-form source="sous-article-ia-locale" layout="inline" :show-note="true" />
                    </div>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Questions fréquentes</h2>
                    @foreach($faq as $qa)
                        <h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.1rem; margin-top: 20px;">{{ $qa[0] }}</h3>
                        <p>{{ $qa[1] }}</p>
                    @endforeach

                    @include('fronttheme::partials.pillars-related', ['current' => 'pillar.ia-dev'])
                </article>
            </div>
        </div>
    </div>
</section>
@endsection
