<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@php $shareData = $tool->getShareData(); @endphp
@section('title', $tool->name . ' - ' . config('app.name'))
@section('meta_description', $shareData['meta_description'] ?? 'Anonymisez vos textes avant de les envoyer à une IA, puis restaurez vos vraies données dans la réponse. 100 % local, conforme Loi 25 et RGPD.')
@section('og_type', $shareData['og_type'] ?? 'website')
@section('og_image', $shareData['og_image'] ?? '')
@section('share_text', $shareData['share_text'] ?? '')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('assets/tools/anonymiseur/anon-v2.css') }}?v={{ config('version.semver') }}">
<link rel="canonical" href="{{ url('/outils/anonymiseur') }}">
@php
    $anonymizerJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Anonymiseur de texte',
        'alternateName' => ['Anonymisation de prompts', 'Pseudonymisation client-side', 'Anonymizer La veille de Stef'],
        'description' => "Outil 100 % local qui anonymise les données personnelles (noms, adresses, courriels, téléphones, dates, montants, numéros de dossier) avant l'envoi à une IA, puis restaure les vraies données dans la réponse reformulée. Conforme Loi 25 (Québec) et RGPD. Aucune donnée ne quitte votre navigateur.",
        'applicationCategory' => ['SecurityApplication', 'BusinessApplication'],
        'operatingSystem' => 'Web',
        'url' => url('/outils/anonymiseur'),
        'isAccessibleForFree' => true,
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'CAD'],
        'featureList' => [
            'Détection automatique des données sensibles (regex québécoise)',
            'Pseudonymisation réversible avec table de correspondance locale',
            'Restauration des réponses IA reformulées (insensible casse/accents)',
            'Conforme Loi 25 + RGPD',
        ],
        'softwareVersion' => '2.0',
        'dateModified' => now()->toIso8601String(),
        'author' => function_exists('lv_jsonld_author_stephane') ? lv_jsonld_author_stephane() : null,
        'publisher' => function_exists('lv_jsonld_publisher') ? lv_jsonld_publisher() : null,
    ];
