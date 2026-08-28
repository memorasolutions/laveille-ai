/**
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Prompteur — éditeur de script vidéo structuré (méthode BYOA : Bring Your Own AI) +
 * téléprompteur plein écran. Alpine.js pur, aucune dépendance externe, aucun appel réseau.
 *
 * 3 briques :
 *  1) Générateur de méta-prompt (onglet 1) : l'utilisateur copie le prompt dans SA propre IA.
 *  2) Import robuste de la réponse de l'IA (onglet 2) : parseur tolérant en cascade, jamais
 *     de plantage, toujours une porte de sortie manuelle (édition libre des sections).
 *  3) Téléprompteur (onglet 3) : défilement précis via deadline absolue (performance.now())
 *     + requestAnimationFrame, même pattern de précision que minuteur-visuel-core.js.
 *
 * Sécurité : le contenu importé (potentiellement généré par une IA externe non fiable)
 * n'est JAMAIS injecté via innerHTML — uniquement x-text/x-model côté Blade (vérifié : aucun
 * x-html dans prompteur.blade.php). Aucun alert()/confirm()/prompt() natif nulle part ; les
 * confirmations (nouveau projet, gabarit qui remplace un projet non vide) passent par un petit
 * encart injecté en DOM (rôle alertdialog, boutons réels, fermeture Échap/clic extérieur).
 */
