@extends(fronttheme_layout())

@php
    $ss = $article->structured_summary;
    // Actus 2.0 : $isDigest calculée une fois ici (à côté de $ss), consommée par tous les
    // blocs conditionnels ci-dessous (design doc section 7).
    $isDigest = (bool) ($article->is_comparative_digest ?? false);
    $fusionDigestArticle = method_exists($article, 'fusionDigest') ? $article->fusionDigest() : null;
    // Richesse v1.188.0 (design doc "Actus - composition manuelle assistée" 2026-08-15, section
    // "Richesse v1.188.0 - structure fixe composée") : une fiche COMPOSÉE (marqueur composed:true,
    // écrit par NewsApplyCommand --payload/composed_summary, via NewsArticle::hasComposedSummary()
    // - point unique, jamais recalculé ici) rend ses sections dans un ORDRE FIXE et des libellés
    // CONSTANTS, distincts de l'ancien résumé MACHINE ci-dessous (structure historique, inchangée
    // pour les fiches qui n'ont jamais transité par la composition).
    $isComposed = $article->hasComposedSummary();
@endphp

{{-- Élagage SEO : vieille actualité peu vue → noindex,follow (le layout master lit cette section). --}}
@if(($article->seo_status ?? 'index') === 'noindex')
    @section('page_noindex', '1')
@endif

@section('title', ($article->seo_title ?? $article->title) . ' - ' . __('Actualités') . ' - ' . config('app.name'))
{{-- Cascade meta description (design doc "Actus - zéro copie du texte source", 2026-08-13,
     section 4.5) : meta_description explicite, sinon NewsArticle::displayExcerpt() (résumé
     court, sinon accroche du résumé structuré, sinon repli configuré) - jamais $article->description. --}}
@section('meta_description', $article->meta_description ?? $article->displayExcerpt(155))
@section('share_text')
@php
    // Refonte share_text News - pattern viral 2026 aligné Blog (sonar-pro hybride #3+#1+#5)
    $ss = $article->structured_summary ?? [];
    $clean = fn($str) => str_replace('\'', "\u{2019}", trim(strip_tags($str ?? '')));
    $title = $clean($article->seo_title ?? $article->title);
    $hook = $clean($ss['hook'] ?? $article->meta_description);
    $whyImportant = $clean($ss['why_important'] ?? null);
    $keyNumber = $clean($ss['key_number'] ?? null);
    $actionConcrete = $clean($ss['action_concrete'] ?? null);
    $categoryTag = $article->category_tag ? '#' . preg_replace('/[^a-z0-9]/i', '', \Illuminate\Support\Str::ascii($article->category_tag)) : null;

    $lines = array_filter([
        "📰 {$title}",
        '',
        $hook ?: null,
        '',
        $whyImportant ? "🧠 Pourquoi ça compte : {$whyImportant}" : null,
        $keyNumber ? "🎯 Chiffre-clé : {$keyNumber}" : ($actionConcrete ? "🎯 À retenir : {$actionConcrete}" : null),
        '',
        '💬 Ton avis? On en parle en commentaire.',
        '🔗 ' . request()->url(),
        $categoryTag,
        '#IAQuebec #VeilleIA',
        'Via @laveilleAI',
    ], fn($line) => $line !== null);

    echo implode("\n", $lines);
@endphp
@endsection
@php
    // Texte de partage PAR RÉSEAU (spec 2026-08-21 - remplace le bloc @php ci-dessus, écrit en
    // dur, par une délégation à NewsArticle::publicShareTexts(), qui délègue à son tour au trait
    // partagé Modules\Core\Concerns\HasAdminShareContents::publicShareTexts() - même moteur que
    // Blog/Directory/Tools, règles issues d'une consultation à 5 modèles en 3 rounds (2026-08-21) :
    // texte TERMINÉ (jamais de « … » de troncature), idée distinctive dans la 1re phrase toujours
    // coupée à une frontière réelle, aucun libellé interne recopié, aucun CTA creux, mots-clics
    // 0 Facebook/Messenger - 1 à 3 LinkedIn - 0 ou 1 X, lien inclus dans les 4 textes.
    $publicShareTexts = $article->publicShareTexts();
    $shareTextX = $publicShareTexts['x'];
    $shareTextLinkedIn = $publicShareTexts['linkedin'];
    $shareTextFacebook = $publicShareTexts['facebook'];
    $shareTextMessenger = $publicShareTexts['messenger'];
