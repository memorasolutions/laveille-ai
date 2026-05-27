'use strict';

/**
 * Anonymiseur — quick wins sécurité v1.44.1 (P2 #313)
 * Additif non-régressif chargé APRÈS app.js. Pattern : zéro modif source.
 * Apporte : Luhn CB + checksum NAS canadien + round-trip check + audit log
 * + contextual scoring keywords. Aucune PII jamais loguée (action/type only).
 */
(function () {
    const AUDIT_KEY = 'anonymiseur_audit_v1';
    const MAX_AUDIT_ENTRIES = 200;

    function luhnCheck(num) {
        const digits = String(num).replace(/\D/g, '');
        if (digits.length < 13 || digits.length > 19) return false;
        let sum = 0;
        let alternate = false;
        for (let i = digits.length - 1; i >= 0; i--) {
            let n = parseInt(digits[i], 10);
            if (alternate) {
                n *= 2;
                if (n > 9) n -= 9;
            }
            sum += n;
            alternate = !alternate;
        }
        return sum % 10 === 0;
    }

    function nasCanadianValid(nas) {
        const digits = String(nas).replace(/\D/g, '');
        if (digits.length !== 9) return false;
        return luhnCheck(digits);
    }

    const CONTEXT_KEYWORDS = {
        id: ['nas', 'numéro d\'assurance', 'social', 'sin', 'carte', 'cb', 'visa', 'mastercard', 'crédit', 'crd', 'dossier'],
        identity: ['patient', 'client', 'employé', 'utilisateur', 'titulaire', 'demandeur', 'monsieur', 'madame'],
        contact: ['courriel', 'email', 'tél', 'téléphone', 'contact', 'joindre'],
        location: ['adresse', 'demeurant', 'rue', 'avenue', 'boulevard', 'app', 'apt', 'code postal']
    };

    function contextScore(text, matchStart, matchEnd, categoryKey) {
        const keywords = CONTEXT_KEYWORDS[categoryKey];
        if (!keywords) return 0;
        const windowSize = 60;
        const before = text.substring(Math.max(0, matchStart - windowSize), matchStart).toLowerCase();
        const after = text.substring(matchEnd, Math.min(text.length, matchEnd + windowSize)).toLowerCase();
        const context = before + ' ' + after;
        return keywords.some(kw => context.includes(kw)) ? 0.15 : 0;
    }

    function auditLog(action, type) {
        try {
            const log = JSON.parse(localStorage.getItem(AUDIT_KEY) || '[]');
            log.push({ ts: new Date().toISOString(), action: String(action || ''), type: String(type || '') });
            if (log.length > MAX_AUDIT_ENTRIES) {
                log.splice(0, log.length - MAX_AUDIT_ENTRIES);
            }
            localStorage.setItem(AUDIT_KEY, JSON.stringify(log));
        } catch (e) { /* silent — quotaExceeded etc. */ }
    }

    function roundTripCheck() {
        const sourceText = document.getElementById('sourceText');
        const anonymized = document.getElementById('anonymizedText');
        const restored = document.getElementById('restoredText');
        if (!sourceText || !anonymized || !restored) return { ok: true };
        const original = sourceText.innerText || sourceText.textContent || '';
        const anonymText = anonymized.value || '';
        const restoredText = restored.value || '';
        if (!original.trim() || !anonymText.trim()) return { ok: true };
        const normOriginal = original.replace(/\s+/g, ' ').trim();
        const normRestored = restoredText.replace(/\s+/g, ' ').trim();
        const ok = normOriginal === normRestored;
        return { ok, originalLen: normOriginal.length, restoredLen: normRestored.length };
    }

    function postFilterDetections() {
        const badges = document.querySelectorAll('.detection-badge');
        badges.forEach((badge) => {
            const text = badge.getAttribute('data-text') || badge.textContent.trim();
            const category = badge.getAttribute('data-category');
            if (category === 'id' && /\b\d{3}[-\s]?\d{3}[-\s]?\d{3}\b/.test(text) && !nasCanadianValid(text)) {
                badge.style.opacity = '0.4';
                badge.title = 'NAS non valide (checksum mod-10 échoué) — faux positif probable';
                badge.setAttribute('data-confidence', 'low');
                return;
            }
            if (category === 'id' && /\b(?:\d{4}[-\s]?){3}\d{4}\b/.test(text) && !luhnCheck(text)) {
                badge.style.opacity = '0.4';
                badge.title = 'Carte bancaire invalide (Luhn échoué) — faux positif probable';
                badge.setAttribute('data-confidence', 'low');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const btnDetect = document.getElementById('btnDetect');
        if (btnDetect) {
            btnDetect.addEventListener('click', () => {
                setTimeout(() => {
                    postFilterDetections();
                    auditLog('detect', 'pii');
                }, 150);
            });
        }

        const btnCopy = document.getElementById('btnCopy');
        if (btnCopy) {
            btnCopy.addEventListener('click', () => {
                const check = roundTripCheck();
                if (!check.ok && check.originalLen > 0 && typeof window.showToast === 'function') {
                    window.showToast(
                        '⚠️ Round-trip incohérent : restauration diffère de l\'original (' +
                        check.originalLen + ' vs ' + check.restoredLen +
                        ' caractères). Vérifiez vos règles avant envoi IA.',
                        'warning'
                    );
                }
                auditLog('copy_anonymized', 'output');
            });
        }

        const btnExport = document.getElementById('btnExport');
        if (btnExport) btnExport.addEventListener('click', () => auditLog('export', 'rules'));
        const btnImport = document.getElementById('btnImport');
        if (btnImport) btnImport.addEventListener('click', () => auditLog('import', 'rules'));
        const btnRestore = document.getElementById('btnRestore');
        if (btnRestore) btnRestore.addEventListener('click', () => auditLog('restore', 'ai_response'));
    });

    window.AnonymiseurEnhancements = { luhnCheck, nasCanadianValid, contextScore, auditLog, roundTripCheck };
})();
