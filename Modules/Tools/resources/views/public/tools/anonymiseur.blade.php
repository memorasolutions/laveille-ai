<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@php $shareData = $tool->getShareData(); @endphp
@section('title', $tool->name . ' - ' . config('app.name'))
@section('meta_description', $shareData['meta_description'] ?? 'Anonymisez vos textes avant de les envoyer à une IA. 100 % local, conforme Loi 25 et RGPD.')
@section('og_type', $shareData['og_type'] ?? 'website')
@section('og_image', $shareData['og_image'] ?? '')
@section('share_text', $shareData['share_text'] ?? '')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('assets/tools/anonymiseur/styles.css') }}?v={{ config('version.semver') }}">
<link rel="manifest" href="{{ asset('assets/tools/anonymiseur/manifest.webmanifest') }}">
<meta name="theme-color" content="#0B7285">
<link rel="canonical" href="{{ url('/outils/anonymiseur') }}">
@vite('resources/js/tiptap-frontend.js')
@php
    $anonymizerJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Anonymiseur de texte',
        'alternateName' => ['Anonymisation de prompts', 'Pseudonymisation client-side', 'Anonymizer La veille de Stef'],
        'description' => "Outil 100 % local qui anonymise les données personnelles (noms, adresses, NAS, courriels, téléphones, dates, montants) avant l'envoi à une IA. Conforme Loi 25 (Québec) et RGPD. Aucune donnée ne quitte votre navigateur.",
        'applicationCategory' => ['SecurityApplication', 'BusinessApplication'],
        'operatingSystem' => 'Web',
        'url' => url('/outils/anonymiseur'),
        'isAccessibleForFree' => true,
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'CAD'],
        'featureList' => [
            'Détection automatique PII (regex + heuristique québécoise)',
            'Pseudonymisation réversible avec table de correspondance locale',
            'Export/Import JSON local (aucun stockage serveur)',
            'Restauration des réponses IA via mapping inverse',
            'Conforme Loi 25 + RGPD',
            'WCAG 2.2 AAA accessibilité'
        ],
        'softwareVersion' => '1.0',
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
                    <div class="card-body p-4 p-md-5">

                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->icon }} {{ $tool->name }}</h1>
                                <p class="text-muted mb-0">{{ __('Sécurisez vos données avant traitement par IA. 100 % local. Conforme Loi 25 et RGPD.') }}</p>
                            </div>
                            <div class="d-flex gap-1 align-items-center" style="flex-shrink:0;">
                                @include('tools::partials.fullscreen-btn')
                                @include('tools::partials.share-btn', ['tool' => $tool])
                                <div class="action-menu-wrapper" style="position:relative;">
                                    <button id="btnActionsMenu" type="button" class="ct-btn ct-btn-ghost ct-btn-xs action-menu-trigger" aria-label="{{ __('Menu d\'actions Import/Export') }}" aria-haspopup="true" title="{{ __('Plus d\'actions') }}" style="border-radius:50%;width:32px;height:32px;padding:0;line-height:32px;display:inline-flex;align-items:center;justify-content:center;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                    </button>
                                    <div id="actionsMenu" class="action-menu" role="menu">
                                        <button id="btnImport" type="button" class="action-menu-item" role="menuitem"><span>📥 {{ __('Importer JSON') }}</span></button>
                                        <button id="btnExport" type="button" class="action-menu-item" role="menuitem"><span>📤 {{ __('Exporter JSON') }}</span></button>
                                        <div class="action-menu-divider" role="separator"></div>
                                        <button id="btnClearAllRules" type="button" class="action-menu-item danger" role="menuitem"><span>🗑️ {{ __('Tout effacer les règles') }}</span></button>
                                    </div>
                                </div>
                                <input type="file" id="fileImport" accept=".json" class="hidden" aria-label="{{ __('Importer un fichier JSON de règles d\'anonymisation') }}">
                            </div>
                        </div>

<a href="#sourceText" class="skip-link visually-hidden-focusable">{{ __('Aller au contenu principal') }}</a>

