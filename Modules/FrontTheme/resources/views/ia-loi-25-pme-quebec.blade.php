<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', "IA et Loi 25 pour les PME québécoises : rester conforme - " . config('app.name'))
@section('meta_description', "Utiliser une IA sur des données de clients ou d'employés est un traitement de renseignements personnels au sens de la Loi 25. Obligations concrètes pour une PME et règle d'or : anonymiser avant de soumettre à une IA.")
@push('head')
<meta property="og:title" content="IA et Loi 25 pour les PME québécoises : rester conforme">
@php
$faq = [
    ["Une PME doit-elle respecter la Loi 25 quand elle utilise une IA ?", "Oui. Dès qu'un outil d'IA traite des données de clients ou d'employés, il s'agit d'un traitement de renseignements personnels au sens de la Loi 25. La PME demeure responsable de ces données et doit respecter les obligations de la loi, peu importe l'outil utilisé."],
    ["Qu'est-ce que le « shadow IA » et pourquoi est-ce risqué ?", "Le « shadow IA » désigne l'usage d'outils d'IA grand public par des employés, sans encadrement. Le risque : coller des renseignements personnels dans une IA publique, où ces données peuvent être conservées ou réutilisées. La règle d'or est de ne jamais soumettre d'information identifiable à une IA publique et d'anonymiser d'abord."],
    ["Comment éviter de transmettre des renseignements personnels à une IA ?", "Anonymisez le texte avant de le soumettre : retirez ou remplacez les noms, adresses, numéros de dossier et autres éléments identifiants. Un anonymiseur local, comme celui de laveille.ai, masque ces éléments directement dans votre navigateur, sans envoyer vos données à un serveur."],
];
$articleLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => "IA et Loi 25 pour les PME québécoises : rester conforme",
    'description' => "Utiliser une IA sur des données de clients ou d'employés est un traitement de renseignements personnels au sens de la Loi 25. Obligations concrètes pour une PME et règle d'or : anonymiser avant de soumettre à une IA.",
    'datePublished' => '2026-06-20',
    'dateModified' => '2026-06-20',
    'inLanguage' => 'fr-CA',
    'author' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
    'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
    'mainEntityOfPage' => request()->url(),
];
$faqLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($q) => [
        '@type' => 'Question',
        'name' => $q[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]],
    ], $faq),
];
@endphp
<script type="application/ld+json">{!! json_encode($articleLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('breadcrumb')
@include('fronttheme::partials.breadcrumb', [
    'breadcrumbTitle' => "IA et Loi 25 pour les PME",
    'breadcrumbItems' => ["L'IA pour les PME québécoises", "IA et Loi 25"],
])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.7;">

                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">IA et Loi 25 pour les PME québécoises : rester conforme</h1>
                    <x-fronttheme::page-freshness updated="2026-06-20" />

                    <p>Pour une PME québécoise, utiliser un outil d'IA sur des données de clients ou d'employés n'a rien d'anodin : cela constitue un traitement de renseignements personnels au sens de la Loi 25. Il faut donc le traiter comme tel, avec les mêmes précautions que pour n'importe quel autre traitement de données sensibles. L'IA est un outil formidable, mais la responsabilité juridique reste entière, peu importe le fournisseur derrière l'outil.</p>

                    <p>Bonne nouvelle : se conformer n'exige pas une équipe juridique complète. Quelques réflexes simples, appliqués avec constance, permettent de tirer parti de l'IA sans exposer les renseignements de vos clients ou de votre personnel.</p>

                    <x-core::answer-box
                        summary="Dès qu'une IA traite des données de clients ou d'employés, la Loi 25 s'applique. Anonymisez avant de soumettre quoi que ce soit à une IA publique, encadrez l'usage par vos équipes, et conservez la trace de vos traitements."
                        :points="[
                            'Ne jamais soumettre de renseignements identifiables à une IA publique : anonymisez d\'abord.',
                            'Désigner un responsable de la protection des renseignements personnels et tenir un registre des traitements.',
                            'Conclure des ententes écrites avec les fournisseurs d\'IA (emplacement des données, sécurité, non-réutilisation pour l\'entraînement).',
                            'Réaliser une évaluation des facteurs relatifs à la vie privée (ÉFVP) pour les projets à risque.',
                            'Encadrer le « shadow IA » : sensibiliser les équipes pour éviter les fuites involontaires.',
                        ]"
                    />

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Utiliser une IA, c'est traiter des renseignements personnels</h2>
                    <p>Dès qu'un outil d'IA reçoit le nom d'un client, son adresse, un numéro de dossier, un courriel d'employé ou tout autre élément permettant d'identifier une personne, il y a traitement de renseignements personnels. La Loi 25 (Loi modernisant des dispositions législatives en matière de protection des renseignements personnels) encadre ce traitement, et la Commission d'accès à l'information du Québec veille à son application. Une PME ne peut donc pas considérer l'IA comme une « zone grise » : les obligations habituelles s'appliquent pleinement.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Les obligations concrètes pour une PME</h2>
                    <p>Selon la Loi 25 et les orientations de la Commission d'accès à l'information du Québec, une PME doit notamment :</p>
                    <ul style="padding-left: 20px; margin: 16px 0;">
                        <li><strong>Désigner un responsable de la protection des renseignements personnels</strong> au sein de l'entreprise.</li>
                        <li><strong>Tenir un registre des traitements</strong> : quelles données, pourquoi, pour combien de temps, et avec quels fournisseurs.</li>
                        <li><strong>Maintenir une politique de confidentialité à jour</strong>, claire et accessible.</li>
                        <li><strong>Conclure des ententes écrites avec les fournisseurs d'IA</strong> précisant l'emplacement des données, les mesures de sécurité et l'engagement de non-réutilisation des données pour entraîner les modèles.</li>
                        <li><strong>Réaliser une évaluation des facteurs relatifs à la vie privée (ÉFVP)</strong> pour les projets à risque.</li>
                        <li><strong>Obtenir le consentement</strong> quand il est requis.</li>
                        <li><strong>Signaler les incidents de confidentialité</strong> selon les règles applicables.</li>
                    </ul>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Le risque du « shadow IA »</h2>
                    <p>Le plus grand risque pour une PME n'est pas toujours un projet d'IA mal ficelé : c'est souvent le « shadow IA », c'est-à-dire des employés qui collent des renseignements personnels dans des outils d'IA grand public pour aller plus vite. Ces données peuvent alors être conservées, analysées ou réutilisées hors de votre contrôle. La règle d'or est simple : ne jamais soumettre de renseignements identifiables à une IA publique. Anonymisez d'abord.</p>

                    <p style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
                        <x-core::button :href="url('/outils/anonymiseur')" variant="primary">Anonymiser un texte (100 % local)</x-core::button>
                        <x-core::button :href="route('pillar.ia-pme')" variant="secondary" size="sm">Retour au dossier PME</x-core::button>
                        <x-core::button :href="route('guide.pme-cas-usage')" variant="secondary" size="sm">Cas d'usage de l'IA pour les PME</x-core::button>
                    </p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Pour aller plus loin</h2>
                    <p>Cette page complète notre dossier sur la conformité. Pour les organismes publics et parapublics, consultez la version dédiée : <a href="{{ route('guide.sp-loi25') }}" style="color: var(--sys-text-link, #064E5A);">IA et Loi 25 dans le secteur public</a>. Pour les usages pratiques par fonction, voyez nos <a href="{{ route('guide.pme-cas-usage') }}" style="color: var(--sys-text-link, #064E5A);">cas d'usage concrets de l'IA pour les PME</a>, et revenez au pilier <a href="{{ route('pillar.ia-pme') }}" style="color: var(--sys-text-link, #064E5A);">L'IA pour les PME québécoises</a>.</p>
                    <p>Source de référence : <a href="https://www.cai.gouv.qc.ca/" target="_blank" rel="noopener" style="color: var(--sys-text-link, #064E5A);">Commission d'accès à l'information du Québec</a>.</p>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Rester à jour</h2>
                    <div style="max-width:520px;margin:16px 0;">
                        <x-fronttheme::newsletter-form source="sous-article-pme-loi25" layout="inline" :show-note="true" />
                    </div>

                    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-top: 32px; margin-bottom: 16px;">Questions fréquentes</h2>
                    @foreach($faq as $qa)
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
