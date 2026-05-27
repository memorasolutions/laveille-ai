'use strict';

/**
 * Anonymiseur v1.45.0 P3 — score confiance + 4 modes masquage + AES-GCM Web Crypto.
 * Additif non-régressif chargé APRÈS app.js et enhancements.js.
 */
(function () {
    const STORAGE_KEY = 'anonymiseur_rules_v2';
    const SETTINGS_KEY = 'anonymiseur_settings_v1';

    function loadSettings() {
        try { return JSON.parse(localStorage.getItem(SETTINGS_KEY)) || {}; } catch (e) { return {}; }
    }
    function saveSettings(s) {
        try { localStorage.setItem(SETTINGS_KEY, JSON.stringify(s)); } catch (e) {}
    }
    let settings = Object.assign({
        confidenceThreshold: 0.6,
        maskMode: 'pseudo',
        encryptionEnabled: false,
        fpeSeed: 'memora-fpe-v1'
    }, loadSettings());

    async function sha256Hex(text) {
        const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
        return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('').substring(0, 12);
    }

    async function fpeMask(text, category) {
        const seed = (settings.fpeSeed || 'memora-fpe-v1') + ':' + category;
        const key = await crypto.subtle.importKey('raw', new TextEncoder().encode(seed), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
        const sig = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(text));
        const bytes = new Uint8Array(sig);
        let out = '';
        for (let i = 0; i < text.length; i++) {
            const c = text[i];
            const b = bytes[i % bytes.length];
            if (/[A-Z]/.test(c)) out += String.fromCharCode(65 + (b % 26));
            else if (/[a-z]/.test(c)) out += String.fromCharCode(97 + (b % 26));
            else if (/\d/.test(c)) out += String.fromCharCode(48 + (b % 10));
            else out += c;
        }
        return out;
    }

    async function applyMask(text, category) {
        switch (settings.maskMode) {
            case 'hash': return '[' + (await sha256Hex(text + ':' + category)).toUpperCase() + ']';
            case 'redaction': return '[REDACTED-' + String(category || 'X').toUpperCase() + ']';
            case 'fpe': return await fpeMask(text, category || 'default');
            case 'pseudo':
            default: return null;
        }
    }

    async function deriveKey(passphrase, salt) {
        const baseKey = await crypto.subtle.importKey('raw', new TextEncoder().encode(passphrase), 'PBKDF2', false, ['deriveKey']);
        return crypto.subtle.deriveKey(
            { name: 'PBKDF2', salt, iterations: 100000, hash: 'SHA-256' },
            baseKey,
            { name: 'AES-GCM', length: 256 },
            false,
            ['encrypt', 'decrypt']
        );
    }

    async function encryptPayload(plaintext, passphrase) {
        const salt = crypto.getRandomValues(new Uint8Array(16));
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const key = await deriveKey(passphrase, salt);
        const ct = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, new TextEncoder().encode(plaintext));
        return {
            v: 'aes-gcm-256-pbkdf2-100k',
            salt: btoa(String.fromCharCode(...salt)),
            iv: btoa(String.fromCharCode(...iv)),
            ct: btoa(String.fromCharCode(...new Uint8Array(ct)))
        };
    }

    async function decryptPayload(blob, passphrase) {
        const salt = Uint8Array.from(atob(blob.salt), c => c.charCodeAt(0));
        const iv = Uint8Array.from(atob(blob.iv), c => c.charCodeAt(0));
        const ct = Uint8Array.from(atob(blob.ct), c => c.charCodeAt(0));
        const key = await deriveKey(passphrase, salt);
        const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, ct);
        return new TextDecoder().decode(pt);
    }

    function shouldKeepDetection(badge) {
        const conf = parseFloat(badge.getAttribute('data-confidence-score') || '1');
        return conf >= settings.confidenceThreshold;
    }
    function applyConfidenceFilter() {
        document.querySelectorAll('.detection-badge').forEach(badge => {
            if (shouldKeepDetection(badge)) {
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const slider = document.getElementById('confidenceThreshold');
        const sliderLabel = document.getElementById('confidenceThresholdValue');
        if (slider) {
            slider.value = settings.confidenceThreshold;
            if (sliderLabel) sliderLabel.textContent = Math.round(settings.confidenceThreshold * 100) + ' %';
            slider.addEventListener('input', (e) => {
                settings.confidenceThreshold = parseFloat(e.target.value);
                if (sliderLabel) sliderLabel.textContent = Math.round(settings.confidenceThreshold * 100) + ' %';
                applyConfidenceFilter();
                saveSettings(settings);
            });
        }

        const maskSelect = document.getElementById('maskMode');
        if (maskSelect) {
            maskSelect.value = settings.maskMode;
            maskSelect.addEventListener('change', (e) => {
                settings.maskMode = e.target.value;
                saveSettings(settings);
                if (typeof window.showToast === 'function') {
                    const labels = { pseudo: 'Pseudonymisation', hash: 'Hash SHA-256', redaction: '[REDACTED]', fpe: 'FPE format-préservant' };
                    window.showToast('Mode masquage : ' + (labels[settings.maskMode] || settings.maskMode) + '. Ré-anonymisez pour appliquer.', 'info');
                }
            });
        }

        const encryptToggle = document.getElementById('encryptionEnabled');
        const passphraseInput = document.getElementById('encryptionPassphrase');
        if (encryptToggle) {
            encryptToggle.checked = settings.encryptionEnabled;
            encryptToggle.addEventListener('change', (e) => {
                settings.encryptionEnabled = e.target.checked;
                if (passphraseInput) passphraseInput.disabled = !e.target.checked;
                saveSettings(settings);
            });
            if (passphraseInput) passphraseInput.disabled = !settings.encryptionEnabled;
        }

        const btnExport = document.getElementById('btnExport');
        if (btnExport) {
            btnExport.addEventListener('click', async (e) => {
                if (!settings.encryptionEnabled || !passphraseInput || !passphraseInput.value) return;
                e.stopImmediatePropagation();
                e.preventDefault();
                try {
                    const rules = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
                    const blob = await encryptPayload(JSON.stringify({ version: '2.0-aes', rules }), passphraseInput.value);
                    const json = JSON.stringify(blob, null, 2);
                    const file = new Blob([json], { type: 'application/json' });
                    const url = URL.createObjectURL(file);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'anonymisation_encrypted_' + new Date().toISOString().substring(0, 10) + '.aes.json';
                    a.click();
                    URL.revokeObjectURL(url);
                    if (window.showToast) window.showToast('Export chiffré AES-GCM réussi.', 'success');
                } catch (err) {
                    if (window.showToast) window.showToast('Erreur chiffrement : ' + err.message, 'error');
                }
            }, true);
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/assets/tools/anonymiseur/sw.js', { scope: '/outils/anonymiseur' })
                .catch(() => { /* silent — pas critique */ });
        }
    });

    window.AnonymiseurV145 = { settings, saveSettings, sha256Hex, fpeMask, applyMask, encryptPayload, decryptPayload, applyConfidenceFilter };
})();