@endphp
<script type="application/ld+json">{!! json_encode(array_filter($anonymizerJsonLd), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5 anon-wrap">

                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->icon }} {{ $tool->name }}</h1>
                                <p class="text-muted mb-0">{{ __('Anonymisez vos données pour l\'IA, puis remettez vos vraies données dans sa réponse. 100 % local.') }}</p>
                            </div>
                            <div class="d-flex gap-1 align-items-center" style="flex-shrink:0;">
                                @include('tools::partials.fullscreen-btn')
                                @include('tools::partials.share-btn', ['tool' => $tool])
                            </div>
                        </div>

                        <div id="infoBanner" class="mb-3">
                            <x-core::accordion id="anonymTrust" icon="🛡️" :title="__('100 % local')" :subtitle="__('Traitement dans votre navigateur — aucun contenu envoyé à un serveur')" :open="false">
                                <p style="margin:0 0 .5rem;">{{ __('Votre texte, les règles et les correspondances vraie ↔ fictive restent sur votre appareil. Rien n\'est transmis : seul le texte déjà anonymisé sort, et uniquement si VOUS le copiez vers une IA externe.') }}</p>
                                <p style="margin:0;font-size:.88rem;color:#52586a;">
                                    <a href="/glossaire/loi-25" target="_blank" rel="noopener">{{ __('Conforme Loi 25') }}</a> ·
                                    <a href="/glossaire/rgpd" target="_blank" rel="noopener">{{ __('Conforme RGPD') }}</a> ·
                                    <a href="/glossaire/anonymisation" target="_blank" rel="noopener">{{ __('Comprendre l\'anonymisation') }}</a>
                                </p>
                            </x-core::accordion>
                        </div>

                        {{-- Navigation des 2 étapes --}}
                        <nav class="anon-steps" aria-label="{{ __('Étapes') }}">
                            <button type="button" class="anon-step active" data-step="1"><span class="num">1</span><span class="lbl">{{ __('Anonymiser votre texte') }}</span></button>
                            <button type="button" class="anon-step" data-step="2"><span class="num">2</span><span class="lbl">{{ __('Restaurer la réponse IA') }}</span></button>
                        </nav>

                        {{-- ÉTAPE 1 : éditeur annoté + aperçu --}}
                        <div class="anon-panel active" data-step-content="1">
                            <div class="anon-help">
                                💡 <strong>{{ __('Comment ça marche') }}</strong> : {{ __('collez votre texte, cliquez « Détecter ». Les données repérées sont soulignées : cliquez dessus (ou « Tout anonymiser ») pour les masquer (elles deviennent surlignées). Pour masquer autre chose, sélectionnez simplement un passage avec la souris — un bouton « 🕵️ Anonymiser » apparaît juste au-dessus. Le texte prêt pour l\'IA apparaît à droite.') }}
                            </div>

                            {{-- Barre d'outils : 1 action primaire + sélection + menu « Actions » (réduit la surcharge — tendance 2026) --}}
                            <div class="anon-toolbar" role="toolbar" aria-label="{{ __('Actions d\'anonymisation') }}">
                                <button type="button" id="btnDetect" class="anon-btn">🔍 {{ __('Détecter') }}</button>
                                <button type="button" id="btnAnonymizeSelection" class="anon-btn secondary" title="{{ __('Sélectionnez un passage dans votre texte, puis cliquez') }}">✍️ {{ __('Anonymiser la sélection') }}</button>
                                <div class="anon-menu-wrap">
                                    <button type="button" id="btnActionsMenu" class="anon-btn secondary" aria-haspopup="menu" aria-expanded="false">⋯ {{ __('Actions') }}</button>
                                    <div id="actionsMenu" class="anon-menu hidden" role="menu" aria-label="{{ __('Plus d\'actions') }}">
                                        <button type="button" id="btnAnonymizeAll" class="anon-menu-item" role="menuitem">🕵️ {{ __('Tout anonymiser') }}</button>
                                        <button type="button" id="btnEditText" class="anon-menu-item" role="menuitem">✏️ {{ __('Modifier le texte') }}</button>
                                        <button type="button" id="btnModeToggle" class="anon-menu-item" role="menuitem" aria-pressed="false" title="{{ __('Basculer entre faux noms réalistes et jetons balisés stables') }}">🎭 {{ __('Réaliste') }}</button>
                                        <button type="button" id="btnResetAll" class="anon-menu-item" role="menuitem">↺ {{ __('Réinitialiser') }}</button>
                                    </div>
                                </div>
                            </div>
                            <p id="anonModeHint" class="anon-help" style="display:none;margin-top:.5rem;">
                                🏷️ <strong>{{ __('Mode jetons stables') }}</strong> : {{ __('vos données deviennent des balises comme [PERSONNE_1]. Ajoutez à votre demande à l\'IA : « garde les jetons entre crochets intacts ». Restauration la plus fiable même si l\'IA reformule beaucoup.') }}
                            </p>

                            <div class="anon-grid">
                                {{-- Volet gauche : éditeur annoté --}}
                                <div>
                                    <label class="anon-pane-label" for="anonSource">{{ __('Votre texte (cliquez les passages soulignés pour les anonymiser)') }}</label>
                                    <div id="anonEditorWrap" class="mode-edit">
                                        <textarea id="anonSource" class="anon-textarea" placeholder="{{ __('Ex. : Le dossier #86734 pour M. Jean Dubé concerne le chantier du 15 rue de la Gare…') }}"></textarea>
                                        <div id="anonAnnotated" tabindex="0" role="textbox" aria-label="{{ __('Texte annoté — cliquez une entité pour l\'anonymiser') }}"></div>
                                    </div>
                                    <div class="anon-legend" aria-hidden="true">
                                        <span><span class="anon-cand">{{ __('souligné') }}</span> = {{ __('sera anonymisé') }}</span>
                                        <span><span class="anon-anon">{{ __('surligné') }}</span> = {{ __('anonymisé') }}</span>
                                    </div>
                                </div>

                                {{-- Volet droit : aperçu anonymisé --}}
                                <div>
                                    <label class="anon-pane-label" for="anonOutput">{{ __('Texte anonymisé (prêt pour l\'IA)') }}</label>
                                    <textarea id="anonOutput" class="anon-textarea" readonly aria-label="{{ __('Texte anonymisé prêt pour l\'IA') }}"></textarea>
                                    <div class="anon-actions">
                                        <button type="button" id="btnCopyAnon" class="anon-btn">📋 {{ __('Copier pour l\'IA') }}</button>
                                        <button type="button" class="anon-btn secondary anon-step" data-step="2">{{ __('J\'ai la réponse de l\'IA →') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ÉTAPE 2 : restauration --}}
                        <div class="anon-panel" data-step-content="2">
                            <div class="anon-help">
                                🔁 {{ __('Collez ci-dessous la réponse de l\'IA (même reformulée) : l\'outil remet automatiquement vos vraies données.') }}
                            </div>
                            <div class="anon-field">
                                <label class="anon-label" for="aiResponse">{{ __('Réponse de l\'IA à restaurer') }}</label>
                                <textarea id="aiResponse" class="anon-textarea" placeholder="{{ __('Collez ici la réponse de l\'IA contenant les valeurs anonymisées…') }}"></textarea>
                            </div>
                            <div class="anon-actions">
                                <button type="button" id="btnRestore" class="anon-btn">🔓 {{ __('Restaurer mes vraies données') }}</button>
                            </div>
                            <div id="restoreReport" class="anon-report" role="status" aria-live="polite"></div>
                            <div class="anon-field" style="margin-top:.5rem;">
                                <label class="anon-label" for="restoredOutput">{{ __('Résultat avec vos vraies données') }}</label>
                                <textarea id="restoredOutput" class="anon-textarea" readonly aria-label="{{ __('Texte restauré') }}"></textarea>
                            </div>
                            <div class="anon-actions">
                                <button type="button" id="btnCopyRestored" class="anon-btn">📋 {{ __('Copier le résultat') }}</button>
                            </div>
                        </div>

                    </div>{{-- /.card-body --}}

                    {{-- Bulle contextuelle : apparaît près de la sélection (pattern Medium/Notion) --}}
                    <div id="anonSelBubble" class="anon-sel-bubble hidden" role="tooltip">
                        <button type="button" id="anonSelBubbleBtn">🕵️ {{ __('Anonymiser') }}</button>
                    </div>
                </div>{{-- /.card --}}
            </div>{{-- /.col --}}
        </div>{{-- /.row --}}
    </div>{{-- /.container --}}
</section>
@include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'anonymiseur'])
@endsection

@push('scripts')
{{-- Désinscrit l'ancien Service Worker (outil pré-refonte) pour garantir la version actuelle --}}
<script>
(() => {
  try {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.getRegistrations().then(registrations => {
        registrations.forEach(r => { if (r.scope.includes('/outils/anonymiseur')) r.unregister(); });
      });
    }
    if (window.caches) {
      caches.keys().then(keys => { keys.forEach(k => { if (/anonym/i.test(k)) caches.delete(k); }); });
    }
  } catch (e) {}
})();
</script>
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-core.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-ui.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
