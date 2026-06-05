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
                                💡 <strong>{{ __('Comment ça marche') }}</strong> <button type="button" class="ct-btn ct-btn-ghost ct-btn-xs" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;margin-left:4px;flex-shrink:0;" data-help-key="anon-comment" aria-label="{{ __('Aide : comment ça marche') }}">?</button> : {{ __('collez votre texte, cliquez « Détecter ». Les données repérées sont soulignées : cliquez dessus (ou « Tout anonymiser ») pour les masquer (elles deviennent surlignées). Pour masquer autre chose, sélectionnez simplement un passage avec la souris — un bouton « 🕵️ Anonymiser » apparaît juste au-dessus. Le texte prêt pour l\'IA apparaît à droite.') }}
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

                            {{-- Bascule de vue : Éditeur pleine largeur / Split / Aperçu pleine largeur --}}
                            <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;">
                                <div class="anon-viewswitch" role="group" aria-label="{{ __('Affichage des volets') }}">
                                    <button type="button" data-view="editor" aria-pressed="false">✍️ {{ __('Éditeur') }}</button>
                                    <button type="button" data-view="split" aria-pressed="true">⬓ {{ __('Split') }}</button>
                                    <button type="button" data-view="preview" aria-pressed="false">👁️ {{ __('Aperçu') }}</button>
                                </div>
                                <button type="button" class="ct-btn ct-btn-ghost ct-btn-xs" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;margin-left:4px;flex-shrink:0;" data-help-key="anon-vues" aria-label="{{ __('Aide : affichage des volets') }}">?</button>
                            </div>

                            <div class="anon-grid">
                                {{-- Volet gauche : éditeur annoté --}}
                                <div class="anon-pane-editor">
                                    <label class="anon-pane-label" for="anonSource">{{ __('Votre texte') }} <button type="button" class="ct-btn ct-btn-ghost ct-btn-xs" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;margin-left:4px;flex-shrink:0;" data-help-key="anon-selection" aria-label="{{ __('Aide : masquer une donnée et options') }}">?</button></label>
                                    <div id="anonEditorWrap" class="mode-edit">
                                        <div id="anonSource" class="anon-textarea anon-rich" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ __('Votre texte — la mise en forme (gras, listes…) est conservée au collage') }}" data-placeholder="{{ __('Ex. : Le dossier #86734 pour M. Jean Dubé concerne le chantier du 15 rue de la Gare…') }}"></div>
                                        <div id="anonAnnotated" tabindex="0" role="textbox" aria-label="{{ __('Texte annoté — cliquez une entité pour l\'anonymiser') }}"></div>
                                    </div>
                                    <div class="anon-legend" aria-hidden="true">
                                        <span><span class="anon-cand">{{ __('souligné') }}</span> = {{ __('sera anonymisé') }}</span>
                                        <span><span class="anon-anon">{{ __('surligné') }}</span> = {{ __('anonymisé') }}</span>
                                    </div>
                                </div>

                                {{-- Volet droit : aperçu anonymisé --}}
                                <div class="anon-pane-preview">
                                    <label class="anon-pane-label" for="anonOutput">{{ __('Texte anonymisé (prêt pour l\'IA)') }} <button type="button" class="ct-btn ct-btn-ghost ct-btn-xs" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;margin-left:4px;flex-shrink:0;" data-help-key="anon-restauration" aria-label="{{ __('Aide : récupérer vos données après l\'IA') }}">?</button></label>
                                    <div class="anon-output-wrap">
                                        {{-- Copie toujours accessible en haut du panneau (pattern bloc de code), sans casser l'alignement --}}
                                        <button type="button" id="btnCopyAnonTop" class="anon-copy-float" aria-label="{{ __('Copier le texte anonymisé pour l\'IA') }}">📋 {{ __('Copier') }}</button>
                                        <textarea id="anonOutput" class="anon-textarea" readonly aria-label="{{ __('Texte anonymisé prêt pour l\'IA') }}"></textarea>
                                    </div>
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
                        <div class="anon-sel-main">
                            <button type="button" id="anonSelBubbleBtn">🕵️ {{ __('Anonymiser') }}</button>
                            <button type="button" id="anonSelBubbleCustomBtn" title="{{ __('Choisir ma propre valeur de remplacement') }}" aria-label="{{ __('Valeur personnalisée') }}">✎ {{ __('Ma valeur') }}</button>
                        </div>
                        <div id="anonSelBubbleCustom" class="anon-sel-custom hidden">
                            <input type="text" id="anonSelBubbleInput" placeholder="{{ __('Votre valeur…') }}" aria-label="{{ __('Valeur de remplacement personnalisée') }}">
                            <button type="button" id="anonSelBubbleConfirm" aria-label="{{ __('Confirmer la valeur') }}">✓</button>
                        </div>
                    </div>

                    {{-- Popover sur une occurrence déjà anonymisée : annuler ou rendre CETTE occurrence différente --}}
                    <div id="anonOccPopover" class="anon-sel-bubble hidden" role="dialog" aria-label="{{ __('Options de cette occurrence') }}">
                        <div class="anon-sel-main">
                            <button type="button" id="anonOccValue" title="{{ __('Saisir ma propre valeur (remplace partout)') }}">✎ {{ __('Ma valeur') }}</button>
                            <button type="button" id="anonOccDiff" title="{{ __('Une valeur différente uniquement pour cette occurrence-ci') }}">🔀 {{ __('Seulement ici') }}</button>
                            <button type="button" id="anonOccCancel" title="{{ __('Annuler l\'anonymisation') }}">↩︎ {{ __('Annuler') }}</button>
                        </div>
                        <div id="anonOccCustom" class="anon-sel-custom hidden">
                            <input type="text" id="anonOccInput" placeholder="{{ __('Valeur pour cette occurrence…') }}" aria-label="{{ __('Valeur de remplacement pour cette occurrence') }}">
                            <button type="button" id="anonOccConfirm" aria-label="{{ __('Confirmer') }}">✓</button>
                        </div>
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
<script>window.LV_ANON_VERSION = '{{ config('version.semver') }}';</script>
{{-- Aides contextuelles (composant officiel <x-core::help-modal> global) --}}
<script>
window.HELP_CONTENT = Object.assign(window.HELP_CONTENT || {}, {
  "anon-comment": {"title":"Comment ça marche?","body":"<p>Cet outil vous permet de masquer vos données personnelles avant de les envoyer à une intelligence artificielle, puis de les récupérer dans la réponse. Voici les 3 étapes :</p><ul><li><strong>1. Collez votre texte</strong> dans la zone d'édition.</li><li><strong>2. Masquez les données sensibles</strong> : soit en cliquant sur « Détecter » pour que l'outil repère automatiquement les noms, adresses, etc., soit en sélectionnant vous-même un passage avec la souris.</li><li><strong>3. Copiez le texte masqué</strong> vers l'IA, puis collez la réponse de l'IA dans l'outil et cliquez sur « Restaurer » pour retrouver vos vraies données.</li></ul><p><strong>Rassurez-vous : tout se fait dans votre navigateur, rien n'est envoyé sur Internet.</strong></p>"},
  "anon-vues": {"title":"Changer l'affichage","body":"<p>Vous pouvez choisir comment voir votre texte grâce au sélecteur en haut :</p><ul><li><strong>Éditeur</strong> : votre texte original en grand, idéal pour écrire ou modifier.</li><li><strong>Aperçu</strong> : le texte masqué en grand, pour vérifier ce qui sera envoyé à l'IA.</li><li><strong>Split</strong> : les deux côte à côte, pratique pour comparer sur de longs textes.</li></ul><p>Passez de l'un à l'autre sans perdre votre travail.</p>"},
  "anon-selection": {"title":"Masquer une donnée et gérer les options","body":"<p>Deux façons de masquer :</p><ul><li><strong>Cliquez sur un mot souligné</strong> (repéré automatiquement) pour le masquer immédiatement.</li><li><strong>Sélectionnez un passage</strong> avec la souris : un bouton « 🕵️ Anonymiser » apparaît. Cliquez dessus pour le masquer.</li></ul><p><strong>Choisir votre propre valeur</strong> : dans la bulle ou en cliquant un élément déjà masqué, utilisez « ✎ Ma valeur » pour décider par quoi remplacer la donnée (ex. « Jean Dupont » → « Client »).</p><p><strong>« 🔀 Seulement ici »</strong> : donne une valeur différente à <em>cette occurrence-ci seulement</em> — pratique si le même mot apparaît plusieurs fois et que vous voulez les distinguer (ex. deux « Montréal » → « Ville A » et « Ville B »). <strong>« ↩︎ Annuler »</strong> retire le masquage.</p>"},
  "anon-occurrence": {"title":"Gérer les éléments déjà masqués","body":"<p>Quand vous cliquez sur un élément déjà masqué (surligné), trois options s'offrent à vous :</p><ul><li><strong>« ✎ Ma valeur »</strong> : changez le remplacement pour toutes les occurrences identiques (exemple : tous les « Jean » deviennent « Client »).</li><li><strong>« 🔀 Seulement ici »</strong> : donnez une valeur différente à CETTE occurrence précise, les autres restent comme avant. Exemple : si vous avez deux fois « Montréal », vous pouvez remplacer l'un par « Ville A » et l'autre par « Ville B ».</li><li><strong>« ↩︎ Annuler »</strong> : retirez le masquage et remettez la donnée originale.</li></ul>"},
  "anon-restauration": {"title":"Récupérer vos données après l'IA","body":"<p>Une fois que vous avez reçu la réponse de l'IA (même si elle a reformulé le texte), faites ceci :</p><ul><li><strong>Copiez la réponse</strong> et collez-la dans la zone prévue.</li><li><strong>Cliquez sur « Restaurer »</strong> : l'outil retrouve automatiquement vos vraies données et les remet à leur place.</li></ul><p><strong>C'est fiable</strong> : l'outil garde en mémoire le lien entre les données masquées et les vraies, même si l'IA change la formulation.</p>"}
});
</script>
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-core.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-rich.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-ui.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