<div class="app">
    {{-- Bouton fullpage masqué (remplacé par tools::partials.fullscreen-btn dans le header charte) --}}
    <button id="btnFullpage" type="button" class="hidden" aria-hidden="true" tabindex="-1"></button>

    <div id="infoBanner" class="lv-trust-banner" role="region" aria-label="{{ __('Garantie de confidentialité') }}">
        {{-- État réduit (badge cliquable après acceptation) --}}
        <button type="button" id="lvTrustToggle" class="lv-trust-toggle" aria-expanded="true" aria-controls="lvTrustContent">
            <span class="lv-trust-icon" aria-hidden="true">🛡️</span>
            <span class="lv-trust-title">
                <strong>{{ __('100 % local') }}</strong>
                <span class="lv-trust-sub">{{ __('Traitement dans votre navigateur — aucun contenu envoyé à un serveur') }}</span>
            </span>
            <span class="lv-trust-chevron" aria-hidden="true">▾</span>
        </button>

        {{-- Contenu détaillé (accordéon) --}}
        <div id="lvTrustContent" class="lv-trust-content">
            {{-- Schéma flux visuel --}}
            <div class="lv-trust-flow" aria-label="{{ __('Flux des données') }}">
                <span class="lv-trust-flow-step"><span aria-hidden="true">📝</span> {{ __('Votre texte') }}</span>
                <span class="lv-trust-flow-arrow" aria-hidden="true">→</span>
                <span class="lv-trust-flow-step lv-trust-flow-here"><span aria-hidden="true">🖥️</span> {{ __('Votre navigateur') }}</span>
                <span class="lv-trust-flow-arrow" aria-hidden="true">→</span>
                <span class="lv-trust-flow-step"><span aria-hidden="true">✋</span> {{ __('Vous décidez') }}</span>
                <span class="lv-trust-flow-arrow" aria-hidden="true">→</span>
                <span class="lv-trust-flow-step lv-trust-flow-external"><span aria-hidden="true">🤖</span> {{ __('IA externe (optionnel)') }}</span>
            </div>

            {{-- 4 sections par finalité (best practice Iapp/CNIL 2026) --}}
            <div class="lv-trust-grid">
                <div class="lv-trust-card">
                    <h4>✅ {{ __('Ce qui reste local') }}</h4>
                    <ul>
                        <li>{{ __('Votre texte source') }}</li>
                        <li>{{ __('Les règles d\'anonymisation') }}</li>
                        <li>{{ __('Les correspondances vraie ↔ fictive') }}</li>
                        <li>{{ __('Vos exports JSON') }}</li>
                    </ul>
                </div>
                <div class="lv-trust-card">
                    <h4>📤 {{ __('Ce qui peut sortir') }}</h4>
                    <ul>
                        <li>{{ __('Uniquement si VOUS cliquez « Copier »') }}</li>
                        <li>{{ __('Et le collez dans une IA externe') }}</li>
                        <li>{{ __('Le contenu copié est déjà anonymisé') }}</li>
                    </ul>
                </div>
                <div class="lv-trust-card">
                    <h4>🔇 {{ __('Quand on transmet') }}</h4>
                    <ul>
                        <li>{{ __('Jamais à l\'initiative de l\'outil') }}</li>
                        <li>{{ __('Pas d\'envoi auto ni de tracking') }}</li>
                        <li>{{ __('Pas de cookie d\'analyse sur cette page') }}</li>
                    </ul>
                </div>
                <div class="lv-trust-card">
                    <h4>⚙️ {{ __('Vos contrôles') }}</h4>
                    <ul>
                        <li>{{ __('Effacer à tout moment (bouton Effacer)') }}</li>
                        <li>{{ __('Exporter vos règles (JSON local)') }}</li>
                        <li>{{ __('Fonctionne hors-ligne') }}</li>
                        <li><a href="/glossaire/loi-25" target="_blank" rel="noopener">{{ __('Conforme Loi 25') }}</a> · <a href="/glossaire/rgpd" target="_blank" rel="noopener">{{ __('Conforme RGPD') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="lv-trust-actions">
                <button type="button" id="lvTrustAccept" class="lv-trust-accept">{{ __('J\'ai compris, ne plus afficher') }}</button>
                <a href="/glossaire/anonymisation" target="_blank" rel="noopener" class="lv-trust-learn-more">{{ __('Comprendre l\'anonymisation') }} →</a>
            </div>
        </div>
    </div>

    <div class="anonymiseur-presets" style="margin:1rem 0 1.5rem;">
        <h3 style="margin:0 0 0.35rem;font-size:1.05rem;font-weight:700;color:var(--text-primary);">
            🛡️ {{ __('Comment voulez-vous protéger vos données ?') }}
        </h3>
        <p style="margin:0 0 0.85rem;font-size:0.88rem;color:var(--text-secondary);line-height:1.5;">
            {{ __('Le mode « Standard » remplace par des faux noms similaires pour que l\'IA donne la meilleure réponse possible, puis restaure vos vraies données.') }}
        </p>
        <div class="preset-grid" role="radiogroup" aria-label="{{ __('Niveau de protection') }}">
            <button type="button" class="preset-card preset-card-recommended is-selected" data-preset="standard" role="radio" aria-checked="true">
                <h4>🔄 {{ __('Standard — IA') }}</h4>
                <p>{{ __('Le bon choix pour utiliser ChatGPT, Claude, Gemini.') }}</p>
                <ul>
                    <li>{{ __('Remplace par des faux noms SIMILAIRES (Marie → Catherine)') }}</li>
                    <li>{{ __('L\'IA comprend le contexte et donne sa meilleure réponse') }}</li>
                    <li>{{ __('On restaure vos vraies données dans la réponse') }}</li>
                </ul>
            </button>
            <button type="button" class="preset-card" data-preset="maximum" role="radio" aria-checked="false">
                <h4>🛡️ {{ __('Maximum — Sensible') }}</h4>
                <p>{{ __('Pour santé, légal, finance — sans retour possible.') }}</p>
                <ul>
                    <li>{{ __('Efface définitivement par [SUPPRIMÉ]') }}</li>
                    <li>{{ __('Export protégé par mot de passe') }}</li>
                    <li>{{ __('Impossible de retrouver les vraies données ensuite') }}</li>
                </ul>
            </button>
            <button type="button" class="preset-card" data-preset="custom" role="radio" aria-checked="false">
                <h4>⚙️ {{ __('Personnalisé') }}</h4>
                <p>{{ __('Pour utilisateurs avancés (techniques).') }}</p>
                <ul>
                    <li>{{ __('Choisir comment remplacer (4 modes)') }}</li>
                    <li>{{ __('Sensibilité de détection ajustable') }}</li>
                    <li>{{ __('Chiffrement optionnel des exports') }}</li>
                </ul>
            </button>
        </div>

        <div id="custom-settings" hidden style="margin-top:1rem;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);padding:1.15rem;">
            <h4 style="margin:0 0 0.85rem;font-size:0.95rem;font-weight:700;color:var(--text-primary);">{{ __('Réglages personnalisés') }}</h4>
            <div class="anonymiseur-custom-grid">
                <div class="anonymiseur-custom-field">
                    <label for="maskMode" class="anonymiseur-custom-label">
                        <span>{{ __('Comment remplacer vos données') }}</span>
                        <button type="button" class="ct-help-btn" data-help-key="anonym-modes" aria-label="{{ __('Aide : comprendre les 4 modes de remplacement') }}">ⓘ</button>
                    </label>
                    <div class="anonymiseur-custom-control">
                        <select id="maskMode" class="form-input">
                            <option value="pseudo">🔄 {{ __('Faux noms similaires (recommandé pour ChatGPT/Claude/Gemini)') }}</option>
                            <option value="redaction">🗑️ {{ __('Effacer définitivement [SUPPRIMÉ]') }}</option>
                            <option value="hash">🔒 {{ __('Code unique irréversible (hash SHA-256)') }}</option>
                            <option value="fpe">🎲 {{ __('Brouillage format identique (avancé)') }}</option>
                        </select>
                    </div>
                    <small class="anonymiseur-custom-hint">{{ __('Le but : que l\'IA comprenne votre contexte SANS voir vos vraies données. Vous pourrez les restaurer après.') }}</small>
                </div>

                <div class="anonymiseur-custom-field">
                    <label for="confidenceThreshold" class="anonymiseur-custom-label">
                        <span style="display:flex;align-items:center;gap:0.5rem;">
                            <span>{{ __('Sensibilité de détection') }}</span>
                            <button type="button" class="ct-help-btn" data-help-key="anonym-sensitivity" aria-label="{{ __('Aide : comprendre la sensibilité de détection') }}">ⓘ</button>
                        </span>
                        <span id="confidenceThresholdValue" class="anonymiseur-custom-value">60 %</span>
                    </label>
                    <div class="anonymiseur-custom-control">
                        <input type="range" id="confidenceThreshold" min="0" max="1" step="0.05" value="0.6" aria-describedby="confidenceHint">
                    </div>
                    <small id="confidenceHint" class="anonymiseur-custom-hint">{{ __('Plus haut = moins de fausses alertes') }}</small>
                </div>

                <div class="anonymiseur-custom-field">
                    <label for="encryptionEnabled" class="anonymiseur-custom-label" style="cursor:pointer;">
                        <span style="display:flex;align-items:center;gap:0.5rem;">
                            <input type="checkbox" id="encryptionEnabled" class="anonymiseur-custom-checkbox">
                            <span>{{ __('Protéger les exports') }}</span>
                        </span>
                        <button type="button" class="ct-help-btn" data-help-key="anonym-encryption" aria-label="{{ __('Aide : comprendre la protection des exports') }}">ⓘ</button>
                    </label>
                    <div class="anonymiseur-custom-control" id="encryptionPassphraseWrap">
                        <input type="password" id="encryptionPassphrase" class="form-input" placeholder="{{ __('Cochez d\'abord ☝️ pour activer') }}" autocomplete="new-password" disabled>
                    </div>
                    <small class="anonymiseur-custom-hint">{{ __('Chiffre votre fichier JSON exporté avec un mot de passe (AES-GCM). Vous seul·e pourrez l\'ouvrir.') }}</small>
                </div>
            </div>
        </div>
    </div>

    <nav class="steps" aria-label="Étapes du processus">
        <button class="step active" data-step="1" aria-current="step">
            <span class="step-number">1</span>
            <span class="step-label">Texte original</span>
        </button>
        <button class="step" data-step="2">
            <span class="step-number">2</span>
            <span class="step-label">Règles &amp; anonymisation</span>
        </button>
        <button class="step" data-step="3">
            <span class="step-number">3</span>
            <span class="step-label">Restauration IA</span>
        </button>
    </nav>

    <main class="main-content">
        <div class="step-content active" data-step-content="1">
            <div class="lv-anonym-help" style="background:#E6F7F5;border:1px solid rgba(11,114,133,0.2);border-radius:var(--radius);padding:0.85rem 1.15rem;margin-bottom:1rem;font-size:0.92rem;line-height:1.5;color:var(--text-primary);">
                💡 <strong>{{ __('Comment ça marche') }}</strong> : {{ __('collez votre texte ci-dessous, puis cliquez') }} <strong>{{ __('Détecter PII') }}</strong> {{ __('pour repérer automatiquement les données sensibles (noms, courriels, NAS…). Vous pouvez aussi sélectionner du texte à la souris puis cliquer') }} <strong>🕵️ {{ __('Anonymiser') }}</strong>. {{ __('Les vraies données restent sur votre appareil — rien n\'est envoyé à un serveur.') }}
            </div>
            <section class="panel editor-panel">
                <div class="panel-header">
                    <h2 class="panel-title">{{ __('Votre texte') }}</h2>
                    <div class="panel-actions">
                        <button id="btnClear" class="btn btn-secondary btn-sm" title="{{ __('Effacer tout le texte') }}">{{ __('Effacer') }}</button>
                        <button id="btnDetect" class="btn btn-primary btn-sm" title="{{ __('Repérer automatiquement les données personnelles') }}">🔍 {{ __('Détecter les données sensibles') }}</button>
                    </div>
                </div>
                <div class="panel-content">
                    <div id="tiptap-source" data-tiptap-anonymiseur="1" class="source-content tiptap-source-host" aria-label="{{ __('Zone de texte à anonymiser') }}"></div>
                    <div class="char-count">
                        <span id="charCount">0</span> {{ __('caractères') }}
                    </div>

                    {{-- Bubble menu Tiptap vanilla : Bold / Italic / Underline / Strike / Code / Highlight / Clear --}}
                    <div id="tiptap-bubble-menu" class="tiptap-bubble-menu" role="toolbar" aria-label="{{ __('Format de texte') }}">
                        <button type="button" data-action="anonymize" aria-label="{{ __('Anonymiser la sélection') }}" title="{{ __('Anonymiser cette sélection') }}">🕵️ {{ __('Anonymiser') }}</button>
                        <span class="tiptap-bubble-divider" aria-hidden="true"></span>
                        <button type="button" data-mark="bold" aria-label="{{ __('Gras') }}" title="{{ __('Gras (Ctrl+B)') }}"><strong>B</strong></button>
                        <button type="button" data-mark="italic" aria-label="{{ __('Italique') }}" title="{{ __('Italique (Ctrl+I)') }}"><em>I</em></button>
                        <button type="button" data-mark="underline" aria-label="{{ __('Souligné') }}" title="{{ __('Souligné (Ctrl+U)') }}"><u>U</u></button>
                        <button type="button" data-mark="strike" aria-label="{{ __('Barré') }}" title="{{ __('Barré') }}"><s>S</s></button>
                        <button type="button" data-mark="code" aria-label="{{ __('Code inline') }}" title="{{ __('Code') }}"><code>&lt;/&gt;</code></button>
                        <button type="button" data-mark="highlight" aria-label="{{ __('Surlignage') }}" title="{{ __('Surligner') }}">🖍️</button>
                        <span class="tiptap-bubble-divider" aria-hidden="true"></span>
                        <button type="button" data-action="clearmark" aria-label="{{ __('Effacer mise en forme') }}" title="{{ __('Effacer le formatage') }}">✕</button>
                    </div>
                </div>
                <div id="detectionsBar" class="detections-bar hidden">
                    <div class="panel-header">
                        <h3 class="panel-title">Détections automatiques</h3>
                        <button id="btnAddRule" class="btn btn-success btn-sm">+ Ajouter règle</button>
                    </div>
                    <div id="detectionsList" class="detections-list"></div>
                </div>
            </section>
        </div>

        <div class="step-content" data-step-content="2">
            <div class="category-grid-layout">
                <aside class="sidebar">
                    <div class="panel">
                        <div class="panel-header">
                            <h2 class="panel-title">Règles actives</h2>
                        </div>
                        <div class="stats-bar">
                            <span class="stat-item"><strong id="statRules">0</strong> règles</span>
                            <span class="stat-item"><strong id="statReplacements">0</strong> remplacements</span>
                        </div>
                        <div id="rulesList" class="rules-list">
                            <div class="empty-state">
                                <div class="empty-state-icon">📋</div>
                                <p>Aucune règle définie</p>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="panel">
                    <div class="result-tabs">
                        <button class="result-tab active" data-tab="output">Anonymisé</button>
                        <button class="result-tab" data-tab="restored">Aperçu restauration</button>
                    </div>
                    <div class="panel-content">
                        <div class="result-content active" data-tab-content="output">
                            <textarea id="anonymizedText" class="anonymized-output" readonly aria-label="Texte anonymisé"></textarea>
                            <div class="panel-actions">
                                <button id="btnCopy" class="btn btn-primary">Copier anonymisé</button>
                            </div>
                        </div>
                        <div class="result-content" data-tab-content="restored">
                            <textarea id="restoredText" class="anonymized-output" readonly aria-label="Aperçu du texte restauré"></textarea>
                            <div class="panel-actions">
                                <button id="btnCopyRestored" class="btn btn-secondary">Copier restauré</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="step-content" data-step-content="3">
            <section class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Réponse de l'IA</h2>
                </div>
                <div class="panel-content">
                    <textarea id="aiResponse" class="source-content" placeholder="Collez ici la réponse de l'IA contenant des termes anonymisés..." aria-label="Réponse de l'IA"></textarea>
                    <div class="panel-actions">
                        <button id="btnRestore" class="btn btn-primary">Restaurer les données originales</button>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div id="ruleModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle" class="panel-title">Ajouter une règle</h2>
                <button id="closeModal" class="btn-icon" aria-label="Fermer la modale" title="{{ __('Fermer') }}">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Catégorie</label>
                    <div class="category-grid">
                        <button class="category-btn active" data-category="identity">👤 Identité</button>
                        <button class="category-btn" data-category="contact">📞 Contact</button>
                        <button class="category-btn" data-category="location">📍 Lieu</button>
                        <button class="category-btn" data-category="id">🆔 ID</button>
                        <button class="category-btn" data-category="date">📅 Date</button>
                        <button class="category-btn" data-category="money">💰 Argent</button>
                        <button class="category-btn" data-category="other">✨ Autre</button>
                    </div>
                </div>

                <div id="genderGroup" class="form-group">
                    <label class="form-label">Genre (pour génération)</label>
                    <div class="form-row">
                        <button class="gender-btn active" data-gender="male">👨 Homme</button>
                        <button class="gender-btn" data-gender="female">👩 Femme</button>
                        <button class="gender-btn" data-gender="random">🎲 Aléatoire</button>
                    </div>
                </div>

                <div id="identityFields" class="identity-fields">
                    <p class="form-hint" style="margin-bottom:0.75rem;">{{ __('La vraie identité (gauche) sera remplacée par l\'identité fictive (droite) dans le texte envoyé à l\'IA. Vous pourrez restaurer les vraies données à l\'étape 3.') }}</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="inputFirstName" class="form-label">{{ __('Prénom réel') }}</label>
                            <input type="text" id="inputFirstName" class="form-input" placeholder="ex: Stéphane">
                        </div>
                        <div class="form-group">
                            <label for="inputLastName" class="form-label">{{ __('Nom réel') }}</label>
                            <input type="text" id="inputLastName" class="form-input" placeholder="ex: Lapointe">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="inputFakeFirstName" class="form-label">{{ __('Prénom fictif') }} <span style="color:var(--primary);font-weight:600;">↔</span></label>
                            <input type="text" id="inputFakeFirstName" class="form-input" placeholder="ex: Pierre">
                        </div>
                        <div class="form-group">
                            <label for="inputFakeLastName" class="form-label">{{ __('Nom fictif') }}</label>
                            <input type="text" id="inputFakeLastName" class="form-input" placeholder="ex: Tremblay">
                        </div>
                    </div>
                    <div class="form-row" style="align-items:center;gap:0.5rem;">
                        <button id="btnGenerateIdentity" class="btn btn-secondary btn-sm" title="{{ __('Générer automatiquement une identité fictive québécoise') }}">🎲 {{ __('Générer une identité fictive') }}</button>
                        <button id="btnRefreshVariant" class="btn btn-secondary btn-sm" aria-label="{{ __('Proposer une autre identité fictive') }}" title="{{ __('Proposer une autre suggestion') }}">🔄 {{ __('Autre suggestion') }}</button>
                    </div>
                </div>

                <div id="otherFields" class="other-fields hidden">
                    <div class="form-group">
                        <label id="labelOriginal" for="inputOriginal" class="form-label">Texte original</label>
                        <input type="text" id="inputOriginal" class="form-input" placeholder="Texte à masquer">
                        <span id="hintOriginal" class="form-hint"></span>
                    </div>
                    <div class="form-group">
                        <label id="labelReplacement" for="inputReplacement" class="form-label">Remplacement</label>
                        <div class="form-row">
                            <input type="text" id="inputReplacement" class="form-input" placeholder="ex: Ville_1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Suggestions</label>
                        <div id="variantsList" class="variants-list"></div>
                    </div>
                </div>

                <div class="exceptions-group">
                    <label for="inputExceptions" class="form-label">Exceptions (CSV)</label>
                    <input type="text" id="inputExceptions" class="form-input" placeholder="Royale, Banque Royale, Leroy...">
                    <span class="form-hint">Mots ou expressions à NE PAS remplacer même s'ils contiennent le texte original.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnCancelRule" class="btn btn-secondary">Annuler</button>
                <button id="btnSaveRule" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>

    {{-- Aides contextuelles migrées vers le composant officiel <x-core::help-modal> (S129 cohérence charte) : contenu dans window.HELP_CONTENT (@push scripts), boutons .ct-help-btn data-help-key. --}}

    <div id="confirmModal" class="modal-overlay confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmMessage">
        <div class="modal">
            <div class="modal-body text-center">
                <div id="confirmIcon" class="confirm-icon">❓</div>
                <div id="confirmMessage" class="confirm-message">Êtes-vous sûr ?</div>
            </div>
            <div class="modal-footer confirm-actions">
                <button id="confirmCancel" class="btn btn-secondary">Annuler</button>
                <button id="confirmOk" class="btn btn-danger">Confirmer</button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container" aria-live="polite" role="status"></div>
</div>

                    </div>{{-- /.card-body --}}
                </div>{{-- /.card --}}
            </div>{{-- /.col --}}
        </div>{{-- /.row --}}
    </div>{{-- /.container --}}
</section>
@endsection

@push('scripts')
@php
    // Aides contextuelles via composant officiel <x-core::help-modal> (window.HELP_CONTENT[key] = {title, body})
    $modesBody = <<<'HTML'
<div class="lv-help-tabs" role="tablist" aria-label="Modes de remplacement">
  <button type="button" role="tab" tabindex="0" class="lv-help-tab is-active" data-help-tab="pseudo"><span aria-hidden="true">🔄</span><span class="lv-help-tab-label">Faux noms</span><span class="lv-help-tab-badge">Reco</span></button>
  <button type="button" role="tab" tabindex="-1" class="lv-help-tab" data-help-tab="redaction"><span aria-hidden="true">🗑️</span><span class="lv-help-tab-label">Effacer</span></button>
  <button type="button" role="tab" tabindex="-1" class="lv-help-tab" data-help-tab="hash"><span aria-hidden="true">🔒</span><span class="lv-help-tab-label">Code unique</span></button>
  <button type="button" role="tab" tabindex="-1" class="lv-help-tab" data-help-tab="fpe"><span aria-hidden="true">🎲</span><span class="lv-help-tab-label">Brouillage</span></button>
</div>
<div class="lv-help-panel is-active" data-help-panel="pseudo">
  <h3 class="lv-help-panel-title">🔄 Faux noms similaires <span class="lv-help-panel-pill">Recommandé</span></h3>
  <dl class="lv-help-dl"><dt>Quand</dt><dd>vous voulez utiliser ChatGPT, Claude ou Gemini.</dd><dt>Pourquoi</dt><dd>l'IA comprend votre contexte (un nom, une adresse, une date) et donne sa meilleure réponse. Ensuite on remet vos vraies données dans la réponse.</dd><dt>Exemple</dt><dd>« Marie Tremblay habite à Montréal »<br>→ « <strong>Catherine Bouchard</strong> habite à <strong>Québec</strong> »</dd></dl>
  <p class="lv-help-reversible">✅ Restauration possible — vous récupérez vos vraies données après.</p>
</div>
<div class="lv-help-panel" data-help-panel="redaction" hidden>
  <h3 class="lv-help-panel-title">🗑️ Effacer définitivement</h3>
  <dl class="lv-help-dl"><dt>Quand</dt><dd>vous voulez partager un document public (rapport, étude, capture d'écran).</dd><dt>Pourquoi</dt><dd>aucun risque de fuite — les vraies données disparaissent pour toujours.</dd><dt>Exemple</dt><dd>« Marie Tremblay habite à Montréal »<br>→ « <strong>[SUPPRIMÉ]</strong> habite à <strong>[SUPPRIMÉ]</strong> »</dd></dl>
  <p class="lv-help-reversible lv-help-reversible--no">⛔ Aucune restauration possible.</p>
</div>
<div class="lv-help-panel" data-help-panel="hash" hidden>
  <h3 class="lv-help-panel-title">🔒 Code unique irréversible</h3>
  <dl class="lv-help-dl"><dt>Quand</dt><dd>vous voulez comparer ou dédoublonner sans connaître les vrais noms (statistiques internes, recherche).</dd><dt>Pourquoi</dt><dd>même nom = même code, donc on peut compter les occurrences sans jamais voir les vraies identités.</dd><dt>Exemple</dt><dd>« Marie Tremblay » → « <strong>a8f3c2e1</strong> »<br>« Marie Tremblay » (2<sup>e</sup> fois) → « <strong>a8f3c2e1</strong> »</dd></dl>
  <p class="lv-help-reversible lv-help-reversible--no">⛔ Aucune restauration possible.</p>
</div>
<div class="lv-help-panel" data-help-panel="fpe" hidden>
  <h3 class="lv-help-panel-title">🎲 Brouillage format identique <span class="lv-help-panel-pill lv-help-panel-pill--advanced">Avancé</span></h3>
  <dl class="lv-help-dl"><dt>Quand</dt><dd>vous avez un système (base de données, vieux logiciel) qui exige un format précis : 16 chiffres pour une carte bancaire, 9 chiffres pour un NAS.</dd><dt>Pourquoi</dt><dd>le système reste fonctionnel parce que le format est respecté, mais les vraies données sont protégées.</dd><dt>Exemple</dt><dd>« 4532-1234-5678-9876 »<br>→ « <strong>7831-9402-1456-2289</strong> » (toujours 16 chiffres)</dd></dl>
  <p class="lv-help-reversible">✅ Restauration avec clé technique.</p>
</div>
<p class="lv-help-cta">💡 <strong>Pas sûr·e ?</strong> Choisissez <strong>🔄 Faux noms similaires</strong> — c'est le mode qui marche le mieux avec ChatGPT, Claude et Gemini, et vous récupérez vos vraies données après.</p>
HTML;

    $sensitivityBody = <<<'HTML'
<p style="margin:0 0 1rem;">Ce réglage contrôle à quel point l'outil est strict pour repérer vos informations personnelles (noms, courriels, numéros…).</p>
<div class="lv-help-analogy"><h3>🔥 Comme un détecteur de fumée</h3><dl class="lv-help-dl"><dt>Très sensible</dt><dd>il sonne dès qu'il voit un peu de fumée — repère le maximum de données, mais parfois à tort (faux positifs).</dd><dt>Peu sensible</dt><dd>il sonne seulement s'il est sûr — moins de fausses alertes, mais il peut rater certaines données.</dd></dl></div>
<div class="lv-help-scale" aria-hidden="true"><span>← Détecte tout</span><span class="lv-help-scale-mid">Équilibré</span><span>Très précis →</span></div>
<p class="lv-help-cta">💡 <strong>Conseil :</strong> pour respecter la Loi 25 et le RGPD, gardez un niveau <strong>équilibré ou plus sensible</strong> — mieux vaut une fausse alerte que de laisser passer une vraie donnée personnelle.</p>
HTML;

    $encryptionBody = <<<'HTML'
<p style="margin:0 0 1rem;">Quand vous exportez vos règles dans un fichier (pour les réutiliser ou les partager), ce fichier peut contenir vos vraies données. Cette option le verrouille avec un mot de passe.</p>
<div class="lv-help-analogy"><h3>🔐 Comme un coffre-fort</h3><dl class="lv-help-dl"><dt>Sans protection</dt><dd>le fichier est lisible par quiconque l'ouvre (collègue, pièce jointe interceptée, clé USB perdue).</dd><dt>Avec protection</dt><dd>le fichier devient illisible sans le mot de passe. Même intercepté, personne ne peut rien en faire.</dd></dl></div>
<dl class="lv-help-dl" style="margin-top:1rem;"><dt>Quand</dt><dd>vous partagez le fichier par courriel, le stockez dans le nuage, ou sur un appareil partagé.</dd><dt>Comment</dt><dd>cochez la case, choisissez un mot de passe de 12 caractères ou plus, puis exportez. Gardez ce mot de passe — sans lui, le fichier est irrécupérable.</dd></dl>
<p class="lv-help-cta">🛡️ <strong>Technique :</strong> chiffrement AES-GCM 256 bits (standard bancaire), calculé dans votre navigateur. Le mot de passe ne quitte jamais votre appareil.</p>
HTML;

    $anonymHelp = [
        'anonym-modes' => ['title' => 'Les 4 façons de remplacer vos données', 'body' => $modesBody],
        'anonym-sensitivity' => ['title' => "La sensibilité de détection, c'est quoi ?", 'body' => $sensitivityBody],
        'anonym-encryption' => ['title' => "Protéger vos exports, c'est quoi ?", 'body' => $encryptionBody],
    ];
@endphp
<script>
    window.HELP_CONTENT = Object.assign({}, window.HELP_CONTENT || {}, {!! json_encode($anonymHelp, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
</script>
<script src="{{ asset('assets/tools/anonymiseur/app.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements-v145.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements-v146.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements-v148-presets.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements-v149-trust.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements-v150-help.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
