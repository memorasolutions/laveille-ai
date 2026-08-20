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
    // Texte de partage PAR RÉSEAU (spec 2026-08-20 - corrige l'incohérence X : la barre flottante
    // du layout master utilise désormais LA MÊME variante pour l'intent ET le presse-papiers).
    // Réutilise la matière déjà extraite ci-dessus ($title/$hook/$whyImportant/$keyNumber/
    // $actionConcrete/$categoryTag) - aucune nouvelle lecture de l'article, DRY.
    $pageUrl = request()->url();

    // X : 90-160 caractères avant le lien (déjà prefillé séparément par l'intent), 0-2 hashtags,
    // une affirmation nette - jamais le titre verbatim.
    $xBody = \Illuminate\Support\Str::limit($keyNumber ?: ($hook ?: $title), 135, '…');
    $xHashtags = trim(($categoryTag ? $categoryTag . ' ' : '') . '#VeilleIA');
    $shareTextX = trim("🤖 {$xBody}\n\n{$xHashtags}");

    // LinkedIn : 250-600 caractères, les 140-210 premiers autonomes, 1re personne professionnelle,
    // angle « à retenir » + invitation à discuter, 2-4 hashtags. Chaque segment est plafonné
    // individuellement pour que le total reste dans la fourchette quelle que soit la longueur de
    // la matière source (hook/chiffre-clé/pourquoi ça compte tous potentiellement longs).
    $liOpening = \Illuminate\Support\Str::limit($hook ?: $title, 170, '…');
    $liTakeaway = $whyImportant ?: $actionConcrete;
    $liKeyLine = $keyNumber ? \Illuminate\Support\Str::limit("Le chiffre à retenir : {$keyNumber}", 110, '…') : null;
    $liWhyLine = $liTakeaway ? \Illuminate\Support\Str::limit("Pourquoi ça compte : {$liTakeaway}", 110, '…') : null;
    $liLines = array_filter([
        $liOpening,
        '',
        $liKeyLine,
        $liWhyLine,
        '',
        'Votre avis ? Je serais curieux de vous lire en commentaire.',
        '',
        $pageUrl,
        '',
        trim(($categoryTag ? $categoryTag . ' ' : '') . '#IntelligenceArtificielle #VeilleIA #Innovation'),
    ], fn($line) => $line !== null);
    $shareTextLinkedIn = implode("\n", $liLines);

    // Facebook : 0-140 caractères ou rien du tout (l'aperçu Open Graph fait le travail), 0-1
    // hashtag, conversationnel - si aucune accroche solide, on ne force aucun texte.
    $fbCore = $hook ? \Illuminate\Support\Str::limit($hook, 120, '…') : null;
    $fbLines = array_filter([$fbCore, $categoryTag ?: null, $pageUrl], fn($line) => $line !== null && $line !== '');
    $shareTextFacebook = $fbCore ? implode("\n", $fbLines) : '';

    // Messenger : 40-120 caractères, aucun hashtag, ton direct orienté destinataire. Le lien
    // Messenger de la barre flottante pointe vers la page de la marque (pas un intent article) -
    // le lien doit donc rester dans le texte copié pour rester utile une fois collé. Str::limit
    // porte sur la LIGNE COMPLÈTE (intro + accroche) pour garantir le plafond, quelle que soit
    // la longueur de la matière source.
    $msgLine = \Illuminate\Support\Str::limit('Je viens de voir ça, ça va t’intéresser : ' . ($keyNumber ?: ($hook ?: $title)), 115, '…');
    $shareTextMessenger = "{$msgLine}\n{$pageUrl}";
@endphp
@section('share_text_x', $shareTextX)
@section('share_text_linkedin', $shareTextLinkedIn)
@section('share_text_facebook', $shareTextFacebook)
@section('share_text_messenger', $shareTextMessenger)
@section('og_type', 'article')
{{-- og:image fallback chain : .jpg (prioritaire) → image_url originale → absent (externes inchangées) --}}
@if(!empty($article->image_url) && !str_starts_with($article->image_url, 'http'))
    @php
        $_jpgPath = preg_replace('/\.[^.]*$/', '.jpg', $article->image_url);
        $_ogImagePath = file_exists(public_path(ltrim($_jpgPath, '/'))) ? $_jpgPath : $article->image_url;
    @endphp
    @section('og_image', url($_ogImagePath).'?v='.($article->updated_at?->timestamp ?? '0'))
