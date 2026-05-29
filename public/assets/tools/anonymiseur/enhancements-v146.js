'use strict';

/**
 * Anonymiseur v1.45.1 P4 — NLP léger via compromise.js lazy-load.
 * Additif non-régressif. Charge compromise (~250 KB minified) seulement
 * au premier click sur btnDetect. Combine extraction #People + #Place +
 * #Organization avec les regex existantes pour réduire ~80 % des faux
 * positifs properName (devis cycle 3 #3, note 90/100).
 * Fallback gracieux : si CDN inaccessible, regex seules continuent.
 */
(function () {
    // Auto-hébergé sur laveille.ai (plus de CDN externe) : aucune connexion tierce, fonctionne hors-ligne (PWA). compromise 14.14.0.
    const COMPROMISE_CDN = '/assets/tools/anonymiseur/compromise.min.js?v=14.14.0';
    let compromiseLoaded = false;
    let compromiseLoading = null;
    let nlp = null;

    function loadCompromise() {
        if (compromiseLoaded) return Promise.resolve(nlp);
        if (compromiseLoading) return compromiseLoading;
        compromiseLoading = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = COMPROMISE_CDN;
            script.async = true;
            script.defer = true;
            script.onload = () => {
                nlp = window.nlp || null;
                compromiseLoaded = !!nlp;
                if (compromiseLoaded) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('NLP léger compromise.js chargé (réduit faux positifs).', 'info');
                    }
                    resolve(nlp);
                } else {
                    reject(new Error('compromise non disponible après chargement'));
                }
            };
            script.onerror = () => reject(new Error('Échec chargement compromise CDN'));
            document.head.appendChild(script);
        });
        return compromiseLoading;
    }

    function extractEntities(text) {
        if (!nlp || !text) return { people: [], places: [], organizations: [] };
        try {
            const doc = nlp(text);
            return {
                people: doc.people().out('array') || [],
                places: doc.places().out('array') || [],
                organizations: doc.organizations().out('array') || []
            };
        } catch (e) {
            return { people: [], places: [], organizations: [] };
        }
    }

    function nerBoostBadges(text) {
        const entities = extractEntities(text);
        if (!entities.people.length && !entities.places.length && !entities.organizations.length) return;
        const allEntities = new Set([
            ...entities.people.map(s => s.toLowerCase()),
            ...entities.places.map(s => s.toLowerCase()),
            ...entities.organizations.map(s => s.toLowerCase())
        ]);
        document.querySelectorAll('.detection-badge').forEach(badge => {
            const text = (badge.getAttribute('data-text') || badge.textContent || '').toLowerCase().trim();
            const category = badge.getAttribute('data-category');
            if (category === 'identity' || category === 'other') {
                let matched = false;
                for (const ent of allEntities) {
                    if (text.includes(ent) || ent.includes(text)) { matched = true; break; }
                }
                if (matched) {
                    badge.setAttribute('data-confidence-score', '0.92');
                    badge.style.boxShadow = '0 0 0 2px #064E5C';
                    badge.title = 'NER confirmé (compromise) — confiance 92 %';
                } else {
                    const current = parseFloat(badge.getAttribute('data-confidence-score') || '0.4');
                    badge.setAttribute('data-confidence-score', Math.min(current, 0.4).toString());
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const btnDetect = document.getElementById('btnDetect');
        if (!btnDetect) return;
        btnDetect.addEventListener('click', () => {
            loadCompromise().then(() => {
                const sourceText = document.getElementById('sourceText');
                const text = sourceText ? (sourceText.innerText || sourceText.textContent || '') : '';
                if (text.trim().length > 0) {
                    setTimeout(() => nerBoostBadges(text), 250);
                }
            }).catch(() => { /* CDN unreachable — regex-only continues */ });
        }, { once: false });
    });

    window.AnonymiseurNLP = { loadCompromise, extractEntities, nerBoostBadges };
})();