@endphp
@section('share_text_x', $shareTextX)
@section('share_text_linkedin', $shareTextLinkedIn)
@section('share_text_facebook', $shareTextFacebook)
@section('share_text_messenger', $shareTextMessenger)
@section('og_type', 'article')
{{-- og:image jamais en WebP/AVIF (Facebook/LinkedIn n'affichent pas ces formats en aperçu de
     partage - aperçu vide et silencieux sans cette protection), y compris pour une image_url
     EXTERNE : chaîne de repli centralisée dans Modules\Core\Services\SocialImageResolver,
     appelée aussi par Glossaire/Blogue/Outils (DRY, audit 2026-08-22 - la logique locale
     d'avant laissait passer les images externes en WebP telles quelles). --}}
@if(!empty($article->image_url))
    @php
        $_ogImagePath = \Modules\Core\Services\SocialImageResolver::shareable($article->image_url);
        $_ogImageIsExternal = $_ogImagePath && str_starts_with($_ogImagePath, 'http');
    @endphp
    @if($_ogImagePath)
        @section('og_image', $_ogImageIsExternal ? $_ogImagePath : url($_ogImagePath).'?v='.($article->updated_at?->timestamp ?? '0'))
    @endif
@endif

@section('breadcrumb')
    {{-- Haut de page allégé (point 3, panel 2026-08-17) : le bandeau sombre garde le fil
         d'Ariane (dont le titre complet, comme dernier maillon), mais ne répète plus le titre
         en grand h2 - le h1 du corps (nw-show-title, plus bas) devient l'unique titre affiché.
         breadcrumb.blade.php reste hors périmètre : on vide breadcrumbTitle depuis l'appelant. --}}
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => '',
        'breadcrumbItems' => [__('Actualités'), $article->seo_title ?? $article->title]
    ])
@endsection

@can('view_admin_panel')
@include('core::components.admin-bar', [
    'label' => __('Article admin'),
    'model' => $article,
    'editUrl' => Route::has('admin.news.articles.edit') ? route('admin.news.articles.edit', $article) : null,
    'actions' => array_filter([
        Route::has('admin.news.articles.edit') ? ['label' => __('Éditer'), 'icon' => 'pencil', 'url' => route('admin.news.articles.edit', $article)] : null,
        Route::has('admin.news.articles.edit') ? ['label' => __('Outils liés'), 'icon' => 'link', 'url' => '#nw-tools-editor'] : null,
        Route::has('admin.news.articles.rescore') ? ['label' => __('Rescorer'), 'icon' => 'bar-chart-2', 'url' => route('admin.news.articles.rescore', $article), 'method' => 'POST', 'confirm' => __('Relancer le scoring IA ?')] : null,
        ['divider' => true],
        Route::has('admin.news.articles.destroy') ? ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.news.articles.destroy', $article), 'method' => 'DELETE', 'confirm' => __('Supprimer cet article ?'), 'danger' => true] : null,
    ]),
])
@endcan
@can('view_admin_panel')
<button type="button" class="core-capture-fab" onclick="document.getElementById('core-capture-dialog-news').showModal()" title="Capture assistée écran" aria-label="Capture assistée écran">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
</button>
<dialog id="core-capture-dialog-news" class="core-capture-dialog">
    <form method="dialog" class="core-capture-dialog__close-form">
        <button type="submit" class="core-capture-dialog__close" aria-label="Fermer">✕</button>
    </form>
    <h5 class="core-capture-dialog__title">📸 {{ __('Capture assistée (Screen Capture API)') }}</h5>
    <x-core::screenshot-capture
        :uploadUrl="route('admin.news.articles.upload-image', $article)"
        :enabled="\Modules\Settings\Facades\Settings::get('news.assisted_screenshot_enabled', true)"
        label=""
        helpText="Ouvre l’article source dans un autre onglet, accepte les cookies, cadre sur l’image clé. Reviens ici et clique Capturer. Upload auto 1200×630 pour remplacer l’image de l’article."
    />
</dialog>
<style>
    .core-capture-fab { position: fixed; bottom: 24px; right: 24px; z-index: 8990; width: 48px; height: 48px; border-radius: 50%; background: var(--c-primary, #064E5A); color: #fff; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15), 0 2px 4px rgba(0,0,0,0.08); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .core-capture-fab:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.18), 0 3px 6px rgba(0,0,0,0.10); }
    .core-capture-dialog { max-width: 520px; width: calc(100% - 32px); border: none; border-radius: 14px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); }
    .core-capture-dialog::backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
    .core-capture-dialog__title { margin: 0 0 16px 0; font-size: 18px; font-weight: 700; color: var(--c-dark, #1A1D23); }
    .core-capture-dialog__close-form { position: absolute; top: 12px; right: 12px; margin: 0; }
    .core-capture-dialog__close { width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; border: none; color: #6b7280; font-size: 16px; cursor: pointer; line-height: 1; }
    .core-capture-dialog__close:hover { background: #e5e7eb; color: #111827; }
    @media (max-width: 767px) { .core-capture-fab { bottom: 16px; right: 16px; width: 44px; height: 44px; } }
    @media print { .core-capture-fab, .core-capture-dialog { display: none !important; } }
</style>
@endcan

{{-- Meta AEO/LLM-first 2026 + Schema.org NewsArticle + FAQPage --}}
@push('head')
{{-- Résumé pour agents (design doc section 4.5) : meta_description, sinon
     NewsArticle::displayExcerpt() (résumé court, sinon rendu du résumé structuré, sinon repli
     configuré catégorie+date) - jamais $article->description.
     v1.244.15 : jamais e() ICI - {{ }} Blade échappe déjà tout seul (double échappement corrigé,
     ex. l&#039; devenait l&amp;#039;). Ne jamais réintroduire un e()/htmlspecialchars() manuel
     à l'intérieur d'un bloc {{ }} - voir MachineMarkupEscapingTest pour la preuve d'injection. --}}
<meta name="llm:summary" content="{{ $article->seo_title ?? $article->title }} - {{ $article->meta_description ?? $article->displayExcerpt(200) }} ({{ $article->displaySourceName() }})">
<meta name="llm:keywords" content="actualité IA, {{ $article->displaySourceName() }}, intelligence artificielle, francophone, Québec">
<meta name="llm:url" content="{{ route('news.show', $article) }}">
@php
    // Graphes JSON-LD de la fiche, assemblés en UN seul endroit (2026-08-21) : l'ancienne
    // structure dupliquait l'appel à newsArticle() dans les deux branches d'un @if, et ajouter
    // un troisième graphe y aurait doublé la duplication. Chaque graphe est simplement ajouté
    // s'il a lieu d'être ; render() est variadique et n'est appelé qu'une fois.
    $nwSchemas = [\Modules\SEO\Services\JsonLdService::newsArticle($article)];

    if ($ss && isset($ss['faq_question'])) {
        $nwSchemas[] = ['@type' => 'FAQPage', 'mainEntity' => [['@type' => 'Question', 'name' => $ss['faq_question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $ss['faq_answer'] ?? '']]]];
    }

    // ClaimReview : présent UNIQUEMENT sur une fiche qui vérifie une affirmation (module
    // « vérification »), et un seul par page - c'est la règle du vocabulaire, pas une exigence
    // de Google, qui a retiré ce balisage de ses résultats enrichis en juin 2025 (voir le
    // docblock de JsonLdService::claimReview() pour la raison de le poser quand même).
    if ($nwClaimReview = \Modules\SEO\Services\JsonLdService::claimReview($article)) {
        $nwSchemas[] = $nwClaimReview;
    }
@endphp
{!! \Modules\SEO\Services\JsonLdService::render(...$nwSchemas) !!}
@endpush

@push('styles')
<style>
    .nw-show { max-width: 740px; margin: 0 auto; }
    .nw-hero { width: 100%; max-height: 420px; object-fit: cover; border-radius: 12px; margin-bottom: 1.5rem; }
    /* Bonification panel 2026-08-17 (soir) - crédit photo discret. Contraste AAA (~8:1 sur
       fond blanc) via le jeton --c-text-secondary, jamais le gris clair déjà utilisé pour les
       méta-données secondaires de cette page (celui-ci passe tout juste l'AA, pas l'AAA). */
    .nw-hero-has-credit { margin-bottom: 0.375rem; }
    .nw-image-credit { font-size: 0.8125rem; color: var(--c-text-secondary, #4a4f5c); text-align: right; margin: 0 0 1.5rem; }
    .nw-show-title { font-family: var(--f-heading); font-size: 2rem; line-height: 1.2; margin-bottom: 1rem; }
    {{-- .nw-shared-dot : styles fournis par le composant partagé x-news::admin-shared-dot (@once push('styles')). --}}
    .nw-lead { font-size: 1.0625rem; font-weight: 600; color: var(--c-dark); line-height: 1.6; margin-bottom: 1.5rem; }
    .nw-meta-bar {
        display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;
        padding-bottom: 0.75rem; margin-bottom: 0; border-bottom: none;
    }
    .nw-pill {
        display: inline-flex; align-items: center; gap: 0.25rem;
        padding: 0.2rem 0.625rem; border-radius: 4px;
        font-size: 0.8125rem; font-weight: 500; background: #f3f4f6; color: #374151;
    }
    .nw-pill-cat { background: var(--c-primary); color: #fff; }
    .nw-pill-sep { color: #d1d5db; font-size: 0.75rem; }
    .nw-section-heading {
        font-family: var(--f-heading); font-size: 1.125rem; font-weight: 700;
        color: var(--c-dark); margin-bottom: 0.75rem; padding-bottom: 0.375rem;
        border-bottom: 2px solid var(--c-primary);
    }
    .nw-key-list { padding-left: 1.25rem; margin-bottom: 1.75rem; }
    .nw-key-list li { font-size: 0.9375rem; color: #374151; line-height: 1.65; margin-bottom: 0.5rem; }
    .nw-why {
        border-left: 3px solid var(--c-primary); background: #f9fafb;
        padding: 1rem 1.25rem; border-radius: 0 8px 8px 0; margin-bottom: 1.75rem;
    }
    .nw-why p { font-size: 0.9375rem; color: #4b5563; line-height: 1.65; margin: 0; }
    .nw-faq { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.75rem; }
    .nw-faq h3 { font-family: var(--f-heading); font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
    .nw-faq p { font-size: 0.9375rem; color: #4b5563; line-height: 1.65; margin: 0; }
    .nw-audience { font-size: 0.8125rem; color: #6b7280; margin-bottom: 1.75rem; }
    .nw-desc { line-height: 1.7; color: var(--c-dark); margin-bottom: 2rem; }
    .nw-cta { display: inline-block; background: var(--c-primary); color: #fff; padding: 0.75rem 1.75rem; border-radius: 8px; text-decoration: none; font-weight: 600; }
    .nw-cta:hover { opacity: 0.9; color: #fff; text-decoration: none; }
    .nw-back { color: var(--c-primary); font-weight: 500; text-decoration: none; }
    .nw-back:hover { text-decoration: underline; }
    .nw-nav { border-top: 1px solid #e5e7eb; padding: 1.25rem 0; margin: 2rem 0 1rem; display: flex; justify-content: space-between; gap: 1rem; }
    .nw-nav a { color: var(--c-primary); text-decoration: none; font-size: 0.875rem; font-weight: 500; max-width: 48%; }
    .nw-nav a:hover { text-decoration: underline; }
    .nw-nav-next { text-align: right; margin-left: auto; }
    .nw-related { border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-top: 1rem; }
    .nw-related h3 { font-family: var(--f-heading); font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; }
    .nw-related-grid { display: flex; flex-wrap: wrap; gap: 1rem; }
    .nw-related-card { flex: 1; min-width: 200px; max-width: 33%; }
    .nw-related-card a { text-decoration: none; color: inherit; }
    .nw-related-card a:hover .nw-related-title { color: var(--c-primary); }
    .nw-related-img { width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 6px; margin-bottom: 0.5rem; }
    .nw-related-title { font-family: var(--f-heading); font-size: 0.9rem; font-weight: 600; line-height: 1.35; margin-bottom: 0.375rem; color: var(--c-dark); }
    .nw-related-meta { font-size: 0.75rem; color: #6b7280; }
    .nw-user-actions { display: flex; align-items: center; gap: 1rem; padding: 1rem 0; border-top: 1px solid #e5e7eb; margin-top: 1.5rem; }
    @media (max-width: 767px) { .nw-related-card { max-width: 100%; } }
    /* Mise en page des fiches - v1.187.0 (panel 2026-08-17, point 2) : l'ancien libellé de
       l'encadré devient « L'essentiel ». La classe reste `nw-tldr` (JsonLdService::newsArticle() cible ce
       sélecteur pour le schema Speakable, NewsSeoEnrichedTest.php l'attend) - seul le libellé
       visible change, via ::before ci-dessous. Ce même encadré sert désormais aux TROIS
       origines de contenu (tldr, hook, résumé de repli) - voir show.blade.php. */
    .nw-tldr {
        background: linear-gradient(135deg, #f0fdfa 0%, #e6fffa 100%);
        border-left: 4px solid var(--c-primary); border-radius: 8px;
        padding: 1rem 1.25rem; margin-bottom: 0.75rem;
        position: relative;
    }
    .nw-tldr::before {
        content: "L'ESSENTIEL"; position: absolute; top: -10px; left: 1rem;
        background: var(--c-primary); color: #fff; font-size: 0.7rem;
        font-weight: 700; letter-spacing: 0.05em; padding: 2px 8px;
        border-radius: 4px;
    }
    .nw-tldr p { margin: 0; font-size: 1rem; line-height: 1.6; color: #134e4a; font-weight: 500; }
    /* Ligne de transparence (point 2) - sous l'encadré « L'essentiel », jamais un aveu de
       faiblesse : reformulée en force. Contraste AAA via le jeton partagé avec le crédit photo. */
    .nw-essential-transparency {
        font-size: 0.8125rem; color: var(--c-text-secondary, #4a4f5c);
        font-style: italic; margin: 0 0 1.25rem;
    }
    /* Ligne de provenance compacte (point 4), sous les métadonnées. */
    .nw-provenance { font-size: 0.875rem; color: var(--c-text-secondary, #4a4f5c); margin: 0 0 1.25rem; }
    .nw-provenance a { color: var(--c-primary); font-weight: 600; text-decoration: none; }
    .nw-provenance a:hover { text-decoration: underline; }
    /* Fin de page dégraissée (point 5) - un seul lien générique, même gabarit que le maillage
       evergreen partagé (fronttheme::partials.evergreen-related, hors périmètre ici). */
    .nw-plus-loin { margin-top: 44px; padding-top: 24px; border-top: 1px solid #e5e7eb; }
    .nw-plus-loin-link {
        display: inline-flex; align-items: center; padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 999px;
        color: var(--c-primary); text-decoration: none; font-size: 0.9rem; font-weight: 600; background: #f8fafb;
        /* Cible tactile WCAG 2.2 AAA 44px (2026-08-29) - défaut PRÉEXISTANT (pilule mesurée à
           38,25px sur mobile 390x844), révélé par l'ajout du second lien ci-dessous plutôt que
           causé par lui. Classe PARTAGÉE par les deux pilules -> corrigée ici, jamais sur un cas
           particulier. Même variable que .ct-btn-icon (public/css/charte.css). */
        min-height: var(--ct-btn-min-height, 44px);
    }
    .nw-plus-loin-link:hover { text-decoration: underline; }
    /* Haut de page allégé (point 3) - le bandeau sombre du thème (partial breadcrumb, hors
       périmètre ici) garde le fil d'Ariane mais ne répète plus le titre : h2 vidé côté PHP
       (breadcrumbTitle => ''), collapsé ici pour ne pas laisser un espace vide dans le bandeau. */
    .wpo-breadcumb-area .wpo-breadcumb-wrap h2:empty { display: none; }
    /* R10 - extrait verbatim de la source (rendu si quote présent) */
    .nw-quote {
        border-left: 3px solid #94a3b8; background: #f8fafc;
        padding: 0.875rem 1.25rem; margin: 1.25rem 0;
        font-style: italic; color: #475569;
    }
    .nw-quote cite { display: block; margin-top: 0.5rem; font-size: 0.8125rem; color: #64748b; font-style: normal; }
    /* Attribution citation (article 29.2 LDA) - lien vers l'article original, cf.
       x-news::quote-attribution. */
    .nw-quote-source-link { color: var(--c-primary); font-weight: 600; text-decoration: none; }
    .nw-quote-source-link:hover { text-decoration: underline; }
    .nw-stat { display: inline-block; background: var(--c-primary); color: #fff; padding: 0.125rem 0.5rem; border-radius: 4px; font-weight: 700; font-size: 0.875rem; }
    .nw-expert { font-size: 0.875rem; color: #475569; margin: 0.5rem 0 0; }
    .nw-expert strong { color: var(--c-dark); }
    /* Actus 2.0 - bandeau page membre (design doc section 7) */
    .nw-fusion-banner {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;
        background: #f0f9fa; border: 1.5px solid var(--c-primary); border-radius: 8px;
        padding: 0.75rem 1.125rem; margin-bottom: 1.25rem; font-size: 0.9375rem; color: #134e4a;
    }
    .nw-fusion-banner a { color: var(--c-primary); font-weight: 700; text-decoration: none; white-space: nowrap; }
    .nw-fusion-banner a:hover { text-decoration: underline; }
    /* Actus 2.0 - liste des sources d'une fiche comparative */
    .nw-sources-list { list-style: none; padding: 0; margin: 0 0 1.75rem; }
    .nw-sources-list li { padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb; font-size: 0.9375rem; color: #374151; }
    .nw-sources-list li:last-child { border-bottom: none; }
    .nw-sources-list a { color: var(--c-primary); font-weight: 600; text-decoration: none; }
    .nw-sources-list a:hover { text-decoration: underline; }
    .nw-sources-author { color: #6b7280; }
    .nw-sources-angle { color: #4b5563; }
    /* Implémentation /actu2 - volet serveur (design doc "Actus - composition manuelle assistée"
       2026-08-15, section "Implémentation /actu2 - volet serveur (2026-08-17)") - citation
       statique d'un post X quand l'ORIGINAL retrouvé par le skill est lui-même un post. Jamais le
       widget officiel de X (script tiers interdit : pistage, CSP, fragilité) - même style de
       bloc que .nw-quote, en distinct (nw-post-quote) car sa structure porte l'auteur/le handle/
       la date, pas seulement une source. */
    .nw-post-quote {
        border-left: 3px solid var(--c-primary); background: #f8fafc;
        padding: 0.875rem 1.25rem; margin: 1.25rem 0; border-radius: 0 8px 8px 0;
    }
    .nw-post-quote__text { margin: 0 0 0.5rem; color: var(--c-dark); line-height: 1.6; font-style: italic; }
    .nw-post-quote__footer { display: flex; flex-wrap: wrap; gap: 0.375rem 0.5rem; align-items: center; font-size: 0.8125rem; color: #4b5563; }
    .nw-post-quote__author { font-weight: 700; color: var(--c-dark); }
    .nw-post-quote__handle { color: #6b7280; }
    .nw-post-quote__date { color: #6b7280; }
    .nw-post-quote__link { color: var(--c-primary); font-weight: 600; text-decoration: none; }
    .nw-post-quote__link:hover { text-decoration: underline; }
    /* Badge sobre de niveau de preuve (même section du design doc) - contraste AAA (~8:1 sur
       fond blanc), jamais l'étiquette technique brute (primaire/mixte/relais), toujours le
       libellé traduit calculé côté PHP juste avant la section Sources. */
    .nw-niveau-preuve { margin: 0 0 0.75rem; }
    .nw-niveau-preuve__pill {
        display: inline-block; font-size: 0.8125rem; font-weight: 600;
        color: var(--c-text-secondary, #4a4f5c); background: #f3f4f6;
        padding: 0.2rem 0.625rem; border-radius: 4px;
    }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="nw-show">

                    {{-- Actus 2.0 - page d'un article MEMBRE (design doc section 7) : le bloc
                         noindex existant (ci-dessus, déjà conditionné sur seo_status) s'applique
                         sans modification. Bandeau VISIBLE en plus (pas seulement une meta
                         invisible) pour ne pas laisser un visiteur humain sans porte de sortie
                         sur une page volontairement appauvrie. --}}
                    @if($fusionDigestArticle)
                        <div class="nw-fusion-banner" role="note">
                            <span>{{ __('Cette actualité fait partie d\'une fiche comparative plus complète.') }}</span>
                            <a href="{{ route('news.show', $fusionDigestArticle) }}">{{ __('Voir la fiche comparative') }} &rarr;</a>
                        </div>
                    @endif

                    {{-- Point rouge "déjà publié" (superadmin only) - composant partagé, même gate
                         que le menu de partage admin plus bas (isSuperAdmin()). Voir
                         components/admin-shared-dot.blade.php pour le détail du contrat. --}}
                    <h1 class="nw-show-title" data-editable="title">
                        <x-news::admin-shared-dot :article="$article" />
                        {{ $article->seo_title ?? $article->title }}
                    </h1>

                    {{-- Temps de lecture calculé sur le résumé publié, jamais sur le texte source
                         (design doc section 4.5) : NewsArticle::structuredBodyText() est le
                         bloc réutilisable unique du rendu intégral du résumé structuré. --}}
                    @php
                        $readMinutes = reading_time_minutes($article->structuredBodyText());
                    @endphp
                    {{-- Badge de pertinence (point 1, panel 2026-08-17) : un seul badge clair,
                         dérivé du score brut (jamais affiché tel quel) - ≥8 élevée, 5-7 moyenne,
                         <5 masqué. L'ancien couple opaque « 8/10 »/« Élevé » (impact_level) a
                         disparu de l'affichage. --}}
                    @php
                        $relevanceLabel = null;
                        if (is_numeric($article->relevance_score ?? null)) {
                            if ($article->relevance_score >= 8) {
                                $relevanceLabel = __('Pertinence : élevée');
                            } elseif ($article->relevance_score >= 5) {
                                $relevanceLabel = __('Pertinence : moyenne');
                            }
                        }
                    @endphp
                    {{-- Haut de page allégé (point 3) : métadonnées réduites à média/date/temps
                         de lecture, catégorie conservée, scores remplacés par le badge ci-dessus
                         (auteur retiré, hors de la liste retenue par l'arbitrage). --}}
                    <div class="nw-meta-bar">
                        <span class="nw-pill">{{ $readMinutes }} min {{ __('de lecture') }}</span>
                        <span class="nw-pill-sep">&middot;</span>
                        <span class="nw-pill">{{ $article->displaySourceName() }}</span>
                        <span class="nw-pill-sep">&middot;</span>
                        <span class="nw-pill">{{ $article->pub_date ? format_date($article->pub_date) : '' }}</span>
                        @if($article->category_tag)
                            <span class="nw-pill-sep">&middot;</span>
                            <span class="nw-pill nw-pill-cat">{{ $article->category_tag }}</span>
                        @endif
                        @if($relevanceLabel)
                            <span class="nw-pill-sep">&middot;</span>
                            <span class="nw-pill" title="{{ __('Évaluation interne de pertinence pour le lectorat québécois.') }}">{{ $relevanceLabel }}</span>
                        @endif
                    </div>

                    {{-- Badge de vérification (module « vérification », 2026-08-21) - placé AVANT
                         la signature : le lecteur voit d'abord de quoi il s'agit, puis qui en
                         répond. Ne rend rien sur une fiche ordinaire (composant DRY, vocabulaire
                         dans NewsArticle::FACT_CHECK_VERDICTS). --}}
                    <x-news::fact-check-badge :article="$article" />

                    {{-- Signature éditoriale « Vérifié par la rédaction » (signal humain E-E-A-T,
                         design doc SPEC-SIGNAL-HUMAIN 2026-08-20) - composant DRY, ne rend rien
                         tant que la fiche n'a pas reçu de vraie relecture datée. --}}
                    <x-news::editorial-signature :article="$article" />

                    {{-- Sources (points 1 bas de page/4/Sources détaillée) - calculées ici (haut
                         de page) pour la ligne de provenance ci-dessous ET réutilisées telles
                         quelles plus bas pour la section « Sources » détaillée (DRY, aucun
                         recalcul - la version précédente les calculait deux fois). --}}
                    @php
                        $externalUrl = $article->resolved_url ?: $article->url;
                        $isGoogleNewsUnresolved = str_contains(parse_url($externalUrl, PHP_URL_HOST) ?? '', 'news.google.com');
                        $primarySources = is_array($article->primary_sources ?? null) ? $article->primary_sources : [];
                        // niveau_preuve est PUBLIC mais TOUJOURS traduit en français courant,
                        // jamais l'étiquette technique brute - source unique du vocabulaire :
                        // NewsArticle::NIVEAU_PREUVE_VALUES (design doc 2026-09-03, section 2.3,
                        // même précédent que NATURE_ORIGINAL_VALUES/natureOriginalLabel()).
                        $niveauPreuveLabel = $article->niveauPreuveLabel();
                    @endphp
                    {{-- Ligne de provenance compacte (point 4) - fiche à source unique
                         uniquement : une fiche comparative affiche déjà sa propre liste
                         « Sources » plus bas (design doc section 7). --}}
                    @unless($isDigest)
                    @if(!empty($primarySources[0]['url'] ?? null))
                        {{-- Design doc « extension de l'écran de composition des actualités » (2026-09-03),
                             volet D : la ligne compacte cite DEUX sources primaires quand deux existent.
                             La section « Sources » du bas reste le lieu exhaustif (jusqu'à 10) - au-delà de
                             deux, la ligne compacte ne s'allonge pas, c'est un non-objectif explicite.
                             La deuxième source est testée par la MÊME garde que la première (l'URL elle-même,
                             pas count()) : une entrée d'indice 1 à l'URL vide est possible en donnée
                             historique, et se comporte alors comme une absence. --}}
                        <p class="nw-provenance">{{ __("D'après") }} <a href="{{ $primarySources[0]['url'] }}" target="_blank" rel="noopener nofollow">{{ $primarySources[0]['label'] ?? __('la source primaire') }}</a>@if(!empty($primarySources[1]['url'] ?? null)) {{ __('et') }} <a href="{{ $primarySources[1]['url'] }}" target="_blank" rel="noopener nofollow">{{ $primarySources[1]['label'] ?? __('une autre source primaire') }}</a>@endif@if($nwRelay = $article->displayRelayName()), {{ __('relayé par') }} {{ $nwRelay }}@endif</p>
                    @endif
                    @endunless

                    {{-- Éditeur « Outils liés » (admin uniquement) : accordéon FERMÉ par défaut, au-dessus de l'image.
                         Le <details> est PARENT du composant Livewire → l'état ouvert/fermé survit au morph (cf. piège Livewire v4). --}}
                    @auth
                        @can('view_admin_panel')
                            <details id="nw-tools-editor" style="scroll-margin-top: 90px; margin: 0 0 1.25rem; border: 1.5px solid var(--c-primary, #064E5A); border-radius: 8px; background: #f0f9fa;">
                                <summary style="cursor: pointer; padding: 10px 16px; font-weight: 700; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; color: #064E5A;">🔗 {{ __('Outils liés (admin)') }}</summary>
                                <livewire:news.article-tools-editor :article="$article" />
                            </details>
                            <script>
                                (function () {
                                    function openNwTools() {
                                        if (location.hash === '#nw-tools-editor') {
                                            var d = document.getElementById('nw-tools-editor');
                                            if (d) { d.open = true; }
                                        }
                                    }
                                    window.addEventListener('hashchange', openNwTools);
                                    document.addEventListener('DOMContentLoaded', openNwTools);
                                })();
                            </script>
                        @endcan
                    @endauth

                    @if($article->image_url)
                        <img src="{{ $article->versionedImageUrl() }}" alt="{{ $article->seo_title ?? $article->title }}" class="nw-hero{{ !empty($article->image_credit) ? ' nw-hero-has-credit' : '' }}" loading="lazy">
                        {{-- Bonification panel 2026-08-17 (soir) : photo créditée plutôt qu'une
                             illustration - crédit discret sous l'image principale, jamais affiché
                             si absent (aucune fiche antérieure n'en porte). --}}
                        @if(!empty($article->image_credit))
                            <p class="nw-image-credit">{{ $article->image_credit }}</p>
                        @endif
                    @endif

                    {{-- Bande dessinée pédagogique (standard « visionneur de BD ») - apparaît si public/bd/{slug}/manifest.json existe. 100 % réutilisé du glossaire (ComicLibrary + comic-viewer), zéro logique dupliquée. --}}
                    @if(class_exists(\Modules\Dictionary\Support\ComicLibrary::class) && \Modules\Dictionary\Support\ComicLibrary::hasComic((string) $article->slug))
                        <x-dictionary::comic-viewer :comic="\Modules\Dictionary\Support\ComicLibrary::forSlug((string) $article->slug)" />
                    @endif

                    {{-- « L'essentiel » (point 2, panel 2026-08-17) : encadré unique (ex-« EN
                         BREF »/ex-« Résumé IA »), reçoit les DEUX branches d'affichage - priorité
                         au tldr (réponse directe AEO, R4 Speakable), sinon au hook (accroche),
                         sinon au résumé de repli $article->summary quand $ss est absent. La
                         classe reste `nw-tldr` (JsonLdService cible ce sélecteur pour le schema
                         Speakable, NewsSeoEnrichedTest.php l'attend) - seul le libellé visible
                         change (règle CSS ::before, plus haut). --}}
                    @php
                        $essentialText = null;
                        $essentialUsedHook = false;
                        if ($ss) {
                            if (!empty($ss['tldr'])) {
                                $essentialText = $ss['tldr'];
                            } elseif (!empty($ss['hook'])) {
                                $essentialText = $ss['hook'];
                                $essentialUsedHook = true;
                            }
                        } elseif ($article->summary) {
                            $essentialText = $article->summary;
                        }
                        // Ligne de transparence - reformulée en force, jamais un aveu de faiblesse.
                        $transparencyText = (($article->niveau_preuve ?? null) === 'relais')
                            ? __('Rédigé à partir du média cité; chaque fait est vérifié contre le texte source.')
                            : __('Rédigé à partir de la source originale; chaque fait est vérifié contre le texte source.');
                        // 2026-08-30 (audit DRY overnight v1.237.1-v1.238.3) : plafond de liens glossaire
                        // en UNE seule variable, relue par les 15 appels @glossarize() de cette vue au
                        // lieu du littéral ['max_occ' => 1] recopié 15 fois (v1.237.3). La connaissance
                        // encodée - « une fiche actualité ne lie jamais un terme plus d'une fois, toute
                        // la page » - est UNE seule règle qui doit évoluer d'un bloc : la dupliquer 15
                        // fois dans le MÊME fichier créait un risque réel (une occurrence oubliée lors
                        // d'un futur changement réintroduit le défaut mesuré sur la fiche 39486, en
                        // partie). Ne PAS confondre avec le glossaire/blog (Dictionary/show.blade.php,
                        // FrontTheme/blog/show.blade.php) : ceux-là n'ont qu'UN SEUL appel chacun, donc
                        // rien à factoriser là-bas - même valeur, mais pas la même connaissance
                        // dupliquée (fichiers distincts, raisons d'évoluer distinctes), donc PAS fusionnés
                        // avec celle-ci (règle DRY du projet : la connaissance, jamais la ressemblance).
                        $glossOpts = ['max_occ' => 1];
                    @endphp
                    {{-- 2026-08-30 (mesure en production, fiche 39486) : TOUS les appels @glossarize()
                         de cette vue utilisent désormais $glossOpts (= ['max_occ' => 1], déclaré une
                         seule fois ci-dessus). Avant ce correctif, aucune option n'était passée nulle
                         part dans ce fichier - donc le plafond par défaut de GlossaryLinkifier
                         (MAX_OCCURRENCES_PER_TERM = 10, GLOBAL et non par section, car
                         $seenThisRequest est partagé entre les ~15 appels de cette page)
                         s'appliquait tel quel. Résultat mesuré : « firmware » lié 5 fois sur une seule
                         fiche dont 2 fois dans la même section « À retenir ». Échantillon indépendant
                         de 73 fiches réelles (sitemap.xml, réparties) : 11 liens/page en médiane, 31 au
                         maximum, et 61/73 fiches (83,6 %) portaient au moins un terme répété 2 fois ou
                         plus DANS LA MÊME section (jusqu'à 6 fois dans le pire cas). `per_section`
                         (tâche #1350) n'aurait rien changé ici : chaque appel de ce fichier reçoit un
                         fragment de texte SANS <h2> (les titres de section sont rendus par Blade, hors
                         de l'appel), donc $currentSection resterait à 0 pour les 15 appels - un
                         per_section => true y serait inerte. `max_occ => 1` réutilise le MÊME mécanisme
                         déjà en place pour le glossaire (Dictionary/show.blade.php, tâche #300, « éviter
                         saturation visuelle ») et pour le blog (FrontTheme/blog/show.blade.php, tâche
                         #1350) - aucune abstraction nouvelle. Effet : un terme ne peut plus être lié
                         qu'une seule fois sur toute la fiche, peu importe le nombre de sections où il
                         est cité (comportement « première occurrence globale » déjà documenté comme
                         intention d'origine dans le docblock de self::$seenThisRequest, restauré ici
                         pour les actualités). Test de non-régression :
                         Modules/News/tests/Feature/GlossaryLinkDensityTest.php. --}}
                    @if($essentialText)
                        <aside class="nw-tldr" aria-label="{{ __("L'essentiel") }}">
                            <p>@glossarize(e($essentialText), $glossOpts)</p>
                        </aside>
                        <p class="nw-essential-transparency">{{ $transparencyText }}</p>

                        {{-- Barre d'interactions (+ menu admin partage), descendue sous
                             « L'essentiel » (point 3). « Ajouter à mon journal » masqué pour un
                             visiteur non connecté : on ne transmet journalSourceType que si
                             authentifié - le partial (hors périmètre ici) cache déjà tout le bloc
                             quand ce paramètre est vide. --}}
                        @include('fronttheme::partials.article-action-bar', [
                            'model' => $article,
                            'modelType' => 'Modules\\News\\Models\\NewsArticle',
                            'journalSourceType' => auth()->check() ? 'news' : null,
                            'adminShareItems' => auth()->user()?->isSuperAdmin() ? $article->adminShareContents() : null,
                        ])
                        {{-- Partage natif « Partager » retiré (v1.196.4, demande fondateur 2026-08-21) :
                             jugé inutile et encombrant pour les lecteurs. Redondant avec « Copier le
                             lien » (barre d'interactions ci-dessus) et la barre de partage flottante
                             par réseau. Restaurable en version mobile-seulement si souhaité. --}}
                    @endif

                    {{-- Lead : hook IA + auto-link glossaire 2026-05-05 #141 - affiché
                         séparément seulement s'il n'a pas déjà servi de contenu à « L'essentiel »
                         ci-dessus (cas où le tldr est absent mais le hook présent). --}}
                    @if($ss && !empty($ss['hook']) && !$essentialUsedHook)
                        <p class="nw-lead">@glossarize(e($ss['hook']), $glossOpts)</p>
                    @endif

                    @if($isComposed)
                        {{-- Richesse v1.188.0 (design doc "Actus - composition manuelle assistée"
                             2026-08-15, section "Richesse v1.188.0 - structure fixe composée") :
                             fiche COMPOSÉE, ordre FIXE et libellés CONSTANTS - « le lecteur
                             retrouve toujours la même maison ». Chaque section est NULLABLE avec
                             droit d'omission silencieuse (@if(!empty(...)) partout : aucun titre
                             orphelin, aucun espace résiduel quand une section est absente). Section
                             1 « L'essentiel » déjà rendue plus haut (encadré nw-tldr, hook/tldr
                             partagés avec l'ancien résumé machine) ; section 9 « Sources » rendue
                             plus bas (bloc partagé, inchangé). Sections 2 à 8 ci-dessous. --}}

                        {{-- 2. À retenir --}}
                        @if(!empty($ss['key_points']))
                        <h2 class="nw-section-heading">{{ __('À retenir') }}</h2>
                        <ul class="nw-key-list">
                            @foreach($ss['key_points'] as $point)
                                <li>@glossarize(e($point), $glossOpts)</li>
                            @endforeach
                        </ul>
                        @endif

                        {{-- 3. Pourquoi ça compte --}}
                        @if(!empty($ss['why_important']))
                        <h2 class="nw-section-heading">{{ __('Pourquoi ça compte') }}</h2>
                        <div class="nw-why">
                            <p>@glossarize(e($ss['why_important']), $glossOpts)</p>
                        </div>
                        @endif

                        {{-- 4. Chiffre-clé --}}
                        @if(!empty($ss['key_number']))
                        <h2 class="nw-section-heading">{{ __('Chiffre-clé') }}</h2>
                        <div class="nw-why">
                            <p><span class="nw-stat">{{ $ss['key_number'] }}</span></p>
                        </div>
                        @endif

                        {{-- 5. Citation - quote composé = objet {text, author}, distinct de
                             l'ancien quote-attribution (chaîne + attribution calculée sur
                             l'article) rendu dans la branche @else ci-dessous. --}}
                        @if(!empty($ss['quote']['text'] ?? null))
                        <h2 class="nw-section-heading">{{ __('Citation') }}</h2>
                        <blockquote class="nw-quote" @if(!empty($article->resolved_url ?? $article->url)) cite="{{ $article->resolved_url ?? $article->url }}" @endif>
                            « @glossarize(e($ss['quote']['text']), $glossOpts) »
                            @if(!empty($ss['quote']['author']))
                                <cite>{{ $ss['quote']['author'] }}</cite>
                            @endif
                        </blockquote>
                        @endif

                        {{-- 6. Ce que ça change au Québec - admissible seulement sur preuve
                             québécoise datée (décision éditoriale, jamais forcée côté code). --}}
                        @if(!empty($ss['angle_qc_ca']))
                        <h2 class="nw-section-heading">{{ __('Ce que ça change au Québec') }}</h2>
                        <p class="nw-expert">🇨🇦 @glossarize(e($ss['angle_qc_ca']), $glossOpts)</p>
                        @endif

                        {{-- 7. Action concrète - bonus Codex (design doc) : cette clé n'était
                             visible QUE dans le texte de partage jusqu'ici, désormais visible sur
                             la fiche. --}}
                        @if(!empty($ss['action_concrete']))
                        <h2 class="nw-section-heading">{{ __('Action concrète') }}</h2>
                        <div class="nw-why">
                            <p>@glossarize(e($ss['action_concrete']), $glossOpts)</p>
                        </div>
                        @endif

                        {{-- 8. Repères datés - jalons d'archives internes juxtaposés, jamais
                             causaux. --}}
                        @if(!empty($ss['reperes_dates']))
                        <h2 class="nw-section-heading">{{ __('Repères datés') }}</h2>
                        <ul class="nw-key-list">
                            @foreach($ss['reperes_dates'] as $repere)
                                <li>
                                    @if(!empty($repere['date']))<strong>{{ $repere['date'] }}</strong> - @endif
                                    @if(!empty($repere['url']))
                                        <a href="{{ $repere['url'] }}" target="_blank" rel="noopener">{{ $repere['texte'] ?? '' }}</a>
                                    @else
                                        {{ $repere['texte'] ?? '' }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @endif
                    @else
                        {{-- R10 - Citation verbatim extraite de la source externe (R7 AiSummary).
                             Attribution complète (journaliste, média, date, lien original) via le
                             composant réutilisable x-news::quote-attribution - conformité article
                             29.2 Loi sur le droit d'auteur (design doc "Attribution citation 29.2
                             LDA", 2026-08-13). --}}
                        @if($ss && !empty($ss['quote']))
                            <blockquote class="nw-quote" @if(!empty($article->resolved_url ?? $article->url)) cite="{{ $article->resolved_url ?? $article->url }}" @endif>
                                « @glossarize(e($ss['quote']), $glossOpts) »
                                <x-news::quote-attribution :article="$article" />
                            </blockquote>
                        @endif
                    @endif

                    {{-- Actus 2.0 - Sources (design doc section 7) : liste « Sources » pour une
                         fiche comparative uniquement, remplace le bloc à source unique plus bas
                         (@unless($isDigest) ajouté autour de ce bloc, aucune suppression). --}}
                    @if($isDigest && !empty($ss['sources']))
                        <h2 class="nw-section-heading">{{ __('Sources') }}</h2>
                        <ul class="nw-sources-list">
                            @foreach($ss['sources'] as $src)
                                <li>
                                    <a href="{{ $src['url'] ?? '#' }}" target="_blank" rel="noopener">{{ $src['source_name'] ?? __('Source') }}</a>
                                    @if(!empty($src['author']))
                                        <span class="nw-sources-author"> &mdash; {{ $src['author'] }}</span>
                                    @endif
                                    @if(!empty($src['angle']))
                                        <span class="nw-sources-angle"> : @glossarize(e($src['angle']), $glossOpts)</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Résumé structuré (ancien résumé MACHINE uniquement - une fiche composée
                         rend ses sections dans le bloc @if($isComposed) plus haut, jamais ici,
                         pour ne jamais dupliquer key_points/why_important/angle_qc_ca). --}}
                    @unless($isComposed)
                    @if($ss)
                        @if(!empty($ss['key_points']))
                        <h2 class="nw-section-heading">{{ __('Que faut-il retenir ?') }}</h2>
                        <ul class="nw-key-list">
                            @foreach($ss['key_points'] as $point)
                                <li>@glossarize(e($point), $glossOpts)</li>
                            @endforeach
                        </ul>
                        @endif

                        @if(isset($ss['why_important']))
                        <h2 class="nw-section-heading">{{ __('Pourquoi cette nouvelle compte-t-elle ?') }}</h2>
                        <div class="nw-why">
                            <p>@glossarize(e($ss['why_important']), $glossOpts)</p>
                            @if(!empty($ss['key_stat']))
                                <p style="margin-top: 0.75rem; margin-bottom: 0;"><span class="nw-stat">{{ $ss['key_stat'] }}</span></p>
                            @endif
                        </div>
                        @endif

                        {{-- Actus 2.0 - divergences entre sources (design doc section 7). --}}
                        @if(!empty($ss['divergences']))
                        <h2 class="nw-section-heading">{{ __('Ce que disent les sources différemment') }}</h2>
                        <ul class="nw-key-list">
                            {{-- Garde défensive : un modèle IA peut renvoyer une chaîne au lieu du tableau contractuel. --}}
                            @foreach((is_array($ss['divergences']) ? $ss['divergences'] : [$ss['divergences']]) as $divergence)
                                <li>@glossarize(e($divergence), $glossOpts)</li>
                            @endforeach
                        </ul>
                        @endif

                        {{-- Actus 2.0 - contexte d'archives, uniquement si pertinent (jamais une liste factice). --}}
                        @if(is_array($ss['archive_context'] ?? null) && !empty($ss['archive_context']['summary']))
                        <h2 class="nw-section-heading">{{ __('Ce qui a changé') }}</h2>
                        <div class="nw-why">
                            <p>@glossarize(e($ss['archive_context']['summary']), $glossOpts)</p>
                            @if(!empty($ss['archive_context']['related']))
                                <ul class="nw-key-list" style="margin-top: 0.75rem; margin-bottom: 0;">
                                    @foreach($ss['archive_context']['related'] as $relatedArchive)
                                        @if(!empty($relatedArchive['url']))
                                            <li><a href="{{ $relatedArchive['url'] }}">{{ $relatedArchive['title'] ?? $relatedArchive['url'] }}</a></li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        @endif

                        {{-- Actus 2.0 - angle québécois/canadien, jamais affiché si absent/nul (jamais forcé côté prompt). --}}
                        @if(!empty($ss['angle_qc_ca']))
                        <p class="nw-expert">🇨🇦 @glossarize(e($ss['angle_qc_ca']), $glossOpts)</p>
                        @endif

                        @if(!empty($ss['expert_name']))
                            <p class="nw-expert">💬 <strong>{{ $ss['expert_name'] }}</strong>@if(!empty($ss['expert_role'])), {{ $ss['expert_role'] }}@endif</p>
                        @endif

                        @if(!empty($ss['audience']))
                        <p class="nw-audience">{{ __('Public concerné') }} : {{ implode(', ', $ss['audience']) }}</p>
                        @endif
                    @endif
                    @endunless
                    {{-- Le repli $article->summary (ex-encadré « Résumé IA ») est désormais
                         rendu plus haut par l'encadré unique « L'essentiel » (point 2, panel
                         2026-08-17) - aucun second rendu ici. --}}

                    {{-- Implémentation /actu2 - volet serveur (design doc "Actus - composition
                         manuelle assistée" 2026-08-15, section "Implémentation /actu2 - volet
                         serveur (2026-08-17)") - citation STATIQUE d'un post X quand l'ORIGINAL
                         retrouvé par le skill est lui-même un post. Placée après le résumé, dans
                         les DEUX branches d'affichage du corps (@if($ss) et
                         @elseif($article->summary) ci-dessus) puisqu'elle vit après leur
                         branchement. Jamais le widget platform.x.com (script tiers interdit). --}}
                    @php
                        $originalPost = is_array($article->original_post ?? null) ? $article->original_post : null;
                    @endphp
                    @if($originalPost && !empty($originalPost['text']))
                        <blockquote class="nw-post-quote">
                            <p class="nw-post-quote__text">« {{ $originalPost['text'] }} »</p>
                            <footer class="nw-post-quote__footer">
                                @if(!empty($originalPost['author']))
                                    <span class="nw-post-quote__author">{{ $originalPost['author'] }}</span>
                                @endif
                                @if(!empty($originalPost['handle']))
                                    <span class="nw-post-quote__handle">{{ $originalPost['handle'] }}</span>
                                @endif
                                @if(!empty($originalPost['date']))
                                    <span class="nw-post-quote__date">{{ $originalPost['date'] }}</span>
                                @endif
                                @if(!empty($originalPost['url']))
                                    <a href="{{ $originalPost['url'] }}" target="_blank" rel="noopener nofollow" class="nw-post-quote__link">{{ __('Voir sur X') }} &rarr;</a>
                                @endif
                            </footer>
                        </blockquote>
                    @endif

                    {{-- FAQ --}}
                    @if($ss && isset($ss['faq_question']))
                    <div class="nw-faq">
                        <h3>{{ $ss['faq_question'] }}</h3>
                        <p>@glossarize(e($ss['faq_answer']), $glossOpts)</p>
                    </div>
                    @endif

                    {{-- Le repli sur le texte source (ex-"Description originale") a été retiré
                         (design doc "Actus - zéro copie du texte source", 2026-08-13, section
                         4.5) : le corps est désormais le résumé structuré uniquement (bloc
                         @if($ss) plus haut, avec repli @elseif($article->summary)). Le
                         garde-fou anti-corps-vide (section 4.4) empêche toute fiche sans résumé
                         exploitable d'être servie ici (PublicNewsController::show(), 404). --}}

                    {{-- Schema.org JSON-LD DefinedTermSet (couvre les zones glossarized ci-dessus) --}}
                    @include('core::partials.glossary-jsonld')

                    {{-- $externalUrl / $isGoogleNewsUnresolved / $primarySources / $niveauPreuveLabel
                         déjà calculés en haut de page (ligne de provenance, point 4) - réutilisés
                         ici tels quels, aucun recalcul (DRY). --}}
                    {{-- Actus 2.0 : bloc à source unique remplacé par la liste « Sources » plus
                         haut pour une fiche comparative (design doc section 7), aucune suppression. --}}
                    @unless($isDigest)
                    @if($niveauPreuveLabel)
                        <p class="nw-niveau-preuve"><span class="nw-niveau-preuve__pill">{{ $niveauPreuveLabel }}</span></p>
                    @endif
                    @if(! empty($primarySources))
                        {{-- Bonification panel 2026-08-17 (soir) - section « Sources » EN FIN de
                             fiche (jamais une citation par affirmation, leçon projet consignée en
                             mémoire "exiger-des-sources-sans-issue-licite") : les sources
                             primaires d'abord, puis le relais média existant, désormais RENOMMÉ
                             « Relais média » puisque la source primaire prime. --}}
                        <h2 class="nw-section-heading">{{ __('Sources') }}</h2>
                        <ul class="nw-sources-list">
                            @foreach($primarySources as $primarySource)
                                @if(!empty($primarySource['url']))
                                <li>
                                    <a href="{{ $primarySource['url'] }}" target="_blank" rel="noopener nofollow">{{ $primarySource['label'] ?? __('Source primaire') }}</a>
                                    @if(!empty($primarySource['note']))
                                        <span class="nw-sources-angle"> : {{ $primarySource['note'] }}</span>
                                    @endif
                                </li>
                                @endif
                            @endforeach
                            @if(! $isGoogleNewsUnresolved)
                                @if($nwRelayLine = $article->displayRelayName())
                                <li>
                                    <a href="{{ $externalUrl }}" target="_blank" rel="noopener">{{ __('Relais média :') }} {{ $nwRelayLine }} &rarr;</a>
                                    @if($article->source?->language === 'en')
                                        &nbsp;·&nbsp;<a href="https://translate.google.com/translate?sl=en&tl=fr&u={{ urlencode($externalUrl) }}" target="_blank" rel="noopener">{{ __('Lire en français') }}</a>
                                    @endif
                                </li>
                                @endif
                            @else
                                <li>{{ __('Relais média :') }} <strong>{{ $article->source?->name ?? __('Google News') }}</strong></li>
                            @endif
                        </ul>
                    @elseif(! $isGoogleNewsUnresolved)
                    <div style="text-align: center; margin: 2rem 0; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                        <a href="{{ $externalUrl }}" target="_blank" rel="noopener" class="nw-cta">{{ __('Voir l\'article original') }} &rarr;</a>
                        @if($article->source?->language === 'en')
                            <a href="https://translate.google.com/translate?sl=en&tl=fr&u={{ urlencode($externalUrl) }}" target="_blank" rel="noopener" class="nw-cta" style="background: #4285F4;">{{ __('Lire en français') }} <i class="ti-world" style="margin-left: 4px;"></i></a>
                        @endif
                    </div>
                    @else
                    <div style="text-align: center; margin: 2rem 0; padding: 12px 16px; background: #f9fafb; border-radius: 8px; color: #6b7280; font-size: 14px;">
                        {{ __('Source :') }} <strong>{{ $article->source?->name ?? __('Google News') }}</strong>
                    </div>
                    @endif
                    @endunless

                    {{-- Fin de page dégraissée (point 5, panel 2026-08-17) : le lien « article
                         précédent » a été retiré (redondant avec les cartes connexes et le fil
                         chronologique de l'index) - navigation « suivant » seule conservée. --}}
                    @if($nextArticle)
                    <nav class="nw-nav">
                        <a href="{{ route('news.show', $nextArticle) }}" class="nw-nav-next">{{ Str::limit($nextArticle->seo_title ?? $nextArticle->title, 55) }} &rarr;</a>
                    </nav>
                    @endif

                    {{-- Articles connexes --}}
                    @if($relatedArticles->isNotEmpty())
                    <div class="nw-related">
                        <h3>{{ __('Articles connexes') }}</h3>
                        <div class="nw-related-grid">
                            @foreach($relatedArticles as $related)
                            <div class="nw-related-card">
                                <a href="{{ route('news.show', $related) }}">
                                    @if($related->image_url)
                                        <img src="{{ $related->versionedImageUrl() }}" alt="{{ $related->seo_title ?? $related->title }}" class="nw-related-img" loading="lazy">
                                    @endif
                                    <div class="nw-related-title">{{ $related->seo_title ?? $related->title }}</div>
                                    <div class="nw-related-meta">{{ $related->displaySourceName() }} &middot; {{ $related->pub_date?->diffForHumans() }}</div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Outils mentionnés dans cette actualité (maillage SEO interne) --}}
                    @php $articleTools = $article->relationLoaded('tools') ? $article->tools : $article->tools()->published()->get(); @endphp
                    @if($articleTools->isNotEmpty())
                    <div style="margin: 28px 0; padding: 20px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: var(--r-base, 8px);">
                        <p style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.06em; color: #6B7280; margin: 0 0 14px;">🔧 {{ __('Outils mentionnés') }}</p>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            @foreach($articleTools as $linkedTool)
                            @php
                                $toolLocale = app()->getLocale();
                                $toolSlug = $linkedTool->getTranslation('slug', $toolLocale, false)
                                    ?: $linkedTool->getTranslation('slug', 'fr_CA', false)
                                    ?: $linkedTool->getTranslation('slug', 'en', false)
                                    ?: '';
                                $toolName = $linkedTool->getTranslation('name', $toolLocale, false)
                                    ?: $linkedTool->getTranslation('name', 'fr_CA', false)
                                    ?: $linkedTool->getTranslation('name', 'en', false)
                                    ?: '';
                            @endphp
                            @if($toolSlug)
                            <a href="{{ route('directory.show', $toolSlug) }}"
                               style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border: 1px solid #D1D5DB; border-radius: 99px; text-decoration: none; font-size: 0.85rem; font-weight: 600; color: var(--c-dark, #111827); background: #fff; transition: background 0.15s, border-color 0.15s;"
                               onmouseover="this.style.background='#EEF7FF'; this.style.borderColor='var(--c-primary)'" onmouseout="this.style.background='#fff'; this.style.borderColor='#D1D5DB'">
                                {{ $toolName }}
                            </a>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Fin de page dégraissée (point 5, panel 2026-08-17) : un seul lien
                         générique (Glossaire Techno) plutôt que le maillage evergreen partagé
                         blog+actualités (fronttheme::partials.evergreen-related, hors périmètre
                         de cette édition) - bloc autonome au même gabarit visuel (nw-section-heading).

                         « Article de blogue lié » (2026-08-29) : un second lien, curaté via
                         news:apply --payload related_article_slugs (Modules\News\Console\
                         NewsApplyCommand), rejoint ce même bloc - jamais de CSS neuf
                         (.nw-plus-loin-link existe déjà, display:inline-block, tolère plusieurs
                         liens). Plafond de 1 imposé par la commande, jamais ici : au plus UN
                         second lien peut donc jamais apparaître. Filtre published() réappliqué
                         AU RENDU (pas seulement à l'attache), même défense en profondeur que le
                         bloc « Outils mentionnés » plus haut - un article lié puis dépublié
                         après coup ne doit jamais rester visible sur une fiche publique. --}}
                    @php
                        $linkedBlogArticle = class_exists(\Modules\Blog\Models\Article::class)
                            ? $article->blogArticles()->published()->first()
                            : null;
                        $linkedBlogArticleSlug = '';
                        $linkedBlogArticleTitle = '';
                        if ($linkedBlogArticle) {
                            $blogLocale = app()->getLocale();
                            $linkedBlogArticleSlug = $linkedBlogArticle->getTranslation('slug', $blogLocale, false)
                                ?: $linkedBlogArticle->getTranslation('slug', 'fr_CA', false)
                                ?: $linkedBlogArticle->getTranslation('slug', 'en', false)
                                ?: '';
                            $linkedBlogArticleTitle = $linkedBlogArticle->getTranslation('title', $blogLocale, false)
                                ?: $linkedBlogArticle->getTranslation('title', 'fr_CA', false)
                                ?: $linkedBlogArticle->getTranslation('title', 'en', false)
                                ?: '';
                        }
                    @endphp
                    @if(Route::has('dictionary.index'))
                    <nav aria-label="{{ __('Pour aller plus loin') }}" class="nw-plus-loin">
                        <h2 class="nw-section-heading">{{ __('Pour aller plus loin') }}</h2>
                        <a href="{{ route('dictionary.index') }}" class="nw-plus-loin-link">{{ __('Glossaire Techno') }}</a>
                        @if($linkedBlogArticleSlug !== '')
                        {{-- Défauts 1+2 (2026-08-29) : mt-2 (Bootstrap, déjà chargé par le thème
                             fronttheme::layouts.master et déjà utilisé ailleurs dans ce module -
                             aucune classe d'espacement équivalente dans charte.css/nw-*) comble
                             les 0px mesurés entre les deux pilules. Mesuré au navigateur : 7,5px
                             (0,5rem à 15px, le html{font-size} de ce thème, pas 16px) - 0,5px
                             sous le plancher de 8px demandé, écart jugé imperceptible plutôt que
                             d'écrire un CSS neuf pour un demi-pixel (DRY, règle 11 des conventions du projet).
                             Préfixe « Article : » calqué sur le SEUL motif déjà en place chez le
                             lien Glossaire voisin (un libellé qui NOMME sa destination, jamais
                             une icône) - même forme que « Source : » plus haut sur cette fiche. --}}
                        <a href="{{ route('blog.show', $linkedBlogArticleSlug) }}" class="nw-plus-loin-link mt-2">{{ __('Article :') }} {{ $linkedBlogArticleTitle }}</a>
                        @endif
                    </nav>
                    @endif

                    {{-- Commentaires - 2026-05-27 #312 DÉSACTIVÉS sur actualités (décision user).
                         Pour réactiver : retirer le `&& false` ci-dessous OU basculer flag config('news.comments_enabled', false). --}}
                    @if(class_exists(\Modules\Community\Livewire\CommentsThread::class) && config('news.comments_enabled', false))
                        <div class="mt-4 pt-4 border-top">
                            @livewire('community-comments-thread', [
                                'commentableType' => \Modules\News\Models\NewsArticle::class,
                                'commentableId' => $article->id
                            ])
                        </div>
                    @endif

                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="{{ route('news.index') }}" class="nw-back">&larr; {{ __('Retour aux actualités') }}</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
