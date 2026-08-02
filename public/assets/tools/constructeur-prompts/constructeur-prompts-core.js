/**
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * constructeur-prompts-core.js - logique Alpine.js du Constructeur de prompts (réécriture complète,
 * 2026-08-02, étape 4 de .outils/PLAN-CONSTRUCTEUR-PROMPTS-ULTRA-2026-08-02.md).
 *
 * Écran unique, deux états : (A) 9 cartes de sélection, (B) une fois une carte choisie, elle reste
 * un radio natif dans le DOM (repliée en pastille), la phrase à trous apparaît sur le MÊME écran.
 * Les 9 gabarits (skeleton + trous) sont fournis par Modules/Tools/resources/views/public/tools/
 * constructeur-prompts.blade.php (source de vérité unique, DRY : jamais dupliqués ici).
 *
 * Zéro appel réseau, zéro API IA tierce. Le vérificateur PII délègue à window.PromptVerifierRules
 * (prompt-verifier-rules.js, fichier séparé et réutilisable - voir son en-tête).
 */
document.addEventListener('alpine:init', function () {
    'use strict';

    // Rappel imparfait imposé (critère d'acceptation 3, plan section 3) - jamais un texte de
    // garantie/validation, jamais un état visuel « propre »/vert.
    var VERIFIER_CAPTION = 'Ce vérificateur repère les motifs évidents mais ne peut pas deviner un nom glissé dans votre texte - relisez avant de copier.';
    var VERIFIER_NOTHING = 'Rien de repéré automatiquement. Relisez : plusieurs formats ne sont pas détectés.';

    // Comportement de qualité PAR DÉFAUT, non désactivable en V1 (section 3 du plan) : typographie
    // française soignée et formulation naturelle, jamais un interrupteur visible. Absent du gabarit
    // Traduire (qualitySuffix:false côté Blade) - sa sortie n'est justement pas censée être en
    // français.
    var QUALITY_SUFFIX = 'Réponds avec une orthographe et une syntaxe françaises soignées, dans un style naturel, sans tournures robotiques.';

    var HISTORY_KEY = 'lv_cp_history_v1';
    var HISTORY_ENABLED_KEY = 'lv_cp_history_enabled_v1';
    var HISTORY_MAX_ITEMS = 20;
    var HISTORY_TTL_MS = 7 * 24 * 60 * 60 * 1000; // 7 jours (section 3 du plan, non négociable)

    // IA cibles du bouton « Ouvrir dans » - préremplissage par URL volontairement absent (fragile,
    // retiré côté Claude web ~octobre 2025, voir plan section 3) : le mécanisme principal reste
    // presse-papiers + collage manuel.
    var AI_TARGETS = [
        { key: 'chatgpt', label: 'ChatGPT', url: 'https://chatgpt.com/' },
        { key: 'claude', label: 'Claude', url: 'https://claude.ai/new' },
        { key: 'gemini', label: 'Gemini', url: 'https://gemini.google.com/app' },
        { key: 'perplexity', label: 'Perplexity', url: 'https://www.perplexity.ai/' }
    ];

    // Tous les noms de trous possibles (canoniques + spécifiques à une carte) - initialisés une
    // seule fois à '' pour que `values` soit un objet PARTAGÉ entre les 9 gabarits. C'est ce
    // partage, et lui seul, qui implémente la « migration » du contenu au changement de carte
    // (section 4 du plan) : aucune copie/recopie n'est nécessaire, la valeur est déjà là quand le
    // trou de même nom réapparaît sous une autre carte.
    var ALL_SLOTS = ['sujet', 'contexte', 'public', 'ton', 'longueur', 'format', 'niveau_correction', 'langue_cible', 'nombre', 'role'];

    Alpine.data('promptBuilder', function (gabarits, anonymizerUrl) {
        return {
            gabarits: gabarits || [],
            anonymizerUrl: anonymizerUrl || '/outils/anonymiseur',

            selectedCard: null,
            values: ALL_SLOTS.reduce(function (acc, slot) { acc[slot] = ''; return acc; }, {}),

            aiTargets: AI_TARGETS,
            targetAiKey: 'chatgpt',

            copyFeedback: false,
            copyError: false,
            openInstructionVisible: false,
            openFallbackUrl: '',
            openCopyFailed: false,

            notifyKept: false,
            flashSlots: {},

            historyEnabled: false,
            historyItems: [],

            init: function () {
                try {
                    this.historyEnabled = localStorage.getItem(HISTORY_ENABLED_KEY) === '1';
                } catch (e) { this.historyEnabled = false; }
                this.historyItems = this.loadHistoryRaw();
            },

            // ===== Gabarit actif =====

            currentTemplate: function () {
                var self = this;
                if (!this.selectedCard) return null;
                return this.gabarits.filter(function (g) { return g.key === self.selectedCard; })[0] || null;
            },

            currentAiLabel: function () {
                var self = this;
                var found = this.aiTargets.filter(function (a) { return a.key === self.targetAiKey; })[0];
                return found ? found.label : this.aiTargets[0].label;
            },

            // ===== Sélection de carte (état A -> état B, et retour) =====

            onCardSelected: function () {
                var self = this;
                var tpl = this.currentTemplate();
                this.notifyKept = !!(tpl && tpl.fields.some(function (f) { return (self.values[f.slot] || '').toString().trim() !== ''; }));
                if (this.notifyKept) {
                    setTimeout(function () { self.notifyKept = false; }, 4000);
                }
                this.openInstructionVisible = false;
                this.openFallbackUrl = '';
                this.$nextTick(function () {
                    if (!self.$refs.phraseArea) return;
                    // Auto-grandissement immédiat des textarea déjà remplies par migration.
                    self.$refs.phraseArea.querySelectorAll('.cp-slot__textarea').forEach(function (el) { self.autoGrow(el); });
                    // Focus + défilement (critère d'interaction du plan, section 4) : premier trou
                    // vide de la nouvelle carte, avec défilement automatique sur mobile.
                    var firstEmpty = self.$refs.phraseArea.querySelector('[data-cp-empty="true"]');
                    if (window.innerWidth < 768) {
                        self.$refs.phraseArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    if (firstEmpty && typeof firstEmpty.focus === 'function') {
                        firstEmpty.focus();
                    }
                });
            },

            // Revient à l'état A (grille des 9 cartes). Les 9 radios restent dans le DOM en tout
            // temps (jamais retirées) : on décoche seulement le groupe en vidant selectedCard.
            resetSelection: function () {
                this.selectedCard = null;
                this.notifyKept = false;
                this.openInstructionVisible = false;
                this.openFallbackUrl = '';
            },

            // ===== Champs de texte auto-extensibles (jamais contenteditable, section 4 du plan) =====

            autoGrow: function (el) {
                if (!el) return;
                if (window.CSS && CSS.supports && CSS.supports('field-sizing', 'content')) return; // natif, rien à faire
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 320) + 'px';
            },

            // ===== Construction de l'aperçu vivant =====

            renderSlotValue: function (field) {
                var raw = (this.values[field.slot] || '').toString().trim();
                if (field.type === 'select') {
                    if (!raw) return '';
                    var opt = (field.options || []).filter(function (o) { return o.value === raw; })[0];
                    return opt ? opt.label : '';
                }
                return raw;
            },

            // Clause de renfort optionnelle, propre à un choix précis d'un champ (ex. gabarit
            // Analyser : « liste de points forts et de points faibles », note d'implémentation du
            // journal de test à 27 cas - voir .outils/GABARITS-CONSTRUCTEUR-PROMPTS-2026-08-02.md).
            activeBoost: function () {
                var tpl = this.currentTemplate();
                if (!tpl) return '';
                for (var i = 0; i < tpl.fields.length; i++) {
                    var f = tpl.fields[i];
                    var v = this.values[f.slot];
                    if (f.boosts && v && f.boosts[v]) return f.boosts[v];
                }
                return '';
            },

            // Segments { type: 'literal'|'user'|'placeholder', text } pour l'aperçu coloré (ambre =
            // vos mots, teal = ajouté par l'outil) - voir aussi finalCleanText() pour la version
            // copiée/envoyée, qui retire les balises de remplacement.
            previewSegments: function () {
                var self = this;
                var tpl = this.currentTemplate();
                if (!tpl) return [];
                var fieldsBySlot = {};
                tpl.fields.forEach(function (f) { fieldsBySlot[f.slot] = f; });

                var segments = [];
                var re = /\{(\w+)\}/g;
                var lastIndex = 0;
                var m;
                while ((m = re.exec(tpl.skeleton)) !== null) {
                    if (m.index > lastIndex) {
                        segments.push({ type: 'literal', text: tpl.skeleton.slice(lastIndex, m.index) });
                    }
                    var field = fieldsBySlot[m[1]];
                    var val = field ? this.renderSlotValue(field) : '';
                    if (val) {
                        segments.push({ type: 'user', text: val });
                    } else {
                        segments.push({ type: 'placeholder', text: '[' + (field ? field.label.toLowerCase() : m[1]) + ']' });
                    }
                    lastIndex = re.lastIndex;
                }
                if (lastIndex < tpl.skeleton.length) {
                    segments.push({ type: 'literal', text: tpl.skeleton.slice(lastIndex) });
                }

                var boost = this.activeBoost();
                if (boost) segments.push({ type: 'literal', text: ' ' + boost });
                if (tpl.qualitySuffix !== false) {
                    segments.push({ type: 'literal', text: ' ' + QUALITY_SUFFIX });
                }
                return segments;
            },

            // Texte final « propre » (copié / envoyé) : les balises [trou] vides disparaissent,
            // jamais copiées littéralement (critère du plan, section 3) - un utilitaire de nettoyage
            // léger absorbe la ponctuation et les espaces laissés par un trou omis.
            finalCleanText: function () {
                var segs = this.previewSegments();
                var text = segs.map(function (s) { return s.type === 'placeholder' ? '' : s.text; }).join('');
                return this.cleanupText(text);
            },

            cleanupText: function (s) {
                return s
                    .replace(/[ \t]+/g, ' ')
                    .replace(/ +([,.;:!?])/g, '$1')
                    .replace(/([,.;:!?])\1+/g, '$1')
                    .replace(/,\s*\./g, '.')
                    .replace(/:\s*\./g, '.')
                    .replace(/\s*\n\s*/g, '\n')
                    .trim();
            },

            // ===== Vérificateur déterministe local (règles simples, zéro appel IA) =====

            verifierSummary: function () {
                var self = this;
                var tpl = this.currentTemplate();
                if (!tpl) return null;
                var textBlob = tpl.fields.map(function (f) { return self.values[f.slot] || ''; }).join('\n');
                var entities = (window.PromptVerifierRules && window.PromptVerifierRules.detect(textBlob)) || [];
                var emptySlots = tpl.fields
                    .filter(function (f) { return (self.values[f.slot] || '').toString().trim() === ''; })
                    .map(function (f) { return f.label; });
                return { entities: entities, emptySlots: emptySlots };
            },

            summarizeEntities: function (entities) {
                if (!entities.length) return '';
                var counts = {};
                var order = [];
                entities.forEach(function (e) {
                    if (!(e.label in counts)) { counts[e.label] = 0; order.push(e.label); }
                    counts[e.label] += 1;
                });
                var parts = order.map(function (label) { return counts[label] + ' × ' + label.toLowerCase(); });
                return parts.join(', ') + ' repéré' + (entities.length > 1 ? 's' : '') + '.';
            },

            verifierResultText: function () {
                var summary = this.verifierSummary();
                if (!summary) return '';
                var parts = [];
                parts.push(summary.entities.length ? this.summarizeEntities(summary.entities) : VERIFIER_NOTHING);
                if (summary.emptySlots.length) {
                    parts.push('Trous non remplis : ' + summary.emptySlots.join(', ') + '.');
                }
                return parts.join(' ');
            },

            verifierCaption: function () { return VERIFIER_CAPTION; },

            // Fait clignoter brièvement en ambre les trous restés vides, sans jamais bloquer la
            // copie (plan section 3, ajout round 5 Gemini/Codex).
            flashEmptySlots: function () {
                var self = this;
                var tpl = this.currentTemplate();
                if (!tpl) return;
                var next = {};
                tpl.fields.forEach(function (f) {
                    if ((self.values[f.slot] || '').toString().trim() === '') next[f.slot] = true;
                });
                this.flashSlots = next;
                setTimeout(function () { self.flashSlots = {}; }, 1200);
            },

            // ===== Copier / Ouvrir dans =====

            // Le geste presse-papiers doit rester SYNCHRONE dans le gestionnaire de clic (exigence
            // Safari iOS, plan section 5) - aucun await avant cet appel. `writeText()` retourne une
            // promesse : un simple try/catch autour de l'appel NE capte PAS un rejet (permission
            // refusée, contexte non securise) - `copyFeedback` affichait donc "Copie !" meme sur un
            // echec silencieux (trouve par la passe adversariale round 9). Le .then()/.catch() sur la
            // promesse deja lancee ne retarde rien : l'appel lui-meme reste synchrone.
            copyPrompt: function () {
                var self = this;
                var text = this.finalCleanText();
                this.copyFeedback = false;
                this.copyError = false;
                try {
                    navigator.clipboard.writeText(text).then(function () {
                        self.copyFeedback = true;
                        setTimeout(function () { self.copyFeedback = false; }, 2500);
                    }, function () {
                        self.copyError = true;
                        setTimeout(function () { self.copyError = false; }, 3500);
                    });
                } catch (e) {
                    this.copyError = true;
                    setTimeout(function () { self.copyError = false; }, 3500);
                }
                this.flashEmptySlots();
                this.recordHistory('copy', null);
            },

            // Ordre technique EXACT et strict imposé par le plan (section 5, triple convergence
            // Codex/claude.ai/Gemini) : (1) onglet ouvert en PREMIER, de façon synchrone, avant tout
            // await ; (2) copie presse-papiers ; (3) réassignation de l'onglet déjà ouvert. Inverser
            // cet ordre perd le geste utilisateur et se fait bloquer par Safari iOS.
            openInAI: function () {
                var self = this;
                var newTab = window.open('about:blank', '_blank', 'noopener'); // (1) synchrone, avant tout
                var text = this.finalCleanText();
                // (2) best-effort, mais on ne ment pas sur le resultat : voir copyPrompt() pour
                // l'explication du .then()/.catch() sur la promesse (le try/catch seul ne capte
                // pas un rejet, trouve par la passe adversariale round 9).
                try {
                    navigator.clipboard.writeText(text).then(function () {
                        self.openCopyFailed = false;
                    }, function () {
                        self.openCopyFailed = true;
                    });
                } catch (e) { this.openCopyFailed = true; }

                var target = this.aiTargets.filter(function (a) { return a.key === this.targetAiKey; }, this)[0] || this.aiTargets[0];

                if (newTab) {
                    try { newTab.location.href = target.url; } catch (e) { /* (3) réassignation best-effort */ }
                    this.openInstructionVisible = true;
                    this.openFallbackUrl = '';
                } else {
                    // Bloqué par le navigateur : lien cliquable de secours plutôt qu'un échec silencieux.
                    this.openInstructionVisible = false;
                    this.openFallbackUrl = target.url;
                }
                this.flashEmptySlots();
                this.recordHistory('open', target.label);
            },

            // ===== Pont vers l'anonymiseur (sessionStorage, sans perte de texte) =====

            goToAnonymizer: function () {
                var self = this;
                var tpl = this.currentTemplate();
                try {
                    if (tpl) {
                        var text = tpl.fields
                            .filter(function (f) { return f.type === 'text' || f.type === 'textarea'; })
                            .map(function (f) { return (self.values[f.slot] || '').toString().trim(); })
                            .filter(Boolean)
                            .join('\n\n');
                        if (text) sessionStorage.setItem('lv_cp_handoff_task_text', text);
                    }
                } catch (e) { /* sessionStorage indisponible : on continue quand même vers l'anonymiseur */ }
                window.location.href = this.anonymizerUrl;
            },

            // ===== Historique local (localStorage, désactivé par défaut, expiration 7 jours) =====
            // Squelette fonctionnel minimal pour cette étape - l'implémentation détaillée (dédoublonnage
            // fin, présentation) est raffinée à l'étape 6 de la refonte.

            toggleHistoryEnabled: function () {
                try { localStorage.setItem(HISTORY_ENABLED_KEY, this.historyEnabled ? '1' : '0'); } catch (e) { /* ignore */ }
            },

            clearHistory: function () {
                var self = this;
                window.dispatchEvent(new CustomEvent('open-confirm-global', {
                    detail: {
                        message: 'Effacer tout l\'historique local de cet appareil ? Cette action est irréversible.',
                        callback: function () {
                            try { localStorage.removeItem(HISTORY_KEY); } catch (e) { /* ignore */ }
                            self.historyItems = [];
                            window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: 'Historique effacé.', variant: 'info', duration: 2500 } }));
                        }
                    }
                }));
            },

            loadHistoryRaw: function () {
                try {
                    var raw = localStorage.getItem(HISTORY_KEY);
                    var items = raw ? JSON.parse(raw) : [];
                    var now = Date.now();
                    return items.filter(function (it) { return it && typeof it.ts === 'number' && (now - it.ts) < HISTORY_TTL_MS; });
                } catch (e) { return []; }
            },

            isNearDuplicate: function (a, b) {
                var norm = function (s) { return (s || '').toLowerCase().replace(/\s+/g, ' ').trim(); };
                return norm(a) === norm(b);
            },

            // Déclenché SEULEMENT sur clic réussi Copier/Ouvrir (jamais à chaque frappe, plan
            // section 3).
            recordHistory: function (action, aiLabel) {
                if (!this.historyEnabled) return;
                var tpl = this.currentTemplate();
                if (!tpl) return;
                var text = this.finalCleanText();
                if (!text) return;
                try {
                    var items = this.loadHistoryRaw();
                    var now = Date.now();
                    var entry = { ts: now, card: tpl.label, text: text, action: action, ai: aiLabel || null };
                    if (items.length && this.isNearDuplicate(items[0].text, text)) {
                        items[0] = entry;
                    } else {
                        items.unshift(entry);
                    }
                    items = items.slice(0, HISTORY_MAX_ITEMS);
                    localStorage.setItem(HISTORY_KEY, JSON.stringify(items));
                    this.historyItems = items;
                } catch (e) { /* localStorage indisponible : silencieux, ne bloque jamais copier/ouvrir */ }
            }
        };
    });
});
