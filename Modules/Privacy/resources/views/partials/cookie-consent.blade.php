{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@php
    $categories = config('privacy.categories', []);
    $scripts = config('privacy.scripts', []);
    $cookieName = config('privacy.consent.cookie_name', 'consent_v1');
    $policyVersion = config('privacy.documents.privacy_policy.version', '1.0');
    $policyUrl = config('privacy.documents.privacy_policy.url', '/privacy-policy');
    $locale = app()->getLocale();
    $isFr = str_starts_with($locale, 'fr');
    $hasCookie = false; // Toujours false côté serveur — le JS gère l'état via le cookie client

    $text = [
        'title' => $isFr ? 'Paramètres de confidentialité' : 'Privacy Settings',
        'intro' => $isFr
            ? 'Nous utilisons des témoins (cookies) pour assurer le bon fonctionnement du site, analyser le trafic et personnaliser le contenu. Vous pouvez gérer vos préférences ci-dessous.'
            : 'We use cookies to ensure the site works properly, analyze traffic, and personalize content. You can manage your preferences below.',
        'accept_all' => $isFr ? 'Tout accepter' : 'Accept all',
        'refuse_all' => $isFr ? 'Tout refuser' : 'Refuse all',
        'customize' => $isFr ? 'Personnaliser' : 'Customize',
        'save' => $isFr ? 'Enregistrer mes choix' : 'Save preferences',
        'back' => $isFr ? 'Retour' : 'Back',
        'policy_link' => $isFr ? 'Politique de confidentialité' : 'Privacy policy',
        'fab_label' => $isFr ? 'Gérer les témoins' : 'Manage cookies',
    ];
@endphp

<style>
    :root {
        --cc-bg: #ffffff;
        --cc-text: #1f2937;
        --cc-btn-primary: #0B7285;
        --cc-btn-primary-text: #ffffff;
        --cc-btn-secondary: #ECEEF2;
        --cc-btn-secondary-text: #1A1D23;
        --cc-border: #D5D9E0;
        --cc-overlay: rgba(0, 0, 0, 0.5);
        --cc-toggle-bg: #B8BDC9;
        --cc-toggle-active: #0B7285;
    }
    @media (prefers-color-scheme: dark) {
        :root {
            --cc-bg: #1A1D23;
            --cc-text: #F6F7F9;
            --cc-btn-primary: #0B7285;
            --cc-btn-primary-text: #ffffff;
            --cc-btn-secondary: #2D3039;
            --cc-btn-secondary-text: #F6F7F9;
            --cc-border: #3F4451;
            --cc-toggle-bg: #555B6A;
        }
    }
    #cc-host { font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; }
    .cc-backdrop { position: fixed; inset: 0; background: var(--cc-overlay); z-index: 9998; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
    .cc-backdrop.cc-open { opacity: 1; pointer-events: auto; }
    .cc-modal { position: fixed; bottom: 0; left: 0; right: 0; background: var(--cc-bg); color: var(--cc-text); z-index: 9999; padding: 1.5rem; box-shadow: 0 -4px 6px -1px rgba(0,0,0,0.1); transform: translateY(100%); transition: transform 0.3s ease-out; max-height: 90vh; overflow-y: auto; border-top: 1px solid var(--cc-border); }
    .cc-modal.cc-open { transform: translateY(0); }
    .cc-title { font-size: 1.25rem; font-weight: 700; margin: 0 0 0.5rem 0; }
    .cc-text { font-size: 0.9rem; margin: 0 0 1rem 0; opacity: 0.9; }
    .cc-link { color: inherit; text-decoration: underline; }
    .cc-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
    .cc-btn { flex: 1; min-width: 0; padding: 0.6rem 1rem; border-radius: 0.375rem; font-weight: 600; font-size: 0.9rem; cursor: pointer; border: none; text-align: center; transition: opacity 0.2s; white-space: nowrap; }
    .cc-btn:hover { opacity: 0.85; }
    .cc-btn:focus-visible { outline: 2px solid #3b82f6; outline-offset: 2px; }
    .cc-btn-primary { background: var(--cc-btn-primary); color: var(--cc-btn-primary-text); }
    .cc-btn-secondary { background: var(--cc-btn-secondary); color: var(--cc-btn-secondary-text); }
    .cc-details { display: none; margin-top: 1rem; border-top: 1px solid var(--cc-border); padding-top: 1rem; }
    .cc-details.cc-show { display: block; }
    .cc-switch-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding: 0.5rem 0; }
    .cc-switch-label { display: flex; flex-direction: column; }
    .cc-cat-name { font-weight: 600; font-size: 0.95rem; }
    .cc-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; margin-left: 1rem; }
    .cc-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
    .cc-slider { position: absolute; cursor: pointer; inset: 0; background-color: var(--cc-toggle-bg); transition: 0.3s; border-radius: 24px; }
    .cc-slider::before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; }
    input:checked + .cc-slider { background-color: var(--cc-toggle-active); }
    input:checked + .cc-slider::before { transform: translateX(20px); }
    input:disabled + .cc-slider { opacity: 0.5; cursor: not-allowed; }
    .cc-fab { position: fixed; bottom: 20px; left: 20px; width: 48px; height: 48px; border-radius: 50%; background: var(--cc-btn-primary); color: var(--cc-btn-primary-text); border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.15); cursor: pointer; z-index: 9990; display: flex; align-items: center; justify-content: center; transition: transform 0.2s; }
    .cc-fab:hover { transform: scale(1.1); }
    .cc-fab:focus-visible { outline: 2px solid #3b82f6; outline-offset: 2px; }
    .cc-fab svg { width: 22px; height: 22px; fill: currentColor; }
    .cc-hidden { display: none !important; }
    @media (max-width: 640px) {
        .cc-actions { flex-direction: column; }
        .cc-btn { width: 100%; }
        .cc-modal { padding: 1rem; }
    }
</style>

<div id="cc-host">
    {{-- FAB (visible seulement si consentement deja donne) --}}
    <button id="cc-fab" class="cc-fab {{ $hasCookie ? '' : 'cc-hidden' }}" type="button" aria-label="{{ $text['fab_label'] }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.95 10.99c-1.79-.03-3.7-1.95-2.68-4.22-2.97 1-5.78-1.59-5.19-4.56C7.11.74 2 6.41 2 12c0 5.52 4.48 10 10 10 5.89 0 10.54-5.08 9.95-11.01zM8.5 15c-.83 0-1.5-.67-1.5-1.5S7.67 12 8.5 12s1.5.67 1.5 1.5S9.33 15 8.5 15zm2-5C9.67 10 9 9.33 9 8.5S9.67 7 10.5 7s1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm4.5 6c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/></svg>
    </button>

    {{-- Backdrop --}}
    <div id="cc-backdrop" class="cc-backdrop {{ $hasCookie ? '' : 'cc-open' }}"></div>

    {{-- Banniere modale --}}
    <div id="cc-modal" class="cc-modal {{ $hasCookie ? '' : 'cc-open' }}" role="dialog" aria-modal="true" aria-labelledby="cc-title" aria-describedby="cc-desc">
        <h2 id="cc-title" class="cc-title">{{ $text['title'] }}</h2>
        <p id="cc-desc" class="cc-text">
            {{ $text['intro'] }}
            <a href="{{ $policyUrl }}" class="cc-link">{{ $text['policy_link'] }}</a>
        </p>

        {{-- Actions principales --}}
        <div id="cc-main-actions" class="cc-actions">
            <button type="button" id="cc-btn-refuse" class="cc-btn cc-btn-secondary">{{ $text['refuse_all'] }}</button>
            <button type="button" id="cc-btn-customize" class="cc-btn cc-btn-secondary">{{ $text['customize'] }}</button>
            <button type="button" id="cc-btn-accept" class="cc-btn cc-btn-primary">{{ $text['accept_all'] }}</button>
        </div>

        {{-- Panneau details (categories + toggles) --}}
        <div id="cc-details" class="cc-details">
            <div class="cc-actions" style="margin-bottom: 1rem;">
                <button type="button" id="cc-btn-back" class="cc-btn cc-btn-secondary">{{ $text['back'] }}</button>
            </div>
            <form id="cc-form">
                @foreach($categories as $key => $cat)
                    <div class="cc-switch-row">
                        <div class="cc-switch-label">
                            <span class="cc-cat-name">{{ $isFr ? ($cat['label_fr'] ?? $key) : ($cat['label_en'] ?? $key) }}</span>
                        </div>
                        <label class="cc-switch">
                            <input type="checkbox" name="{{ $key }}"
                                value="1"
                                {{ ($cat['required'] ?? false) ? 'checked disabled' : '' }}
                                aria-label="{{ $isFr ? ($cat['label_fr'] ?? $key) : ($cat['label_en'] ?? $key) }}">
                            <span class="cc-slider"></span>
                        </label>
                    </div>
                @endforeach
                <div class="cc-actions" style="margin-top: 1rem;">
                    <button type="button" id="cc-btn-save" class="cc-btn cc-btn-primary">{{ $text['save'] }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var expirationMap = @json(config('privacy.consent.expiration'));
    var detectedJurisdiction = (document.querySelector('meta[name="privacy-jurisdiction"]') || {}).content || 'pipeda';

    var config = {
        cookieName: @json($cookieName),
        policyVersion: @json($policyVersion),
        jurisdiction: detectedJurisdiction,
        apiUrl: '/api/privacy/consent',
        scripts: @json($scripts),
        categoryKeys: @json(array_keys($categories)),
        expirationDays: expirationMap[detectedJurisdiction] || 365
    };

    var els = {
        modal: document.getElementById('cc-modal'),
        backdrop: document.getElementById('cc-backdrop'),
        fab: document.getElementById('cc-fab'),
        details: document.getElementById('cc-details'),
        mainActions: document.getElementById('cc-main-actions'),
        form: document.getElementById('cc-form'),
        btnAccept: document.getElementById('cc-btn-accept'),
        btnRefuse: document.getElementById('cc-btn-refuse'),
        btnCustomize: document.getElementById('cc-btn-customize'),
        btnSave: document.getElementById('cc-btn-save'),
        btnBack: document.getElementById('cc-btn-back')
    };

    // --- Cookie helpers ---
    function getCookie(name) {
        var m = document.cookie.match('(^|; ?)' + name + '=([^;]*)(;|$)');
        return m ? decodeURIComponent(m[2]) : null;
    }

    function setCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + days * 86400000);
        document.cookie = name + '=' + encodeURIComponent(value) + ';path=/;expires=' + d.toUTCString() + ';SameSite=Lax';
    }

    // --- Read choices from cookie (handles server format {token,choices,v} or plain choices) ---
    function readChoicesFromCookie() {
        var raw = getCookie(config.cookieName);
        if (!raw) return null;
        try {
            var parsed = JSON.parse(raw);
            return parsed.choices || parsed;
        } catch(e) { return null; }
    }

    // --- UI ---
    function openBanner() {
        els.modal.classList.add('cc-open');
        els.backdrop.classList.add('cc-open');
        els.fab.classList.add('cc-hidden');
        els.details.classList.remove('cc-show');
        els.mainActions.classList.remove('cc-hidden');
        initFocusTrap();
    }

    function closeBanner() {
        els.modal.classList.remove('cc-open');
        els.backdrop.classList.remove('cc-open');
        els.fab.classList.remove('cc-hidden');
    }

    function showDetails() {
        els.mainActions.classList.add('cc-hidden');
        els.details.classList.add('cc-show');
        // GPC : desactiver marketing/third_party si signal actif
        if (navigator.globalPrivacyControl) {
            var inputs = els.form.querySelectorAll('input[type="checkbox"]:not([disabled])');
            for (var i = 0; i < inputs.length; i++) { inputs[i].checked = false; }
        }
        els.btnBack.focus();
    }

    function showMain() {
        els.details.classList.remove('cc-show');
        els.mainActions.classList.remove('cc-hidden');
        els.btnCustomize.focus();
    }

    // --- Focus trap (WCAG) ---
    function initFocusTrap() {
        els.modal.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (els.details.classList.contains('cc-show')) { showMain(); }
                else if (getCookie(config.cookieName)) { closeBanner(); }
                return;
            }
            if (e.key !== 'Tab') return;
            var focusable = els.modal.querySelectorAll('button:not(.cc-hidden), a[href], input:not([disabled])');
            var visible = [];
            for (var i = 0; i < focusable.length; i++) {
                if (focusable[i].offsetParent !== null) visible.push(focusable[i]);
            }
            if (!visible.length) return;
            var first = visible[0], last = visible[visible.length - 1];
            if (e.shiftKey && document.activeElement === first) { last.focus(); e.preventDefault(); }
            else if (!e.shiftKey && document.activeElement === last) { first.focus(); e.preventDefault(); }
        });
        var firstBtn = els.modal.querySelector('button:not(.cc-hidden)');
        if (firstBtn) firstBtn.focus();
    }

    // --- Inject third-party scripts ---
    function injectScripts(choices) {
        if (!config.scripts) return;
        for (var i = 0; i < config.scripts.length; i++) {
            var sc = config.scripts[i];
            if (!sc.enabled || !choices[sc.category]) continue;
            var hash = 'cc-' + sc.category + '-' + i;
            if (document.getElementById(hash)) continue;
            var div = document.createElement('div');
            div.id = hash;
            div.innerHTML = sc.code;
            // Move scripts to body for execution
            var scripts = div.querySelectorAll('script');
            for (var j = 0; j < scripts.length; j++) {
                var ns = document.createElement('script');
                if (scripts[j].src) { ns.src = scripts[j].src; ns.async = true; }
                else { ns.textContent = scripts[j].textContent; }
                document.body.appendChild(ns);
            }
        }
    }

    // --- Save consent ---
    function saveConsent(choices) {
        // 1. Cookie local (feedback immediat)
        setCookie(config.cookieName, JSON.stringify({choices: choices, v: config.policyVersion, expiresAt: Date.now() + (config.expirationDays * 86400000)}), config.expirationDays);

        // 2. API (preuve de consentement)
        var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;
        fetch(config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf || ''
            },
            body: JSON.stringify({
                choices: choices,
                jurisdiction: config.jurisdiction,
                policy_version: config.policyVersion
            })
        }).catch(function() {});

        // 3. Scripts tiers + fermer
        injectScripts(choices);
        closeBanner();
    }

    // --- Build choices object ---
    function buildAllChoices(value) {
        var choices = {};
        for (var i = 0; i < config.categoryKeys.length; i++) {
            var k = config.categoryKeys[i];
            choices[k] = (k === 'essential') ? true : !!value;
        }
        return choices;
    }

    function buildFormChoices() {
        var choices = {};
        var inputs = els.form.querySelectorAll('input[type="checkbox"]');
        for (var i = 0; i < inputs.length; i++) {
            choices[inputs[i].name] = inputs[i].checked;
        }
        choices.essential = true;
        return choices;
    }

    // --- Event handlers ---
    els.btnAccept.onclick = function() { saveConsent(buildAllChoices(true)); };
    els.btnRefuse.onclick = function() { saveConsent(buildAllChoices(false)); };
    els.btnCustomize.onclick = showDetails;
    els.btnBack.onclick = showMain;
    els.btnSave.onclick = function() { saveConsent(buildFormChoices()); };
    els.fab.onclick = openBanner;

    // --- Init : charger choix existants ---
    var raw = readChoicesFromCookie();
    var needsReprompt = false;

    if (raw) {
        // Verifier version de la politique
        if (raw.v && raw.v !== config.policyVersion) {
            needsReprompt = true;
        }
        // Verifier expiration du consentement
        if (raw.expiresAt && raw.expiresAt <= Date.now()) {
            needsReprompt = true;
        }

        var existing = raw.choices || raw;

        if (!needsReprompt) {
            // Cookie valide : fermer le banner et montrer le FAB
            closeBanner();
            // Pre-remplir les toggles
            for (var k in existing) {
                var inp = els.form.querySelector('input[name="' + k + '"]');
                if (inp && !inp.disabled) inp.checked = !!existing[k];
            }
            injectScripts(existing);
        } else {
            // Consentement expire ou politique mise a jour : re-prompt
            setTimeout(function() { openBanner(); }, 100);
        }
    } else {
        // Pas de cookie : focus trap sur la banniere
        setTimeout(function() { initFocusTrap(); }, 100);
    }
})();
</script>
