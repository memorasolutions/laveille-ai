@extends('fronttheme::layouts.master')

@section('title', '🕵️ Anonymiseur de texte — La veille de Stef')
@section('meta_description', 'Anonymisez vos textes avant de les envoyer à une IA. 100 % local, conforme Loi 25 et RGPD.')

@push('head')
<link rel="stylesheet" href="{{ asset('assets/tools/anonymiseur/styles.css') }}?v={{ config('version.semver') }}">
<link rel="manifest" href="{{ asset('assets/tools/anonymiseur/manifest.webmanifest') }}">
<meta name="theme-color" content="#0B7285">
<meta property="og:title" content="🕵️ Anonymiseur de texte — La veille de Stef">
<meta property="og:description" content="Anonymisez vos textes localement avant de les envoyer à une IA. Conforme Loi 25 et RGPD.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/outils/anonymiseur') }}">
<link rel="canonical" href="{{ url('/outils/anonymiseur') }}">
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
<a href="#sourceText" class="skip-link">Aller au contenu principal</a>

<div class="app">
    <header class="header">
        <div class="header-left">
            <h1 class="panel-title">🕵️ Anonymiseur de texte</h1>
            <p class="form-hint">Sécurisez vos données avant traitement par IA · 100 % local</p>
        </div>
        <div class="header-actions">
            <button id="btnFullpage" class="btn btn-icon" aria-label="Mode plein écran" title="Plein écran">
                <span class="action-icon">⛶</span>
            </button>
            <div class="action-menu-wrapper">
                <button id="btnActionsMenu" class="btn btn-icon action-menu-trigger" aria-label="Menu d'actions" aria-haspopup="true">
                    <span class="action-icon">⋮</span>
                </button>
                <div id="actionsMenu" class="action-menu">
                    <button id="btnImport" class="action-menu-item">
                        <span>📥 Importer JSON</span>
                    </button>
                    <button id="btnExport" class="action-menu-item">
                        <span>📤 Exporter JSON</span>
                    </button>
                    <div class="action-menu-divider"></div>
                    <button id="btnClearAllRules" class="action-menu-item danger">
                        <span>🗑️ Tout effacer les règles</span>
                    </button>
                </div>
            </div>
            <input type="file" id="fileImport" accept=".json" class="hidden" aria-label="Importer un fichier JSON de règles d'anonymisation">
        </div>
    </header>

    <div id="infoBanner" class="info-banner" role="region" aria-label="Information confidentialité">
        <span class="info-message">🛡️ <strong>100 % local</strong> — Aucune donnée ne quitte votre appareil. Conforme <a href="/glossaire/loi-25" target="_blank" rel="noopener" style="color:#064E5C;font-weight:700;text-decoration:underline;padding:2px 6px;border-radius:4px;">Loi 25</a> et <a href="/glossaire/rgpd" target="_blank" rel="noopener" style="color:#064E5C;font-weight:700;text-decoration:underline;padding:2px 6px;border-radius:4px;">RGPD</a>.</span>
        <button id="closeBanner" class="btn-icon" aria-label="Fermer la bannière" style="min-width:44px;min-height:44px;">×</button>
    </div>

    <details class="anonymiseur-settings" style="margin:1rem 0;padding:0;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);">
        <summary style="padding:0.85rem 1.25rem;cursor:pointer;font-weight:600;color:var(--text-primary);user-select:none;min-height:44px;display:flex;align-items:center;gap:0.5rem;">
            <span aria-hidden="true">⚙️</span>
            <span>Réglages avancés (mode masquage, score confiance, chiffrement)</span>
        </summary>
        <div style="padding:1rem 1.25rem;display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));">
            <div>
                <label for="maskMode" style="display:block;font-weight:600;color:var(--text-primary);margin-bottom:0.35rem;">Mode de masquage</label>
                <select id="maskMode" class="form-input" style="min-height:44px;width:100%;">
                    <option value="pseudo">Pseudonymisation (réversible, default)</option>
                    <option value="hash">Hash SHA-256 (irréversible)</option>
                    <option value="redaction">Redaction [REDACTED] (irréversible)</option>
                    <option value="fpe">FPE format-préservant (déterministe)</option>
                </select>
                <small style="color:var(--text-secondary);display:block;margin-top:0.35rem;">Pseudo = anonymisation Loi 25 conformité. Hash/Redaction/FPE = vraie anonymisation irréversible.</small>
            </div>
            <div>
                <label for="confidenceThreshold" style="display:block;font-weight:600;color:var(--text-primary);margin-bottom:0.35rem;">Seuil de confiance détection : <span id="confidenceThresholdValue" style="color:var(--primary);font-weight:700;">60 %</span></label>
                <input type="range" id="confidenceThreshold" min="0" max="1" step="0.05" value="0.6" style="width:100%;min-height:44px;" aria-describedby="confidenceHint">
                <small id="confidenceHint" style="color:var(--text-secondary);display:block;margin-top:0.35rem;">Masque les détections sous le seuil (réduit faux positifs).</small>
            </div>
            <div>
                <label style="display:flex;align-items:center;gap:0.5rem;font-weight:600;color:var(--text-primary);min-height:44px;">
                    <input type="checkbox" id="encryptionEnabled" style="min-width:24px;min-height:24px;cursor:pointer;">
                    <span>Chiffrer exports JSON (AES-GCM 256)</span>
                </label>
                <input type="password" id="encryptionPassphrase" class="form-input" placeholder="Passphrase (12+ caractères)" style="min-height:44px;width:100%;margin-top:0.35rem;" autocomplete="new-password" disabled>
                <small style="color:var(--text-secondary);display:block;margin-top:0.35rem;">Web Crypto natif zéro dépendance. PBKDF2 100k itérations.</small>
            </div>
        </div>
    </details>

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
            <section class="panel editor-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Source</h2>
                    <div class="panel-actions">
                        <button id="btnClear" class="btn btn-secondary btn-sm">Effacer</button>
                        <button id="btnDetect" class="btn btn-primary btn-sm">Détecter PII</button>
                    </div>
                </div>
                <div class="panel-content">
                    <div id="sourceText" class="source-content" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Coller votre texte original ici" data-placeholder="Collez votre texte ici pour commencer..."></div>
                    <div class="char-count">
                        <span id="charCount">0</span> caractères
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

    <div id="ruleModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" class="panel-title">Ajouter une règle</h2>
                <button id="closeModal" class="btn-icon" aria-label="Fermer la modale">×</button>
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
                    <div class="form-row">
                        <div class="form-group">
                            <label for="inputFirstName" class="form-label">Prénom original</label>
                            <input type="text" id="inputFirstName" class="form-input" placeholder="ex: Stéphane">
                        </div>
                        <div class="form-group">
                            <label for="inputLastName" class="form-label">Nom original</label>
                            <input type="text" id="inputLastName" class="form-input" placeholder="ex: Lapointe">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="inputFakeFirstName" class="form-label">Prénom fictif</label>
                            <input type="text" id="inputFakeFirstName" class="form-input" placeholder="ex: Pierre">
                        </div>
                        <div class="form-group">
                            <label for="inputFakeLastName" class="form-label">Nom fictif</label>
                            <input type="text" id="inputFakeLastName" class="form-input" placeholder="ex: Tremblay">
                        </div>
                    </div>
                    <div class="form-row">
                        <button id="btnGenerateIdentity" class="btn btn-secondary btn-sm">🎲 Générer identité fictive</button>
                        <button id="btnRefreshVariant" class="btn btn-icon btn-sm" aria-label="Nouvelle variante">🔄</button>
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

    <div id="confirmModal" class="modal confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmMessage">
        <div class="modal-content">
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
@endsection

@push('scripts')
<script src="{{ asset('assets/tools/anonymiseur/app.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements-v145.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/enhancements-v146.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