(function () {
    'use strict';

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function debounce(fn, wait) {
        var t = null;
        return function () {
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(null, args); }, wait);
        };
    }

    // Ordre des onglets — utilisé par la navigation clavier (flèches/Home/End) et par la
    // mise en pause automatique du téléprompteur quand on quitte son onglet.
    var TAB_ORDER = ['byoa', 'edit', 'teleprompter'];

    // Marqueurs du bloc JSON attendu en fin de réponse IA (voir generatePrompt()).
    var JSON_MARKER_START = 'LAVEILLE_SCRIPT_JSON_V1_START';
    var JSON_MARKER_END = 'LAVEILLE_SCRIPT_JSON_V1_END';

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('prompteurApp', function () {
            return {
                // ═══════════════════════ État racine (contrat DOM) ═══════════════════════
                activeTab: 'byoa',
                reducedMotion: false,
                compactView: false,
                highContrast: false,
                textSize: 'moyen',
                themePreference: 'systeme',
                settingsOpen: false,

                // --- BYOA (onglet 1) ---
                form: {
                    goal: '',
                    audience: '',
                    duration: '2-3',
                    durationCustom: '',
                    tone: 'professionnel'
                },
                generatedPrompt: '',
                promptCopied: false,

                // --- Import / édition (onglet 2) ---
                importRawText: '',
                importStatusMessage: '',
                sections: [],
                title: '',

                // --- Téléprompteur (onglet 3) ---
                teleprompterState: 'idle', // idle | running | paused
                scrollSpeed: 4, // niveau 1-10 (slider Blade), converti en mots/min — voir _scrollSpeedToWpm()
                readingFontSizeRem: 1.8,
                teleprompterHighContrast: false,
                mirrorMode: false,
                voiceScrollActive: false,
                teleprompterProgress: 0,
                teleprompterTimeRemainingLabel: '',
                teleprompterAriaMessage: '',
                countdownValue: 0,

                // --- Interne (pas dans le contrat DOM) ---
                _pendingDeleteId: null,
                _pendingDeleteAt: 0,
                _pendingDeleteTimer: null,
                _copyTimer: null,
                _countdownTimer: null,
                _readingRafId: null,
                _readingStartTime: 0,
                _pausedElapsedMs: 0,
                _lastAnnouncedMinuteTp: null,
                _voiceRecognition: null,
                _voiceWordsRecognized: 0,
                _voiceTotalWords: 0,
                _settingsTriggerEl: null,
                _settingsTabHandler: null,

                // Gabarits de départ — clé -> fonction générant des sections réalistes en FR.
                // mode: 'exact' | 'outline' (valeurs des radios de la vue, PAS 'verbatim' —
                // 'verbatim' est le mot utilisé dans le méta-prompt envoyé à l'IA externe ;
                // il est normalisé en 'exact' à l'import, voir _normalizeParsedScript()).
                _templateBuilders: {
                    tutoriel: function () {
                        return [
                            {
                                title: 'Accroche et contexte',
                                durationEstimate: '20 s',
                                visual: 'Plan face caméra, logo du logiciel affiché en incrustation.',
                                mode: 'exact',
                                script: "Aujourd'hui, on configure [nom de la fonctionnalité] ensemble, étape par étape. Si vous suivez ces 5 minutes, vous n'aurez plus jamais à chercher comment faire."
                            },
                            {
                                title: 'Étape 1 – Où trouver le bon menu',
                                durationEstimate: '30 s',
                                visual: "Capture d'écran du logiciel, curseur qui pointe vers le menu concerné.",
                                mode: 'outline',
                                script: 'Montrer où se trouve le menu. Nommer chaque option visible à voix haute.'
                            },
                            {
                                title: 'Étape 2 – Configurer le premier réglage',
                                durationEstimate: '40 s',
                                visual: 'Zoom sur le champ à remplir, clic bien visible.',
                                mode: 'outline',
                                script: "Expliquer ce que fait ce réglage et donner un exemple concret pour l'auditoire visé."
                            },
                            {
                                title: 'Étape 3 – Valider et vérifier le résultat',
                                durationEstimate: '30 s',
                                visual: 'Clic sur Enregistrer, puis capture du résultat final à l\'écran.',
                                mode: 'outline',
                                script: 'Montrer que ça fonctionne. Signaler le piège le plus fréquent à cette étape.'
                            },
                            {
                                title: 'Récapitulatif et appel à l\'action',
                                durationEstimate: '20 s',
                                visual: 'Retour plan face caméra, texte à l\'écran avec les 3 étapes résumées.',
                                mode: 'exact',
                                script: "Voilà, c'est tout ce qu'il fallait faire. Si ça vous a aidé, gardez cette vidéo sous la main – et à bientôt pour la suite !"
                            }
                        ];
                    },
                    actualites: function () {
                        return [
                            {
                                title: 'Intro – Accroche du jour',
                                durationEstimate: '15 s',
                                visual: 'Plan face caméra, habillage graphique de la capsule à l\'écran.',
                                mode: 'exact',
                                script: 'Bonjour à tous ! Voici les nouvelles qui font jaser cette semaine, ici au Québec.'
                            },
                            {
                                title: 'Actu 1',
                                durationEstimate: '45 s',
                                visual: 'Capture d\'écran ou photo liée à la première actualité.',
                                mode: 'outline',
                                script: 'Résumer le fait principal, dire pourquoi ça concerne l\'auditoire, donner une source.'
                            },
                            {
                                title: 'Actu 2',
                                durationEstimate: '45 s',
                                visual: 'Capture d\'écran ou photo liée à la deuxième actualité.',
                                mode: 'outline',
                                script: 'Même structure : le fait, l\'impact concret, une source vérifiable.'
                            },
                            {
                                title: 'Conclusion et infolettre',
                                durationEstimate: '20 s',
                                visual: 'Retour plan face caméra, lien de l\'infolettre affiché à l\'écran.',
                                mode: 'exact',
                                script: "C'était le tour de la semaine. Abonnez-vous à l'infolettre pour ne rien manquer, et on se revoit bientôt !"
                            }
                        ];
                    },
                    formation: function () {
                        return [
                            {
                                title: 'Objectifs d\'apprentissage',
                                durationEstimate: '30 s',
                                visual: 'Diapositive avec les 2-3 objectifs de la capsule, listés clairement.',
                                mode: 'exact',
                                script: "À la fin de cette capsule, vous serez capables de [objectif 1], [objectif 2] et [objectif 3]."
                            },
                            {
                                title: 'Partie 1 – Notion de base',
                                durationEstimate: '1 min',
                                visual: 'Schéma ou exemple à l\'écran illustrant la notion.',
                                mode: 'outline',
                                script: 'Expliquer la notion avec un exemple concret et familier pour le public visé.'
                            },
                            {
                                title: 'Partie 2 – Mise en pratique',
                                durationEstimate: '1 min',
                                visual: 'Démonstration ou cas pratique filmé/animé à l\'écran.',
                                mode: 'outline',
                                script: 'Montrer comment appliquer la notion, insister sur l\'erreur la plus fréquente.'
                            },
                            {
                                title: 'Synthèse et évaluation',
                                durationEstimate: '30 s',
                                visual: 'Diapositive récapitulative + question ou mini-quiz à l\'écran.',
                                mode: 'exact',
                                script: "En résumé, retenez ces points-clés. Prenez un instant pour répondre à la question avant de continuer."
                            }
                        ];
                    },
                    vide: function () {
                        return [
                            { title: 'Nouvelle section', durationEstimate: '', visual: '', script: '', mode: 'exact' }
                        ];
                    }
                },

                // ═══════════════════════════════════ init ═══════════════════════════════════
                init: function () {
                    var self = this;

                    var hadSavedPrefs = this._loadPrefs();
                    if (!hadSavedPrefs) {
                        // Aucune préférence sauvegardée : détecter prefers-reduced-motion système
                        // comme valeur de départ (ne jamais imposer d'animation à qui l'a désactivée).
                        this.reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
                    }
                    if (this.themePreference === 'systeme' && window.matchMedia) {
                        // Résolution informative seulement (le contrat DOM lie directement data-theme
                        // à themePreference côté Blade) — conservé pour cohérence interne future.
                        this._systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    }

                    this._loadProject();
                    this.generatePrompt();
                    this._applyReadingFontSize();

                    // Persistance des préférences : sauvegarde instantanée à chaque changement.
                    ['themePreference', 'highContrast', 'textSize', 'compactView', 'reducedMotion'].forEach(function (prop) {
                        self.$watch(prop, function () { self._savePrefs(); });
                    });

                    // Persistance du projet : les champs de section (titre/durée/visuel/texte/mode)
                    // sont liés par x-model direct côté Blade, sans handler dédié — on capte donc
                    // les mutations via délégation d'événements DOM (fiable, indépendant de la
                    // profondeur de tracking réactif d'Alpine sur les tableaux imbriqués).
                    var root = document.getElementById('prompteur-app-root');
                    if (root) {
                        var scheduleSectionsSave = debounce(function () { self._saveProject(); }, 500);
                        root.addEventListener('input', function (e) {
                            if (e.target && e.target.closest && e.target.closest('#prompteur-sections-list')) {
                                scheduleSectionsSave();
                            }
                        });
                        root.addEventListener('change', function (e) {
                            if (e.target && e.target.closest && e.target.closest('#prompteur-sections-list')) {
                                self._saveProject();
                            }
                        });
                    }

                    this._attachKeyboardShortcuts();
                },

                // ═══════════════════════════════════ Onglets ═══════════════════════════════════
                setActiveTab: function (key) {
                    if (TAB_ORDER.indexOf(key) === -1) return;
                    if (this.teleprompterState === 'running' && key !== 'teleprompter') {
                        this._pauseReading();
                    }
                    this.activeTab = key;
                    var self = this;
                    this.$nextTick(function () {
                        var panel = document.getElementById('prompteur-panel-' + key);
                        if (panel && panel.focus) panel.focus({ preventScroll: true });
                    });
                },

                handleTabKeydown: function (event, key) {
                    var idx = TAB_ORDER.indexOf(key);
                    var nextKey = null;
                    switch (event.key) {
                        case 'ArrowRight':
                        case 'ArrowDown':
                            nextKey = TAB_ORDER[(idx + 1) % TAB_ORDER.length];
                            break;
                        case 'ArrowLeft':
                        case 'ArrowUp':
                            nextKey = TAB_ORDER[(idx - 1 + TAB_ORDER.length) % TAB_ORDER.length];
                            break;
                        case 'Home':
                            nextKey = TAB_ORDER[0];
                            break;
                        case 'End':
                            nextKey = TAB_ORDER[TAB_ORDER.length - 1];
                            break;
                        default:
                            return;
                    }
                    event.preventDefault();
                    this.setActiveTab(nextKey);
                    var tabBtn = document.getElementById('prompteur-tab-' + nextKey);
                    if (tabBtn && tabBtn.focus) tabBtn.focus();
                },

                // ═══════════════════════════════════ BYOA (générateur) ═══════════════════════════════════
                generatePrompt: function () {
                    var goal = (this.form.goal || '').trim();
                    var audience = (this.form.audience || '').trim();
                    var durationLabel = this._resolveDurationLabel();
                    var toneLabel = this._resolveToneLabel();

                    this.generatedPrompt =
                        "Tu es un assistant de scénarisation vidéo. Génère un script structuré pour une vidéo avec ces caractéristiques :\n" +
                        "- Objectif : " + (goal || '(non précisé)') + "\n" +
                        "- Public cible : " + (audience || '(non précisé)') + "\n" +
                        "- Durée visée : " + durationLabel + "\n" +
                        "- Ton : " + toneLabel + "\n\n" +
                        "Structure la vidéo en 4 à 8 sections. Pour CHAQUE section, donne :\n" +
                        "1. Un titre court\n" +
                        "2. Une durée estimée en secondes\n" +
                        "3. Une INDICATION VISUELLE/ACTION à l'écran (ce qui doit être montré ou fait, ex: \"Afficher le tableau de bord, zoomer sur le bouton Créer\")\n" +
                        "4. Le CONTENU AUDIO : soit le texte exact à dire (verbatim), soit seulement les grandes lignes/points clés – précise laquelle des deux formes tu fournis pour chaque section\n\n" +
                        "IMPORTANT – FORMAT DE SORTIE OBLIGATOIRE :\n" +
                        "Réponds d'abord en texte normal lisible (Markdown), PUIS termine IMPÉRATIVEMENT par un bloc unique délimité exactement ainsi (ne mets RIEN d'autre après ce bloc) :\n\n" +
                        JSON_MARKER_START + "\n" +
                        "```json\n" +
                        "{\n" +
                        "  \"schema_version\": 1,\n" +
                        "  \"title\": \"Titre de la vidéo\",\n" +
                        "  \"sections\": [\n" +
                        "    {\n" +
                        "      \"title\": \"Titre de la section\",\n" +
                        "      \"duration_estimate\": 30,\n" +
                        "      \"visual\": \"Indication visuelle/action à l'écran\",\n" +
                        "      \"mode\": \"verbatim\",\n" +
                        "      \"script\": \"Texte exact ou grandes lignes selon le mode\"\n" +
                        "    }\n" +
                        "  ]\n" +
                        "}\n" +
                        "```\n" +
                        JSON_MARKER_END + "\n\n" +
                        "Le champ \"mode\" doit être soit \"verbatim\" soit \"outline\". Ne mets AUCUN texte après " + JSON_MARKER_END + ".";

                    this._saveProject();
                },

                _resolveDurationLabel: function () {
                    if (this.form.duration === 'autre') {
                        var custom = (this.form.durationCustom || '').trim();
                        return custom || '(non précisée)';
                    }
                    if (this.form.duration === '5-10') return '5 à 10 minutes';
                    return '2 à 3 minutes';
                },

                _resolveToneLabel: function () {
                    var map = {
                        professionnel: 'Professionnel',
                        pedagogique: 'Pédagogique',
                        conversationnel: 'Conversationnel',
                        humoristique: 'Humoristique'
                    };
                    return map[this.form.tone] || 'Professionnel';
                },

                copyPromptToClipboard: function () {
                    var self = this;
                    var text = this.generatedPrompt || '';
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function () {
                            self._flashCopied();
                        }).catch(function () {
                            self._fallbackCopy();
                        });
                    } else {
                        this._fallbackCopy();
                    }
                },

                _flashCopied: function () {
                    var self = this;
                    this.promptCopied = true;
                    clearTimeout(this._copyTimer);
                    this._copyTimer = setTimeout(function () { self.promptCopied = false; }, 2500);
                },

                // Repli si l'API Clipboard échoue (permission/HTTPS) : sélectionne le texte du
                // textarea readonly pour un Ctrl+C manuel, et affiche un avis discret (jamais alert()).
                _fallbackCopy: function () {
                    var textarea = document.getElementById('prompteur-generated-prompt-textarea');
                    if (textarea && textarea.select) {
                        textarea.focus();
                        textarea.select();
                        if (textarea.setSelectionRange) textarea.setSelectionRange(0, textarea.value.length);
                    }
                    this._showInlineNotice('.pr-copy-row', "Impossible de copier automatiquement. Le texte est sélectionné : utilisez Ctrl+C (ou Cmd+C) pour le copier manuellement.");
                },

                // Collage riche dans le champ « Objectif de la vidéo » : si le presse-papier
                // contient du HTML (ex. article de blog copié depuis un navigateur), on le
                // convertit en Markdown via Turndown (vendorisé, voir turndown.min.js) au lieu
                // de laisser le navigateur coller du texte brut sans formatage. Le texte brut
                // simple (aucun HTML dans le presse-papier) n'est JAMAIS passé dans Turndown :
                // on laisse alors le comportement natif du navigateur agir (pas de preventDefault).
                handleGoalPaste: function (event) {
                    var clipboardData = event.clipboardData || window.clipboardData;
                    if (!clipboardData) return;

                    var html = clipboardData.getData('text/html');
                    if (!html || !html.trim() || typeof window.TurndownService === 'undefined') {
                        return; // pas de HTML riche, ou lib absente : comportement natif inchangé
                    }

                    var markdown = null;
                    try {
                        var turndownService = new window.TurndownService({ headingStyle: 'atx' });
                        markdown = turndownService.turndown(html).trim();
                    } catch (e) {
                        markdown = null;
                    }
                    if (!markdown) return; // conversion échouée : repli sur le collage natif (texte brut)

                    event.preventDefault();

                    var self = this;
                    var textarea = this.$refs.goalTextarea;
                    var currentValue = this.form.goal || '';
                    var start = (textarea && typeof textarea.selectionStart === 'number') ? textarea.selectionStart : currentValue.length;
                    var end = (textarea && typeof textarea.selectionEnd === 'number') ? textarea.selectionEnd : currentValue.length;

                    this.form.goal = currentValue.slice(0, start) + markdown + currentValue.slice(end);
                    var newCursorPos = start + markdown.length;

                    this.$nextTick(function () {
                        if (textarea && textarea.setSelectionRange) {
                            textarea.focus();
                            textarea.setSelectionRange(newCursorPos, newCursorPos);
                        }
                        self._showInlineNotice('#prompteur-goal-field', "Collage HTML converti automatiquement en Markdown.");
                    });

                    this.generatePrompt();
                },

                // ═══════════════════════════════════ Import (onglet 2) ═══════════════════════════════════
                pasteFromClipboard: function () {
                    var self = this;
                    if (!navigator.clipboard || !navigator.clipboard.readText) {
                        this.importStatusMessage = "Votre navigateur ne permet pas la lecture automatique du presse-papier. Collez manuellement (Ctrl+V ou Cmd+V) dans la zone de texte ci-dessus.";
                        return;
                    }
                    navigator.clipboard.readText().then(function (text) {
                        if (!text || !text.trim()) {
                            self.importStatusMessage = "Le presse-papier est vide. Copiez la réponse de votre IA, puis réessayez – ou collez manuellement.";
                            return;
                        }
                        self.importRawText = text;
                        self.importScript();
                    }).catch(function () {
                        self.importStatusMessage = "Impossible de lire le presse-papier automatiquement (permission refusée). Collez manuellement (Ctrl+V ou Cmd+V) dans la zone de texte ci-dessus.";
                    });
                },

                // Parseur tolérant en cascade — voir l'en-tête du fichier. Ne lève jamais
                // d'exception vers l'utilisateur, ne vide jamais importRawText en cas d'échec.
                importScript: function () {
                    var raw = this.importRawText || '';
                    if (!raw.trim()) {
                        this.importStatusMessage = "Collez d'abord le texte de réponse de votre IA dans la zone ci-dessus.";
                        return;
                    }

                    var candidate = this._extractJsonCandidate(raw);
                    if (!candidate) {
                        this.importStatusMessage = "Le format n'a pas été reconnu automatiquement. Vous pouvez modifier le texte ci-dessus pour qu'il contienne un bloc JSON valide, ou ajouter les sections manuellement ci-dessous avec le bouton + Ajouter une section.";
                        return;
                    }

                    var cleaned = this._cleanJsonCandidate(candidate);
                    var parsed = null;
                    try {
                        parsed = JSON.parse(cleaned);
                    } catch (e) {
                        parsed = null;
                    }

                    if (!parsed) {
                        this.importStatusMessage = "Le bloc JSON trouvé n'a pas pu être lu (format invalide). Vous pouvez corriger le texte ci-dessus, ou ajouter les sections manuellement ci-dessous avec le bouton + Ajouter une section.";
                        return;
                    }

                    var normalized = this._normalizeParsedScript(parsed);
                    if (!normalized || !normalized.sections.length) {
                        this.importStatusMessage = "Aucune section exploitable n'a été trouvée dans la réponse. Vous pouvez ajouter les sections manuellement ci-dessous avec le bouton + Ajouter une section.";
                        return;
                    }

                    this.sections = normalized.sections;
                    if (normalized.title) this.title = normalized.title;
                    var count = normalized.sections.length;
                    this.importStatusMessage = count + (count > 1 ? ' sections importées avec succès.' : ' section importée avec succès.');
                    this._saveProject();
                    this._focusSectionsList();
                },

                // Étapes 1-3 du plan : marqueurs dédiés > dernier bloc ```json``` > dernier objet
                // { ... } équilibré (comptage d'accolades conscient des chaînes, pas indexOf naïf).
                _extractJsonCandidate: function (raw) {
                    var markerRe = new RegExp(JSON_MARKER_START + '([\\s\\S]*?)' + JSON_MARKER_END);
                    var markerMatch = raw.match(markerRe);
                    if (markerMatch) {
                        var inner = markerMatch[1];
                        var fencedInMarker = this._lastFencedJsonBlock(inner);
                        return fencedInMarker || inner.trim();
                    }

                    var lastFenced = this._lastFencedJsonBlock(raw);
                    if (lastFenced) return lastFenced;

                    return this._lastBalancedJsonObject(raw);
                },

                _lastFencedJsonBlock: function (text) {
                    var re = /```(?:json)?\s*([\s\S]*?)```/gi;
                    var match;
                    var last = null;
                    while ((match = re.exec(text)) !== null) {
                        last = match[1];
                    }
                    return last ? last.trim() : null;
                },

                // Recherche l'objet JSON racine du document parmi TOUTES les accolades ouvrantes
                // du texte (pas seulement la dernière — un JSON multi-sections a autant de "{"
                // que de sections imbriquées + 1 pour l'objet racine ; partir uniquement de la
                // DERNIÈRE "{" capture systématiquement la dernière section imbriquée, pas le
                // document complet). Pour chaque position, on extrait le candidat équilibré
                // (accolades conscientes des chaînes, via _extractBalancedFrom, inchangée) puis on
                // le parse à l'essai : le candidat retenu est celui qui contient une clé "sections"
                // (tableau non vide) avec le PLUS de sections — signe distinctif du document racine
                // plutôt que d'un fragment de section individuelle. Si aucun candidat n'a de clé
                // "sections" valide (format de réponse IA vraiment différent), on retombe sur le
                // comportement historique (dernier "{" en partant de la fin, premier candidat
                // syntaxiquement équilibré) pour ne rien régresser sur les cas déjà couverts.
                _lastBalancedJsonObject: function (text) {
                    var positions = this._openBracePositions(text);
                    var bestCandidate = null;
                    var bestSectionCount = 0;
                    for (var p = 0; p < positions.length; p++) {
                        var candidate = this._extractBalancedFrom(text, positions[p]);
                        if (!candidate) continue;
                        // Parse d'ESSAI, indépendant du parse final fait par importScript() sur le
                        // candidat choisi (via _cleanJsonCandidate puis JSON.parse) — silencieux en
                        // cas d'échec, ce n'est qu'une heuristique de sélection du meilleur candidat.
                        var parsed = null;
                        try {
                            parsed = JSON.parse(candidate);
                        } catch (e) {
                            parsed = null;
                        }
                        if (parsed && typeof parsed === 'object' && Array.isArray(parsed.sections) &&
                            parsed.sections.length > bestSectionCount) {
                            bestSectionCount = parsed.sections.length;
                            bestCandidate = candidate;
                        }
                    }
                    if (bestCandidate) return bestCandidate;

                    // Repli — comportement historique inchangé.
                    var openIdx = text.lastIndexOf('{');
                    while (openIdx !== -1) {
                        var fallback = this._extractBalancedFrom(text, openIdx);
                        if (fallback) return fallback;
                        openIdx = text.lastIndexOf('{', openIdx - 1);
                    }
                    return null;
                },

                // Positions de toutes les accolades ouvrantes du texte, de la première à la
                // dernière. Limite à ~150 positions max (sous-échantillon régulier réparti sur
                // tout le texte si dépassement) pour éviter un coût quadratique excessif sur un
                // texte pathologiquement long — le cas d'usage réel (réponse d'IA collée, quelques
                // Ko, dizaines de sections max) ne devrait jamais s'en approcher.
                _openBracePositions: function (text) {
                    var all = [];
                    for (var i = 0; i < text.length; i++) {
                        if (text[i] === '{') all.push(i);
                    }
                    if (all.length <= 150) return all;
                    var sampled = [];
                    var step = all.length / 150;
                    for (var j = 0; j < 150; j++) {
                        sampled.push(all[Math.floor(j * step)]);
                    }
                    return sampled;
                },

                _extractBalancedFrom: function (text, startIdx) {
                    var depth = 0;
                    var inString = false;
                    var stringChar = '';
                    var escaped = false;
                    for (var i = startIdx; i < text.length; i++) {
                        var ch = text[i];
                        if (inString) {
                            if (escaped) {
                                escaped = false;
                            } else if (ch === '\\') {
                                escaped = true;
                            } else if (ch === stringChar) {
                                inString = false;
                            }
                            continue;
                        }
                        if (ch === '"' || ch === "'") {
                            inString = true;
                            stringChar = ch;
                            continue;
                        }
                        if (ch === '{') depth++;
                        else if (ch === '}') {
                            depth--;
                            if (depth === 0) {
                                return text.slice(startIdx, i + 1);
                            }
                            if (depth < 0) return null;
                        }
                    }
                    return null;
                },

                // Nettoyage bénin avant JSON.parse : virgules finales, guillemets typographiques,
                // commentaires // en fin de ligne (hors chaînes).
                _cleanJsonCandidate: function (candidate) {
                    var cleaned = candidate
                        .replace(/[“”]/g, '"')
                        .replace(/[‘’]/g, "'");
                    cleaned = this._stripLineComments(cleaned);
                    cleaned = cleaned.replace(/,\s*([}\]])/g, '$1');
                    return cleaned;
                },

                _stripLineComments: function (text) {
                    return text.split('\n').map(function (line) {
                        var inString = false;
                        var stringChar = '';
                        var escaped = false;
                        for (var i = 0; i < line.length - 1; i++) {
                            var ch = line[i];
                            if (inString) {
                                if (escaped) { escaped = false; }
                                else if (ch === '\\') { escaped = true; }
                                else if (ch === stringChar) { inString = false; }
                                continue;
                            }
                            if (ch === '"' || ch === "'") {
                                inString = true;
                                stringChar = ch;
                                continue;
                            }
                            if (ch === '/' && line[i + 1] === '/') {
                                return line.slice(0, i).replace(/\s+$/, '');
                            }
                        }
                        return line;
                    }).join('\n');
                },

                // Valide et normalise un objet parsé (venant de l'IA ou d'un fichier .json exporté
                // par cet outil) en tableau de sections internes, avec valeurs par défaut sûres
                // plutôt que rejet — accepte les deux orthographes de durée (duration_estimate,
                // le champ envoyé à l'IA ; durationEstimate, le champ de notre propre export).
                _normalizeParsedScript: function (parsed) {
                    if (!parsed || typeof parsed !== 'object') return null;
                    var sectionsRaw = Array.isArray(parsed.sections)
                        ? parsed.sections
                        : (Array.isArray(parsed) ? parsed : null);
                    if (!sectionsRaw || !sectionsRaw.length) return null;

                    var self = this;
                    var sections = [];
                    sectionsRaw.forEach(function (s, i) {
                        if (!s || typeof s !== 'object') return;
                        var script = typeof s.script === 'string' ? s.script.trim() : '';
                        var visual = typeof s.visual === 'string' ? s.visual.trim() : '';
                        if (!script && !visual) return; // au moins un des deux requis
                        var title = (typeof s.title === 'string' && s.title.trim()) ? s.title.trim() : ('Section ' + (i + 1));
                        // 'outline' conservé tel quel ; toute autre valeur (dont 'verbatim', le mot
                        // utilisé dans le méta-prompt) se normalise vers 'exact' — la seule autre
                        // valeur acceptée par les radios de la vue (voir prompteur.blade.php).
                        var mode = (s.mode === 'outline') ? 'outline' : 'exact';
                        var durRaw = (s.duration_estimate !== undefined) ? s.duration_estimate : s.durationEstimate;
                        var durationEstimate = '';
                        if (typeof durRaw === 'number' && isFinite(durRaw)) {
                            durationEstimate = String(durRaw);
                        } else if (typeof durRaw === 'string' && durRaw.trim()) {
                            durationEstimate = durRaw.trim();
                        }
                        sections.push({
                            id: self._makeId(),
                            title: title,
                            durationEstimate: durationEstimate,
                            visual: visual,
                            script: script,
                            mode: mode
                        });
                    });

                    if (!sections.length) return null;
                    return {
                        title: (typeof parsed.title === 'string' ? parsed.title.trim() : ''),
                        sections: sections
                    };
                },

                _focusSectionsList: function () {
                    var self = this;
                    this.$nextTick(function () {
                        var list = document.getElementById('prompteur-sections-list');
                        if (list && list.scrollIntoView) {
                            list.scrollIntoView({ behavior: self.reducedMotion ? 'auto' : 'smooth', block: 'start' });
                        }
                    });
                },

                // ═══════════════════════════════════ Gabarits ═══════════════════════════════════
                loadTemplate: function (key) {
                    var self = this;
                    if (this.sections.length > 0) {
                        this._confirmAction(
                            'Charger ce gabarit remplacera les sections actuelles du projet. Continuer ?',
                            function () { self._applyTemplate(key); }
                        );
                        return;
                    }
                    this._applyTemplate(key);
                },

                _applyTemplate: function (key) {
                    var builder = this._templateBuilders[key];
                    if (!builder) return;
                    var self = this;
                    this.sections = builder().map(function (s) {
                        return {
                            id: self._makeId(),
                            title: s.title,
                            durationEstimate: s.durationEstimate,
                            visual: s.visual,
                            script: s.script,
                            mode: s.mode
                        };
                    });
                    this._saveProject();
                },

                // ═══════════════════════════════════ Projet ═══════════════════════════════════
                newProject: function () {
                    var self = this;
                    var hasContent = this.sections.length > 0 || !!(this.title && this.title.trim());
                    if (hasContent) {
                        this._confirmAction(
                            'Démarrer un nouveau projet effacera les sections actuelles non exportées. Continuer ?',
                            function () { self._doNewProject(); }
                        );
                        return;
                    }
                    this._doNewProject();
                },

                _doNewProject: function () {
                    this.sections = [];
                    this.title = '';
                    this.form = { goal: '', audience: '', duration: '2-3', durationCustom: '', tone: 'professionnel' };
                    this.generatePrompt();
                    this._saveProject();
                },

                // V1 : un seul emplacement localStorage — "dupliquer" renomme le titre en cours
                // comme point de départ d'édition (pas de vraie gestion multi-projets).
                duplicateProject: function () {
                    var base = (this.title && this.title.trim()) ? this.title.trim() : 'Sans titre';
                    this.title = 'Copie de ' + base;
                    this._saveProject();
                },

                exportProjectJson: function () {
                    var payload = {
                        schema_version: 1,
                        title: this.title || '',
                        sections: this.sections.map(function (s) {
                            return {
                                title: s.title,
                                durationEstimate: s.durationEstimate,
                                visual: s.visual,
                                script: s.script,
                                mode: s.mode
                            };
                        }),
                        exportedAt: new Date().toISOString()
                    };
                    var blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
                    this._downloadBlob(blob, this._buildExportFilename('json'));
                },

                importProjectJson: function (event) {
                    var self = this;
                    var input = event && event.target;
                    var file = input && input.files && input.files[0];
                    if (!file) return;

                    var reader = new FileReader();
                    reader.onload = function () {
                        var parsed = null;
                        try {
                            parsed = JSON.parse(String(reader.result || ''));
                        } catch (e) {
                            parsed = null;
                        }
                        if (!parsed || !Array.isArray(parsed.sections)) {
                            self.importStatusMessage = "Le fichier sélectionné n'est pas un projet Prompteur valide (JSON attendu avec un tableau \"sections\").";
                            if (input) input.value = '';
                            return;
                        }
                        var normalized = self._normalizeParsedScript(parsed);
                        if (!normalized || !normalized.sections.length) {
                            self.importStatusMessage = "Le fichier ne contient aucune section exploitable.";
                            if (input) input.value = '';
                            return;
                        }
                        self.sections = normalized.sections;
                        if (normalized.title) self.title = normalized.title;
                        var count = normalized.sections.length;
                        self.importStatusMessage = count + (count > 1 ? ' sections importées depuis le fichier projet.' : ' section importée depuis le fichier projet.');
                        self._saveProject();
                        if (input) input.value = '';
                    };
                    reader.onerror = function () {
                        self.importStatusMessage = "Erreur de lecture du fichier. Réessayez.";
                        if (input) input.value = '';
                    };
                    reader.readAsText(file);
                },

                exportProjectText: function () {
                    var lines = [];
                    var title = (this.title && this.title.trim()) ? this.title.trim() : 'Script vidéo sans titre';
                    lines.push('# ' + title);
                    lines.push('');
                    this.sections.forEach(function (s, i) {
                        lines.push('## ' + (i + 1) + '. ' + (s.title || ('Section ' + (i + 1))));
                        if (s.durationEstimate) lines.push('Durée estimée : ' + s.durationEstimate);
                        lines.push('');
                        lines.push('**Action / Visuel :** ' + (s.visual || '(non précisé)'));
                        lines.push('');
                        lines.push('**' + (s.mode === 'outline' ? 'Grandes lignes' : 'Texte exact à dire') + ' :**');
                        lines.push(s.script || '(vide)');
                        lines.push('');
                    });
                    var blob = new Blob([lines.join('\n')], { type: 'text/markdown;charset=utf-8' });
                    this._downloadBlob(blob, this._buildExportFilename('md'));
                },

                _buildExportFilename: function (ext) {
                    var slug = this._slugify(this.title || '') || 'sans-titre';
                    var date = new Date();
                    var y = date.getFullYear();
                    var m = String(date.getMonth() + 1).padStart(2, '0');
                    var d = String(date.getDate()).padStart(2, '0');
                    return 'prompteur-' + slug + '-' + y + '-' + m + '-' + d + '.' + ext;
                },

                _slugify: function (text) {
                    return (text || '').toString().trim().toLowerCase()
                        .normalize('NFD').replace(/[̀-ͯ]/g, '')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .slice(0, 60);
                },

                _downloadBlob: function (blob, filename) {
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                },

                // ═══════════════════════════════════ Éditeur de sections ═══════════════════════════════════
                addSection: function () {
                    this.sections.push({
                        id: this._makeId(),
                        title: 'Nouvelle section',
                        durationEstimate: '',
                        visual: '',
                        script: '',
                        mode: 'exact'
                    });
                    this._saveProject();
                },

                moveSectionUp: function (index) {
                    if (index <= 0 || index >= this.sections.length) return;
                    var arr = this.sections;
                    var tmp = arr[index - 1];
                    arr[index - 1] = arr[index];
                    arr[index] = tmp;
                    this._saveProject();
                },

                moveSectionDown: function (index) {
                    if (index < 0 || index >= this.sections.length - 1) return;
                    var arr = this.sections;
                    var tmp = arr[index + 1];
                    arr[index + 1] = arr[index];
                    arr[index] = tmp;
                    this._saveProject();
                },

                duplicateSection: function (index) {
                    if (index < 0 || index >= this.sections.length) return;
                    var src = this.sections[index];
                    var clone = {
                        id: this._makeId(),
                        title: (src.title || 'Section') + ' (copie)',
                        durationEstimate: src.durationEstimate,
                        visual: src.visual,
                        script: src.script,
                        mode: src.mode
                    };
                    this.sections.splice(index + 1, 0, clone);
                    this._saveProject();
                },

                // Pas de confirm() natif : bascule "cliquez à nouveau pour confirmer" sur le
                // bouton lui-même (titre/aria-label), fenêtre de 3 secondes, sans dépendance à
                // une classe CSS qui n'existe pas dans prompteur.css (a11y d'abord).
                deleteSection: function (index) {
                    if (index < 0 || index >= this.sections.length) return;
                    var section = this.sections[index];
                    var now = Date.now();
                    var self = this;

                    if (this._pendingDeleteId === section.id && (now - this._pendingDeleteAt) < 3000) {
                        clearTimeout(this._pendingDeleteTimer);
                        this._pendingDeleteId = null;
                        this._clearDeleteConfirmUi(section.id);
                        this.sections.splice(index, 1);
                        this._saveProject();
                        return;
                    }

                    this._clearDeleteConfirmUi(this._pendingDeleteId);
                    this._pendingDeleteId = section.id;
                    this._pendingDeleteAt = now;
                    this._showDeleteConfirmUi(section.id);
                    clearTimeout(this._pendingDeleteTimer);
                    this._pendingDeleteTimer = setTimeout(function () {
                        if (self._pendingDeleteId === section.id) {
                            self._pendingDeleteId = null;
                            self._clearDeleteConfirmUi(section.id);
                        }
                    }, 3000);
                },

                _showDeleteConfirmUi: function (sectionId) {
                    var btn = document.getElementById('prompteur-section-' + sectionId + '-delete-btn');
                    if (!btn) return;
                    btn.setAttribute('title', 'Cliquez à nouveau pour confirmer la suppression');
                    btn.setAttribute('aria-label', 'Cliquez à nouveau pour confirmer la suppression de cette section');
                },

                _clearDeleteConfirmUi: function (sectionId) {
                    if (!sectionId) return;
                    var btn = document.getElementById('prompteur-section-' + sectionId + '-delete-btn');
                    if (!btn) return;
                    btn.setAttribute('title', 'Supprimer');
                    btn.setAttribute('aria-label', 'Supprimer cette section');
                },

                // ═══════════════════════════════════ Confirmation non-native (DOM) ═══════════════════════════════════
                // Remplace confirm() natif (interdit) : petit encart modal réel (alertdialog),
                // boutons Confirmer/Annuler, fermeture Échap/clic extérieur — jamais de blocage
                // du thread, jamais de dialogue navigateur.
                _confirmAction: function (message, onConfirm) {
                    this._removeConfirmToast();
                    var self = this;

                    var overlay = document.createElement('div');
                    overlay.id = 'prompteur-confirm-toast';
                    overlay.setAttribute('role', 'alertdialog');
                    overlay.setAttribute('aria-modal', 'true');
                    overlay.setAttribute('aria-labelledby', 'prompteur-confirm-toast-msg');
                    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99999;display:flex;align-items:center;justify-content:center;padding:1rem;';

                    var box = document.createElement('div');
                    box.style.cssText = 'background:#fff;border-radius:.75rem;padding:1.25rem;max-width:26rem;width:100%;box-shadow:0 12px 32px rgba(0,0,0,.25);';

                    var msg = document.createElement('p');
                    msg.id = 'prompteur-confirm-toast-msg';
                    msg.textContent = message;
                    msg.style.cssText = 'margin:0 0 1rem;color:#1A1D23;font-size:.95rem;';

                    var actions = document.createElement('div');
                    actions.style.cssText = 'display:flex;gap:.6rem;justify-content:flex-end;flex-wrap:wrap;';

                    var cancelBtn = document.createElement('button');
                    cancelBtn.type = 'button';
                    cancelBtn.className = 'ct-btn ct-btn-outline ct-btn-sm';
                    cancelBtn.textContent = 'Annuler';

                    var okBtn = document.createElement('button');
                    okBtn.type = 'button';
                    okBtn.className = 'ct-btn ct-btn-primary ct-btn-sm';
                    okBtn.textContent = 'Confirmer';

                    function close() { self._removeConfirmToast(); }
                    cancelBtn.addEventListener('click', close);
                    okBtn.addEventListener('click', function () { close(); onConfirm(); });
                    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

                    var escHandler = function (e) { if (e.key === 'Escape') close(); };
                    overlay._prEscHandler = escHandler;
                    document.addEventListener('keydown', escHandler);

                    actions.appendChild(cancelBtn);
                    actions.appendChild(okBtn);
                    box.appendChild(msg);
                    box.appendChild(actions);
                    overlay.appendChild(box);
                    document.body.appendChild(overlay);
                    okBtn.focus();
                },

                _removeConfirmToast: function () {
                    var el = document.getElementById('prompteur-confirm-toast');
                    if (el) {
                        if (el._prEscHandler) document.removeEventListener('keydown', el._prEscHandler);
                        el.remove();
                    }
                },

                // Petit avis inline non-bloquant, réutilisé pour le repli de copie (voir
                // _fallbackCopy) — pas de dialogue, disparaît seul, réutilise la classe existante
                // .pr-import-status (DRY : même style que les messages d'import).
                _showInlineNotice: function (containerSelector, message) {
                    var container = document.querySelector(containerSelector);
                    if (!container) return;
                    var existing = container.querySelector('.pr-inline-notice');
                    if (existing) existing.remove();
                    var notice = document.createElement('p');
                    notice.className = 'pr-import-status pr-inline-notice';
                    notice.setAttribute('role', 'status');
                    notice.setAttribute('aria-live', 'polite');
                    notice.style.cssText = 'margin:.5rem 0 0;width:100%;';
                    notice.textContent = message;
                    container.appendChild(notice);
                    clearTimeout(container._prNoticeTimer);
                    container._prNoticeTimer = setTimeout(function () {
                        if (notice && notice.parentNode) notice.parentNode.removeChild(notice);
                    }, 6000);
                },

                // ═══════════════════════════════════ Personnalisation ═══════════════════════════════════
                openSettings: function () {
                    this._settingsTriggerEl = document.activeElement;
                    this.settingsOpen = true;
                    var self = this;
                    this.$nextTick(function () {
                        var panel = document.getElementById('prompteur-settings-panel');
                        if (!panel) return;
                        var focusable = panel.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                        if (focusable.length) focusable[0].focus();
                        self._settingsTabHandler = function (ev) {
                            if (ev.key !== 'Tab') return;
                            var items = panel.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                            if (!items.length) return;
                            var first = items[0], last = items[items.length - 1];
                            if (ev.shiftKey && document.activeElement === first) {
                                ev.preventDefault();
                                last.focus();
                            } else if (!ev.shiftKey && document.activeElement === last) {
                                ev.preventDefault();
                                first.focus();
                            }
                        };
                        panel.addEventListener('keydown', self._settingsTabHandler);
                    });
                },

                closeSettings: function () {
                    var panel = document.getElementById('prompteur-settings-panel');
                    if (panel && this._settingsTabHandler) {
                        panel.removeEventListener('keydown', this._settingsTabHandler);
                        this._settingsTabHandler = null;
                    }
                    this.settingsOpen = false;
                    if (this._settingsTriggerEl && this._settingsTriggerEl.focus) {
                        this._settingsTriggerEl.focus();
                    }
                    this._settingsTriggerEl = null;
                },

                resetSettings: function () {
                    this.themePreference = 'systeme';
                    this.highContrast = false;
                    this.textSize = 'moyen';
                    this.compactView = false;
                    this.reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
                    try { localStorage.removeItem('prompteur_prefs_v1'); } catch (e) {}
                },

                _savePrefs: function () {
                    try {
                        localStorage.setItem('prompteur_prefs_v1', JSON.stringify({
                            schema_version: 1,
                            themePreference: this.themePreference,
                            highContrast: this.highContrast,
                            textSize: this.textSize,
                            compactView: this.compactView,
                            reducedMotion: this.reducedMotion
                        }));
                    } catch (e) {}
                },

                _loadPrefs: function () {
                    var hadSaved = false;
                    try {
                        var raw = localStorage.getItem('prompteur_prefs_v1');
                        if (raw) {
                            var data = JSON.parse(raw);
                            if (data) {
                                hadSaved = true;
                                if (typeof data.themePreference === 'string') this.themePreference = data.themePreference;
                                if (typeof data.highContrast === 'boolean') this.highContrast = data.highContrast;
                                if (typeof data.textSize === 'string') this.textSize = data.textSize;
                                if (typeof data.compactView === 'boolean') this.compactView = data.compactView;
                                if (typeof data.reducedMotion === 'boolean') this.reducedMotion = data.reducedMotion;
                            }
                        }
                    } catch (e) {}
                    return hadSaved;
                },

                // ═══════════════════════════════════ Persistance projet ═══════════════════════════════════
                _saveProject: function () {
                    try {
                        localStorage.setItem('prompteur_project_v1', JSON.stringify({
                            schema_version: 1,
                            title: this.title || '',
                            sections: this.sections,
                            form: this.form,
                            updatedAt: new Date().toISOString()
                        }));
                    } catch (e) {}
                },

                _loadProject: function () {
                    try {
                        var raw = localStorage.getItem('prompteur_project_v1');
                        if (!raw) return;
                        var data = JSON.parse(raw);
                        if (!data) return;
                        if (Array.isArray(data.sections)) this.sections = data.sections;
                        if (typeof data.title === 'string') this.title = data.title;
                        if (data.form && typeof data.form === 'object') {
                            this.form = {
                                goal: typeof data.form.goal === 'string' ? data.form.goal : '',
                                audience: typeof data.form.audience === 'string' ? data.form.audience : '',
                                duration: typeof data.form.duration === 'string' ? data.form.duration : '2-3',
                                durationCustom: typeof data.form.durationCustom === 'string' ? data.form.durationCustom : '',
                                tone: typeof data.form.tone === 'string' ? data.form.tone : 'professionnel'
                            };
                        }
                    } catch (e) {}
                },

                _makeId: function () {
                    if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
                    return 'pr-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
                },

                // ═══════════════════════════════════ Téléprompteur ═══════════════════════════════════
                togglePlayPause: function () {
                    if (this.teleprompterState === 'running') {
                        this._pauseReading();
                    } else if (this.teleprompterState === 'paused') {
                        this._resumeReading();
                    } else {
                        this._startReadingWithCountdown();
                    }
                },

                // Le plein écran DOIT être déclenché de façon strictement synchrone dans le geste
                // utilisateur (clic) — l'API Fullscreen refuse toute activation différée (ex. après
                // un setTimeout). Le compte à rebours qui suit est asynchrone, mais la demande de
                // plein écran est passée AVANT, dans le même appel synchrone.
                _startReadingWithCountdown: function () {
                    this._requestFullscreen('#prompteur-fullscreen-target');

                    if (this.sections.length === 0) {
                        this.teleprompterAriaMessage = 'Ajoutez au moins une section avant de démarrer la lecture.';
                        return;
                    }

                    var self = this;
                    this._resetReadingPosition();
                    this.countdownValue = 3;
                    this.teleprompterAriaMessage = 'Départ dans 3 secondes.';
                    clearInterval(this._countdownTimer);
                    this._countdownTimer = setInterval(function () {
                        self.countdownValue -= 1;
                        if (self.countdownValue <= 0) {
                            clearInterval(self._countdownTimer);
                            self._countdownTimer = null;
                            self.countdownValue = 0;
                            self._beginScrolling();
                        }
                    }, 1000);
                },

                _beginScrolling: function () {
                    this.teleprompterState = 'running';
                    this._readingStartTime = performance.now();
                    this._pausedElapsedMs = 0;
                    this._lastAnnouncedMinuteTp = null;
                    this.teleprompterAriaMessage = 'Lecture démarrée.';
                    this._readingLoop();
                },

                _pauseReading: function () {
                    if (this.teleprompterState !== 'running') return;
                    this.teleprompterState = 'paused';
                    if (this._readingRafId) {
                        cancelAnimationFrame(this._readingRafId);
                        this._readingRafId = null;
                    }
                    this._pausedElapsedMs = (this._pausedElapsedMs || 0) + (performance.now() - this._readingStartTime);
                    this.teleprompterAriaMessage = 'Lecture en pause.';
                },

                _resumeReading: function () {
                    this.teleprompterState = 'running';
                    this._readingStartTime = performance.now();
                    this.teleprompterAriaMessage = 'Lecture reprise.';
                    this._readingLoop();
                },

                _readingLoop: function () {
                    var self = this;
                    var step = function () {
                        if (self.teleprompterState !== 'running') return;
                        self._updateReadingProgress();
                        if (self.teleprompterState === 'running') {
                            self._readingRafId = requestAnimationFrame(step);
                        }
                    };
                    this._readingRafId = requestAnimationFrame(step);
                },

                _updateReadingProgress: function () {
                    var content = document.getElementById('prompteur-reading-content');
                    var area = document.getElementById('prompteur-reading-area');
                    if (!content || !area) return;

                    var elapsedMs = (this._pausedElapsedMs || 0) + (performance.now() - this._readingStartTime);
                    var totalMs = this._readingTotalDurationMs();
                    var maxScroll = Math.max(0, content.scrollHeight - area.clientHeight);
                    var fraction = totalMs > 0 ? clamp(elapsedMs / totalMs, 0, 1) : 0;

                    // Le conteneur réellement scrollable est #prompteur-reading-content
                    // (overflow-y: auto) — #prompteur-reading-area a overflow: hidden et ne
                    // sert qu'à clipper les fondus/la ligne de guide. Écrire .scrollTop sur
                    // "area" au lieu de "content" est un piège classique de copier-coller
                    // (les deux éléments partagent la même hauteur visible) : ça ne fait RIEN
                    // visuellement, seule la barre de progression avance. Bug confirmé et
                    // corrigé le 2026-07-20 (scrollTop restait à 0 pendant que la progression
                    // montait à 100 %).
                    content.scrollTop = fraction * maxScroll;
                    this.teleprompterProgress = Math.round(fraction * 100);

                    var remainingMs = Math.max(0, totalMs - elapsedMs);
                    this.teleprompterTimeRemainingLabel = this._formatRemaining(remainingMs);
                    this._maybeAnnounceProgress(remainingMs);

                    if (fraction >= 1) this._finishReading();
                },

                // Vitesse en mots/minute dérivée du niveau 1-10 du slider (aria-valuetext
                // "Vitesse de défilement") : 80 à 260 mots/min, niveau 4 ≈ 140 mots/min — repère
                // de lecture orale confortable en français (le slider Blade n'exprime pas les WPM
                // directement, seulement un niveau 1-10 ; la conversion vit uniquement ici).
                _scrollSpeedToWpm: function () {
                    var level = clamp(parseInt(this.scrollSpeed, 10) || 4, 1, 10);
                    return 80 + (level - 1) * 20;
                },

                _readingTotalDurationMs: function () {
                    var words = this._totalWordCount();
                    var wpm = this._scrollSpeedToWpm();
                    var minutes = words / Math.max(1, wpm);
                    return Math.max(1000, minutes * 60 * 1000);
                },

                _totalWordCount: function () {
                    var text = this.sections.map(function (s) { return s.script || ''; }).join(' ');
                    var words = text.trim().length ? text.trim().split(/\s+/) : [];
                    return words.length || 1;
                },

                _formatRemaining: function (ms) {
                    var totalSec = Math.ceil(ms / 1000);
                    var m = Math.floor(totalSec / 60);
                    var s = totalSec % 60;
                    if (m === 0) return s + ' s restantes';
                    return m + ' min ' + (s < 10 ? '0' : '') + s + ' s restantes';
                },

                // Annonces ARIA sobres : uniquement au changement de minute entière — jamais à
                // chaque frame (même principe que minuteur-visuel-core.js::_checkAnnouncements).
                _maybeAnnounceProgress: function (remainingMs) {
                    var totalMin = Math.ceil(remainingMs / 60000);
                    if (totalMin !== this._lastAnnouncedMinuteTp) {
                        this._lastAnnouncedMinuteTp = totalMin;
                        this.teleprompterAriaMessage = totalMin > 0
                            ? (totalMin + (totalMin > 1 ? ' minutes restantes' : ' minute restante'))
                            : 'Fin de la lecture proche.';
                    }
                },

                _finishReading: function () {
                    this.teleprompterState = 'idle';
                    if (this._readingRafId) {
                        cancelAnimationFrame(this._readingRafId);
                        this._readingRafId = null;
                    }
                    this.teleprompterProgress = 100;
                    this.teleprompterTimeRemainingLabel = '0 s restantes';
                    this.teleprompterAriaMessage = 'Lecture terminée.';
                },

                _resetReadingPosition: function () {
                    // Même conteneur scrollable que _updateReadingProgress : #prompteur-reading-content.
                    var content = document.getElementById('prompteur-reading-content');
                    if (content) content.scrollTop = 0;
                    this.teleprompterProgress = 0;
                    this.teleprompterTimeRemainingLabel = this._formatRemaining(this._readingTotalDurationMs());
                    this._lastAnnouncedMinuteTp = null;
                },

                decreaseReadingFontSize: function () {
                    this.readingFontSizeRem = clamp(Math.round((this.readingFontSizeRem - 0.2) * 10) / 10, 1.2, 4);
                    this._applyReadingFontSize();
                },

                increaseReadingFontSize: function () {
                    this.readingFontSizeRem = clamp(Math.round((this.readingFontSizeRem + 0.2) * 10) / 10, 1.2, 4);
                    this._applyReadingFontSize();
                },

                _applyReadingFontSize: function () {
                    var content = document.getElementById('prompteur-reading-content');
                    if (content) content.style.fontSize = this.readingFontSizeRem + 'rem';
                },

                // pr-high-contrast-reading / pr-mirror-mode sont déjà liées par :class côté Blade
                // sur le panneau du téléprompteur — ces méthodes ne font que basculer l'état.
                toggleTeleprompterContrast: function () {
                    this.teleprompterHighContrast = !this.teleprompterHighContrast;
                },

                toggleMirrorMode: function () {
                    this.mirrorMode = !this.mirrorMode;
                },

                // Amélioration progressive : désactivée proprement (message clair, jamais de
                // plantage) si l'API SpeechRecognition n'existe pas dans le navigateur. Avance le
                // défilement proportionnellement au nombre de mots reconnus vs le total du script
                // — volontairement simple (pas d'alignement mot-à-mot), robuste avant tout.
                startVoiceScroll: function () {
                    if (this.voiceScrollActive) {
                        this._stopVoiceScroll();
                        return;
                    }
                    var SpeechRecognitionCtor = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SpeechRecognitionCtor) {
                        this.teleprompterAriaMessage = "La reconnaissance vocale n'est pas prise en charge par ce navigateur. Utilisez le défilement automatique par vitesse à la place.";
                        return;
                    }

                    var self = this;
                    try {
                        var recognition = new SpeechRecognitionCtor();
                        recognition.continuous = true;
                        recognition.interimResults = true;
                        recognition.lang = 'fr-CA';
                        this._voiceWordsRecognized = 0;
                        this._voiceTotalWords = this._totalWordCount();

                        recognition.onresult = function (event) {
                            var interimWords = 0;
                            for (var i = event.resultIndex; i < event.results.length; i++) {
                                var transcript = (event.results[i][0] && event.results[i][0].transcript) || '';
                                var wordCount = transcript.trim().length ? transcript.trim().split(/\s+/).length : 0;
                                if (event.results[i].isFinal) {
                                    self._voiceWordsRecognized += wordCount;
                                } else {
                                    interimWords += wordCount;
                                }
                            }
                            self._applyVoiceScrollPosition(interimWords);
                        };

                        recognition.onerror = function (event) {
                            self.teleprompterAriaMessage = "Erreur de reconnaissance vocale (" + (event && event.error ? event.error : 'inconnue') + "). Le défilement à la voix a été arrêté.";
                            self._stopVoiceScroll();
                        };

                        recognition.onend = function () {
                            // Certains navigateurs coupent la reconnaissance après un silence
                            // prolongé : redémarrage automatique tant que la session est active.
                            if (self.voiceScrollActive) {
                                try { recognition.start(); } catch (e) { self._stopVoiceScroll(); }
                            }
                        };

                        recognition.start();
                        this._voiceRecognition = recognition;
                        this.voiceScrollActive = true;
                        this.teleprompterAriaMessage = 'Défilement à la voix démarré (expérimental).';
                    } catch (e) {
                        this.teleprompterAriaMessage = 'Impossible de démarrer la reconnaissance vocale (micro refusé ou indisponible).';
                        this.voiceScrollActive = false;
                    }
                },

                _applyVoiceScrollPosition: function (interimWords) {
                    var content = document.getElementById('prompteur-reading-content');
                    var area = document.getElementById('prompteur-reading-area');
                    if (!content || !area) return;
                    var total = this._voiceTotalWords || this._totalWordCount() || 1;
                    var recognized = (this._voiceWordsRecognized || 0) + (interimWords || 0);
                    var fraction = clamp(recognized / total, 0, 1);
                    var maxScroll = Math.max(0, content.scrollHeight - area.clientHeight);
                    content.scrollTop = fraction * maxScroll;
                    this.teleprompterProgress = Math.round(fraction * 100);
                },

                _stopVoiceScroll: function () {
                    if (this._voiceRecognition) {
                        try {
                            this._voiceRecognition.onend = null;
                            this._voiceRecognition.stop();
                        } catch (e) {}
                        this._voiceRecognition = null;
                    }
                    this.voiceScrollActive = false;
                },

                // ═══════════════════════════════════ Plein écran + raccourcis clavier ═══════════════════════════════════
                _requestFullscreen: function (selector) {
                    var el = document.querySelector(selector);
                    if (!el) return;
                    if (document.fullscreenElement || document.webkitFullscreenElement) return;
                    try {
                        if (el.requestFullscreen) el.requestFullscreen().catch(function () {});
                        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
                    } catch (e) {}
                },

                _toggleFullscreen: function (selector) {
                    if (document.fullscreenElement || document.webkitFullscreenElement) {
                        this._exitFullscreen();
                    } else {
                        this._requestFullscreen(selector);
                    }
                },

                _exitFullscreen: function () {
                    try {
                        if (document.exitFullscreen) document.exitFullscreen();
                        else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                    } catch (e) {}
                },

                // Raccourcis actifs uniquement sur l'onglet Téléprompteur (les flèches Haut/Bas
                // casseraient le défilement/la navigation normale des autres onglets), avec garde
                // anti-conflit si le focus est dans un champ de saisie.
                _attachKeyboardShortcuts: function () {
                    var self = this;
                    window.addEventListener('keydown', function (e) {
                        if (self.activeTab !== 'teleprompter') return;
                        var target = e.target;
                        var tag = (target && target.tagName ? target.tagName : '').toLowerCase();
                        var isEditable = !!(target && (target.isContentEditable || ['input', 'textarea', 'select'].indexOf(tag) !== -1));
                        if (isEditable) return;

                        switch (e.key) {
                            case ' ':
                            case 'Spacebar':
                                e.preventDefault();
                                self.togglePlayPause();
                                break;
                            case 'ArrowUp':
                                e.preventDefault();
                                self.scrollSpeed = clamp((parseInt(self.scrollSpeed, 10) || 4) + 1, 1, 10);
                                break;
                            case 'ArrowDown':
                                e.preventDefault();
                                self.scrollSpeed = clamp((parseInt(self.scrollSpeed, 10) || 4) - 1, 1, 10);
                                break;
                            case 'ArrowLeft':
                                e.preventDefault();
                                self.decreaseReadingFontSize();
                                break;
                            case 'ArrowRight':
                                e.preventDefault();
                                self.increaseReadingFontSize();
                                break;
                            case 'f':
                            case 'F':
                                e.preventDefault();
                                self._toggleFullscreen('#prompteur-fullscreen-target');
                                break;
                            case 'Escape':
                                if (document.fullscreenElement || document.webkitFullscreenElement) {
                                    self._exitFullscreen();
                                }
                                break;
                            default:
                                break;
                        }
                    });
                }
            };
        });
    });
})();