@elseif(!empty($article->image_url))
    @section('og_image', $article->image_url)
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
     configuré catégorie+date) - jamais $article->description. --}}
<meta name="llm:summary" content="{{ e($article->seo_title ?? $article->title) }} - {{ e($article->meta_description ?? $article->displayExcerpt(200)) }} ({{ e($article->displaySourceName()) }})">
<meta name="llm:keywords" content="actualité IA, {{ e($article->displaySourceName()) }}, intelligence artificielle, francophone, Québec">
<meta name="llm:url" content="{{ route('news.show', $article) }}">
@if($ss && isset($ss['faq_question']))
    {!! \Modules\SEO\Services\JsonLdService::render(
        \Modules\SEO\Services\JsonLdService::newsArticle($article),
        ['@type' => 'FAQPage', 'mainEntity' => [['@type' => 'Question', 'name' => $ss['faq_question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $ss['faq_answer'] ?? '']]]],
    ) !!}
@else
    {!! \Modules\SEO\Services\JsonLdService::render(\Modules\SEO\Services\JsonLdService::newsArticle($article)) !!}
@endif
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
    /* Partage natif mobile (point 6) - cible tactile 44px. */
    .nw-share-btn { min-height: 44px; min-width: 44px; justify-content: center; }
    /* Fin de page dégraissée (point 5) - un seul lien générique, même gabarit que le maillage
       evergreen partagé (fronttheme::partials.evergreen-related, hors périmètre ici). */
    .nw-plus-loin { margin-top: 44px; padding-top: 24px; border-top: 1px solid #e5e7eb; }
    .nw-plus-loin-link {
        display: inline-block; padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 999px;
        color: var(--c-primary); text-decoration: none; font-size: 0.9rem; font-weight: 600; background: #f8fafb;
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
                        // jamais l'étiquette technique brute.
                        $niveauPreuveLabels = [
                            'primaire' => __('Fondée sur la source originale'),
                            'mixte' => __('Sources originale et média'),
                            'relais' => __('D\'après un média relais'),
                        ];
                        $niveauPreuveLabel = $niveauPreuveLabels[$article->niveau_preuve ?? ''] ?? null;
                    @endphp
                    {{-- Ligne de provenance compacte (point 4) - fiche à source unique
                         uniquement : une fiche comparative affiche déjà sa propre liste
                         « Sources » plus bas (design doc section 7). --}}
                    @unless($isDigest)
                    @if(!empty($primarySources[0]['url'] ?? null))
                        <p class="nw-provenance">{{ __("D'après") }} <a href="{{ $primarySources[0]['url'] }}" target="_blank" rel="noopener nofollow">{{ $primarySources[0]['label'] ?? __('la source primaire') }}</a>@if($nwRelay = $article->displayRelayName()), {{ __('relayé par') }} {{ $nwRelay }}@endif</p>
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
                    @endphp
                    @if($essentialText)
                        <aside class="nw-tldr" aria-label="{{ __("L'essentiel") }}">
                            <p>@glossarize(e($essentialText))</p>
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
                        {{-- Partage natif mobile (point 6) : Web Share API si disponible, repli
                             sur le bouton « Copier le lien » déjà offert ci-dessus. Jamais de
                             fenêtre native bloquante (alert/confirm/prompt). Cible tactile 44px
                             (.nw-share-btn, règle CSS plus haut). Réutilise la classe .aab-btn du
                             partial ci-dessus (déjà chargée sur cette page) plutôt que d'en
                             recréer une - DRY. --}}
                        <div class="aab" style="border-bottom: none; padding-top: 0;">
                            <button type="button" class="aab-btn nw-share-btn" id="nw-share-btn-{{ $article->id }}" aria-label="{{ __('Partager') }}" title="{{ __('Partager') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                <span class="aab-label">{{ __('Partager') }}</span>
                            </button>
                        </div>
                        <script>
                            (function () {
                                var btn = document.getElementById('nw-share-btn-{{ $article->id }}');
                                if (!btn) { return; }
                                btn.addEventListener('click', function () {
                                    var shareTitle = {!! \Illuminate\Support\Js::from($article->seo_title ?? $article->title) !!};
                                    if (navigator.share) {
                                        navigator.share({ title: shareTitle, url: window.location.href }).catch(function () {});
                                        return;
                                    }
                                    navigator.clipboard.writeText(window.location.href);
                                    if (typeof window.toast === 'function') {
                                        window.toast({!! \Illuminate\Support\Js::from(__('Lien copié')) !!}, 'success', 2000);
                                    }
                                });
                            })();
                        </script>
                    @endif

                    {{-- Lead : hook IA + auto-link glossaire 2026-05-05 #141 - affiché
                         séparément seulement s'il n'a pas déjà servi de contenu à « L'essentiel »
                         ci-dessus (cas où le tldr est absent mais le hook présent). --}}
                    @if($ss && !empty($ss['hook']) && !$essentialUsedHook)
                        <p class="nw-lead">@glossarize(e($ss['hook']))</p>
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
                                <li>@glossarize(e($point))</li>
                            @endforeach
                        </ul>
                        @endif

                        {{-- 3. Pourquoi ça compte --}}
                        @if(!empty($ss['why_important']))
                        <h2 class="nw-section-heading">{{ __('Pourquoi ça compte') }}</h2>
                        <div class="nw-why">
                            <p>@glossarize(e($ss['why_important']))</p>
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
                            « @glossarize(e($ss['quote']['text'])) »
                            @if(!empty($ss['quote']['author']))
                                <cite>{{ $ss['quote']['author'] }}</cite>
                            @endif
                        </blockquote>
                        @endif

                        {{-- 6. Ce que ça change au Québec - admissible seulement sur preuve
                             québécoise datée (décision éditoriale, jamais forcée côté code). --}}
                        @if(!empty($ss['angle_qc_ca']))
                        <h2 class="nw-section-heading">{{ __('Ce que ça change au Québec') }}</h2>
                        <p class="nw-expert">🇨🇦 @glossarize(e($ss['angle_qc_ca']))</p>
                        @endif

                        {{-- 7. Action concrète - bonus Codex (design doc) : cette clé n'était
                             visible QUE dans le texte de partage jusqu'ici, désormais visible sur
                             la fiche. --}}
                        @if(!empty($ss['action_concrete']))
                        <h2 class="nw-section-heading">{{ __('Action concrète') }}</h2>
                        <div class="nw-why">
                            <p>@glossarize(e($ss['action_concrete']))</p>
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
                                « @glossarize(e($ss['quote'])) »
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
                                        <span class="nw-sources-angle"> : @glossarize(e($src['angle']))</span>
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
                                <li>@glossarize(e($point))</li>
                            @endforeach
                        </ul>
                        @endif

                        @if(isset($ss['why_important']))
                        <h2 class="nw-section-heading">{{ __('Pourquoi cette nouvelle compte-t-elle ?') }}</h2>
                        <div class="nw-why">
                            <p>@glossarize(e($ss['why_important']))</p>
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
                                <li>@glossarize(e($divergence))</li>
                            @endforeach
                        </ul>
                        @endif

                        {{-- Actus 2.0 - contexte d'archives, uniquement si pertinent (jamais une liste factice). --}}
                        @if(is_array($ss['archive_context'] ?? null) && !empty($ss['archive_context']['summary']))
                        <h2 class="nw-section-heading">{{ __('Ce qui a changé') }}</h2>
                        <div class="nw-why">
                            <p>@glossarize(e($ss['archive_context']['summary']))</p>
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
                        <p class="nw-expert">🇨🇦 @glossarize(e($ss['angle_qc_ca']))</p>
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
                        <p>@glossarize(e($ss['faq_answer']))</p>
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
                         de cette édition) - bloc autonome au même gabarit visuel (nw-section-heading). --}}
                    @if(Route::has('dictionary.index'))
                    <nav aria-label="{{ __('Pour aller plus loin') }}" class="nw-plus-loin">
                        <h2 class="nw-section-heading">{{ __('Pour aller plus loin') }}</h2>
                        <a href="{{ route('dictionary.index') }}" class="nw-plus-loin-link">{{ __('Glossaire Techno') }}</a>
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
