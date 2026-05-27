'use strict';

/**
 * Anonymiseur v1.46.2 — Presets sécurité ultra-simples (3 niveaux).
 * Mappe les boutons .preset-card vers les settings maskMode + encryption + confidence
 * (enhancements-v145.js). Affiche/masque #custom-settings selon le preset.
 * Best practices mai 2026 : presets nommés / langage clair / progressive disclosure.
 */
(function () {
    const PRESETS = {
        standard: { maskMode: 'pseudo', confidence: 0.6, encryption: false, showCustom: false },
        maximum: { maskMode: 'redaction', confidence: 0.5, encryption: true, showCustom: false },
        custom: { showCustom: true }
    };

    function applyPreset(name) {
        const preset = PRESETS[name];
        if (!preset) return;

        document.querySelectorAll('.preset-card').forEach(card => {
            const isSelected = card.getAttribute('data-preset') === name;
            card.classList.toggle('is-selected', isSelected);
            card.setAttribute('aria-checked', isSelected ? 'true' : 'false');
        });

        const customPanel = document.getElementById('custom-settings');
        if (customPanel) {
            if (preset.showCustom) customPanel.removeAttribute('hidden');
            else customPanel.setAttribute('hidden', '');
        }

        if (preset.maskMode !== undefined) {
            const select = document.getElementById('maskMode');
            if (select) {
                select.value = preset.maskMode;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        if (preset.confidence !== undefined) {
            const slider = document.getElementById('confidenceThreshold');
            if (slider) {
                slider.value = String(preset.confidence);
                slider.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
        if (preset.encryption !== undefined) {
            const cb = document.getElementById('encryptionEnabled');
            if (cb && cb.checked !== preset.encryption) {
                cb.checked = preset.encryption;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        try { localStorage.setItem('anonymiseur_preset_v1', name); } catch (e) { /* silent */ }

        if (typeof window.showToast === 'function' && name !== 'custom') {
            const labels = {
                standard: 'Mode IA activé : faux noms similaires pour meilleure réponse + restauration ensuite.',
                maximum: 'Mode Maximum : effacement définitif [SUPPRIMÉ]. Pas de restauration possible.'
            };
            window.showToast(labels[name] || name, 'info');
        }
    }

    function loadSavedPreset() {
        let saved = 'standard';
        try { saved = localStorage.getItem('anonymiseur_preset_v1') || 'standard'; } catch (e) {}
        applyPreset(saved);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.preset-card').forEach(card => {
            card.addEventListener('click', () => {
                applyPreset(card.getAttribute('data-preset'));
            });
            card.addEventListener('keydown', (e) => {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    applyPreset(card.getAttribute('data-preset'));
                }
            });
        });

        if (document.querySelector('.preset-card')) {
            loadSavedPreset();
        }
    });

    window.AnonymiseurPresets = { applyPreset, PRESETS };
})();
