<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "Rédiger avec l'IA dans le secteur public : bonnes pratiques - " . config('app.name'))
@section('meta_description', "Comment les organismes publics québécois peuvent utiliser l'IA pour rédiger notes, courriels ou comptes rendus de façon responsable, sécurisée et conforme à la Loi 25.")
@push('head')
<meta property="og:title" content="Rédiger avec l'IA dans le secteur public : bonnes pratiques">
@php
$faq = [
    ["Un fonctionnaire peut-il rédiger un courriel avec ChatGPT ?", "Oui, à condition de ne pas y insérer de renseignements personnels ou confidentiels, de valider le contenu humainement et de respecter les lignes directrices de son organisation. L'usage doit suivre les principes de responsabilité, de transparence et de protection des données."],
    ["Faut-il indiquer qu'un texte a été écrit avec l'IA ?", "La transparence est une exigence éthique et administrative. Si un document a été assisté par l'IA, il est recommandé de le mentionner, surtout s'il est diffusé publiquement ou utilisé dans un processus décisionnel."],
    ["Comment éviter de divulguer des renseignements personnels ?", "Anonymisez systématiquement les données avant de les soumettre à un outil d'IA. Retirez tout élément identifiant (noms, numéros, adresses, dossiers). Utilisez un anonymiseur local, comme celui de laveille.ai, qui masque ces éléments sans envoyer vos données à un serveur."],
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
    'breadcrumbTitle' => "Rédiger avec l'IA dans le secteur public",
    'breadcrumbItems' => ["L'IA dans le secteur public", "Rédiger avec l'IA"],
])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">Rédiger avec l'IA dans le secteur public : bonnes pratiques</h1>
                    <x-fronttheme::page-freshness updated="2026-06-19" />

                    <p>L'intelligence artificielle accélère la rédaction administrative : notes, courriels, comptes rendus ou communications internes. Toutefois, son usage dans le secteur public québécois est encadré. Le ministère de la Cybersécurité et du Numérique a publié un énoncé de principes pour une utilisation responsable de l'IA, et la Loi 25 impose des obligations rigoureuses en matière de protection des renseignements personnels.</p>

                    <p>Utiliser l'IA comme outil d'assistance — et non comme substitut à la réflexion humaine — permet de gagner en efficacité tout en respectant les normes éthiques, juridiques et administratives applicables aux organismes publics.</p>

                    <x-core::answer-box
                        summary="Pour rédiger en toute sécurité avec l'IA dans le secteur public, retenez trois règles : anonymisez les données avant utilisation, assurez une validation humaine rigoureuse, et soyez transparent sur le recours à l'IA."
                        :points="[
                            'Ne jamais saisir de renseignements personnels ou confidentiels.',
                            'Relire, corriger et approuver tout texte généré avant diffusion.',
                            'Indiquer clairement si un document a bénéficié d\'une assistance par l\'IA.',
                            'Respecter les politiques internes de votre organisation et l\'encadrement du gouvernement du Québec.',
                        ]"
                    />

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Trois règles d'or</h2>
                    <p><strong>1. Anonymiser avant toute utilisation.</strong> Aucune donnée personnelle (nom, numéro de dossier, adresse, etc.) ne doit être transmise à un outil d'IA externe. Nettoyez d'abord vos textes avec <a href="{{ url('/outils/anonymiseur') }}" style="color: var(--sys-text-link, #064E5A);">l'anonymiseur</a>.</p>
                    <p><strong>2. Validation humaine obligatoire.</strong> L'IA ne prend pas de décisions. Tout contenu généré doit être relu, corrigé, contextualisé et validé par une personne compétente.</p>
                    <p><strong>3. Transparence.</strong> Si un document est destiné à être partagé, mentionnez l'assistance par l'IA, notamment pour éviter toute confusion sur l'origine d'une décision ou d'un message.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Une méthode simple en 4 étapes</h2>
                    <p><strong>1. Anonymisez</strong> : masquez toute information identifiable avant d'interagir avec l'IA.</p>
                    <p><strong>2. Donnez un rôle et un contexte clairs</strong> : par exemple « Tu es un conseiller en communication d'un ministère québécois. Rédige un courriel professionnel à un citoyen concernant un délai de traitement. »</p>
                    <p><strong>3. Précisez le format attendu</strong> : ton (neutre, empathique), longueur (court, environ 150 mots) ou structure (objet, salutation, corps, formule de politesse).</p>
                    <p><strong>4. Relisez et validez</strong> : vérifiez l'exactitude, la conformité au mandat, l'absence de biais et l'adéquation au cadre institutionnel.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Ce qu'il faut éviter</h2>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li>Copier-coller un texte généré sans relecture ni adaptation au contexte.</li>
                        <li>Soumettre des documents contenant des renseignements personnels ou des dossiers sensibles.</li>
                        <li>Présenter un texte produit par l'IA comme une décision officielle sans validation.</li>
                    </ul>

                    <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                        <x-core::button :href="route('pillar.ia-secteur-public')" variant="primary">Retour au dossier : l'IA dans le secteur public</x-core::button>
                        <x-core::button :href="url('/outils/anonymiseur')" variant="secondary" size="sm">Anonymiseur</x-core::button>
                        <x-core::button :href="route('dictionary.index')" variant="secondary" size="sm">Glossaire Techno</x-core::button>
                    </p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Rester à jour</h2>
                    <div style="max-width:520px;margin:16px 0;">
                        <x-fronttheme::newsletter-form source="sous-article-sp-rediger" layout="inline" :show-note="true" />
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
