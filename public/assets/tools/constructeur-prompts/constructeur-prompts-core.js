// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// constructeur-prompts-core.js — logique Alpine.js du wizard « Constructeur de prompts » (4 étapes).
// Extrait de resources/views/public/tools/constructeur-prompts.blade.php (Phase 0 audit 2026-07-26)
// pour permettre au navigateur de mettre ce JS en cache (auparavant inline, jamais caché).
// Les données dynamiques (personas/verbes/audiences configurables + i18n) sont injectées par
// le Blade via window.promptBuilderConfig (même pattern que window.taxConfig sur calculatrice-taxes).
// Fichier servi tel quel (pas de build Vite pour Modules/Tools — même convention que
// minuteur-visuel-core.js, anonymizer-core.js, etc.) : cache-busting via ?v={semver} en query string.
document.addEventListener('alpine:init', function() {
    Alpine.data('promptBuilder', function() {
        return {
            step: 1,
            resetArmed: false,
            // Phase 1 (audit 2026-07-26) : entrée par l'intention. selectedTask = id de la carte
            // choisie à l'étape 1 ; taskCards = taxonomie de tâches → mapping persona/verbe,
            // injectée par le Blade via window.promptBuilderConfig (même contrat que personas/verbes).
            selectedTask: '',
            taskCards: (window.promptBuilderConfig && window.promptBuilderConfig.taskCards) || [],
            // Phase 2 : divulgation progressive à 2 niveaux — panneau replié par défaut, rien de retiré.
            showAdvanced: false,
            personaType: 'preset',
            personaPreset: '',
            personaCustom: '',
            personas: (window.promptBuilderConfig && window.promptBuilderConfig.personas) || [],
            verbType: 'preset',
            verb: '',
            verbs: (window.promptBuilderConfig && window.promptBuilderConfig.verbs) || [],
            verbCustom: '',
            taskObject: '',
            audienceType: 'preset',
            audiencePreset: '',
            audiencePresets: [],
            audienceCustom: '',
            audiences: (window.promptBuilderConfig && window.promptBuilderConfig.audiences) || [],
            format: '',
            length: '',
            tone: '',
            language: 'fr',
            constraintAntiAI: true,
            constraintTypo: false,
            constraintCanvas: false,
            canvasAI: 'chatgpt',
            canvasFormat: '',
            canvasCustomFormat: '',
            // 2026-05-05 #104 : formats officiels mai 2026 (sources Anthropic Oct 2025, OpenAI 2026, Google AI 2025-2026, Mistral)
            canvasFormatMap: {
                chatgpt: ['Markdown', 'PDF', 'DOCX (Word)', 'Code (Python/JS/SQL)', 'Tableau interactif', 'Python exécutable'],
                claude: ['Markdown', 'HTML', 'SVG', 'React (.jsx)', 'Mermaid', 'Code', 'DOCX (Word)', 'PDF', 'XLSX (tableur)', 'PPTX (slides)'],
                gemini: ['Google Docs', 'Google Slides', 'PDF', 'Code (Colab)', 'App embarquée', 'Quiz/Infographie', 'Markdown'],
                mistral: ['Markdown', 'HTML', 'Code', 'Diagramme'],
            },
            formatMode: 'preset', // 2026-05-05 #104 : 'preset' (liste prédéfinie selon IA) ou 'custom' (champ texte libre universel)
            get canvasFormats() { return this.canvasFormatMap[this.canvasAI] || []; },
            constraintChainOfThought: false,
            constraintAskIfUnclear: false,
            constraintCustom: '',
            technique: 'zero-shot',
            examples: '',
            useDelimiters: false,
            showHelp: {},
            helps: {
                persona: 'Donner un rôle à l\'IA aide à orienter ses réponses selon une expertise ou un style spécifique. Ex: « Tu es un expert marketing » donnera des réponses plus stratégiques.',
                verb: 'Choisir un verbe d\'action précise ce que l\'IA doit faire : rédiger, analyser, résumer, créer... Le verbe détermine le type de résultat.',
                taskObject: 'Décrivez clairement et précisément ce que l\'IA doit produire. Plus vous donnez de contexte et de détails, meilleur sera le résultat.',
                audience: 'Spécifier le public aide l\'IA à adapter son langage. Un texte pour des débutants sera différent d\'un texte pour des experts.',
                format: 'Le format guide la structure de la réponse. Une liste à puces est facile à lire, un tableau est bon pour comparer, un plan est idéal pour organiser.',
                length: 'Indiquer une longueur permet de contrôler si la réponse est concise (pour un résumé) ou détaillée (pour un article complet).',
                tone: 'Le ton change le style : professionnel pour un rapport, chaleureux pour un courriel client, académique pour un mémoire.',
                technique: 'Réponse directe : l\'IA répond tout de suite sans exemple. Avec des exemples : vous lui donnez 2-3 modèles à suivre. Réflexion étape par étape : l\'IA détaille son raisonnement avant de conclure (meilleur pour la logique et les calculs). Par étapes : l\'IA valide chaque étape avec vous avant de continuer.',
                delimiters: 'Les délimiteurs (###) séparent vos instructions de vos données. Utile quand vous analysez un texte spécifique — l\'IA sait où commence le texte à analyser.',
                constraintAntiAI: 'L\'IA a tendance à produire des textes génériques reconnaissables. Cette option force un style plus naturel, varié et authentiquement humain.',
                constraintCanvas: 'Canvas (ChatGPT) et artefact (Claude) sont des espaces de travail dédiés où l\'IA crée du contenu que vous pouvez modifier directement.',
                constraintChainOfThought: 'La chaîne de pensée force l\'IA à montrer son raisonnement, pas juste le résultat. Très utile pour les problèmes complexes, les mathématiques ou la logique.',
                constraintAskIfUnclear: 'Au lieu de deviner, l\'IA vous posera des questions de clarification. Résultat : des réponses beaucoup plus pertinentes dès le premier essai.'
            },
            copied: false,
            showValidation: false,
            saveName: '',
            saving: false,
            saveError: '',
            isAuthenticated: !!(window.promptBuilderConfig && window.promptBuilderConfig.isAuthenticated),
            hasLocalData: false,
            _editingId: null,
            history: [],

            get isValid() {
                var hasVerb = this.verbType === 'custom' ? !!this.verbCustom : !!this.verb;
                return this.personaText.length > 0 && this.taskObject.length > 0 && hasVerb;
            },

            get personaText() {
                if (this.personaType === 'custom' && this.personaCustom) return this.personaCustom;
                if (this.personaType === 'preset' && this.personaPreset) {
                    for (var i = 0; i < this.personas.length; i++) {
                        if (this.personas[i].value === this.personaPreset) return this.personas[i].label;
                    }
                }
                return '';
            },

            get audienceText() {
                if (this.audienceType === 'none') return '';
                if (this.audienceType === 'custom' && this.audienceCustom) return this.audienceCustom;
                if (this.audienceType === 'preset' && this.audiencePresets.length > 0) {
                    var selectedLabels = [];
                    for (var i = 0; i < this.audiences.length; i++) {
                        if (this.audiencePresets.includes(this.audiences[i].value)) selectedLabels.push(this.audiences[i].label);
                    }
                    if (selectedLabels.length === 1) return selectedLabels[0];
                    if (selectedLabels.length === 2) return selectedLabels.join(' et ');
                    if (selectedLabels.length >= 3) { var last = selectedLabels.pop(); return selectedLabels.join(', ') + ' et ' + last; }
                }
                return '';
            },

            get selectedTaskLabel() {
                for (var i = 0; i < this.taskCards.length; i++) {
                    if (this.taskCards[i].id === this.selectedTask) return this.taskCards[i].label;
                }
                return '';
            },

            // Aperçu en langage courant (Phase 2) : composé à partir des MÊMES données que le
            // générateur de prompt ci-dessous, sans dupliquer ni modifier sa logique d'assemblage.
            get promptSummary() {
                var parts = [];
                var actionVerb = this.verbType === 'custom' ? this.verbCustom : this.verb;
                if (this.personaText) {
                    var summaryArticle = /^\s*(un |une |des |le |la |l'|d'|du |de )/i.test(this.personaText) ? '' : 'un(e) ';
                    parts.push('L\'IA va se comporter comme ' + summaryArticle + this.personaText.charAt(0).toLowerCase() + this.personaText.slice(1) + '.');
                }
                if (actionVerb && this.taskObject) {
                    parts.push('Elle va ' + actionVerb.toLowerCase() + ' ' + this.taskObject + '.');
                } else if (this.taskObject) {
                    parts.push('Sujet : ' + this.taskObject + '.');
                }
                if (this.audienceText) parts.push('Le résultat sera adapté pour : ' + this.audienceText + '.');
                if (this.tone) parts.push('Ton : ' + this.tone + '.');
                if (this.format) parts.push('Présenté sous forme de : ' + this.format.toLowerCase() + '.');
                if (this.length) parts.push('Longueur visée : ' + this.length.toLowerCase() + '.');
                if (!parts.length) return '';
                return parts.join(' ');
            },

            get prompt() {
                var sections = [];
                var actionVerb = this.verbType === 'custom' ? this.verbCustom : this.verb;

                // === RÔLE (enrichi) ===
                if (this.personaText) {
                    var roleArticle = /^\s*(un |une |des |le |la |l'|d'|du |de )/i.test(this.personaText) ? '' : 'un(e) ';
                    sections.push('Tu es ' + roleArticle + this.personaText + ' avec une expertise approfondie dans ton domaine. Tu communiques de manière claire et efficace, en adaptant ton niveau de langage à ton audience.');
                }

                // === TÂCHE ===
                if (actionVerb && this.taskObject) {
                    sections.push('Ta tâche : ' + actionVerb + ' ' + this.taskObject + '.');
                } else if (this.taskObject) {
                    sections.push('Ta tâche : ' + this.taskObject + '.');
                }

                // === AUDIENCE ===
                if (this.audienceText) {
                    sections.push('Audience cible : ' + this.audienceText + '. Adapte ton vocabulaire, tes exemples et ton niveau de détail en conséquence. Assure-toi que le contenu soit pertinent et accessible pour ce public.');
                }

                // === FORMAT DE SORTIE ===
                var outputRules = [];
                if (this.format) outputRules.push('Structure : ' + this.format);
                if (this.length) outputRules.push('Longueur visée : ' + this.length);
                if (this.tone) outputRules.push('Ton et style : ' + this.tone);
                if (this.language === 'en') outputRules.push('Langue de rédaction : anglais');
                if (this.language === 'es') outputRules.push('Langue de rédaction : espagnol');
                if (outputRules.length > 0) {
                    sections.push('Format de la réponse :\n- ' + outputRules.join('\n- '));
                }

                // === CONTRAINTES ===
                var constraints = [];
                if (this.constraintAntiAI) constraints.push('Écriture naturelle et humaine : varie la longueur des phrases, utilise des expressions authentiques et des transitions fluides. Évite les formulations génériques (« dans un monde en constante évolution »), les listes à puces systématiques et les répétitions de structure.');
                if (this.constraintTypo) constraints.push('Typographie française stricte : majuscules en début de phrase et noms propres uniquement, pas de tiret cadratin (utilise le tiret court), ponctuation correcte, accents toujours présents.');
                if (this.constraintCanvas) {
                    var canvasNames = { chatgpt: 'Canvas', claude: 'artefact', gemini: 'espace de travail', mistral: 'espace de travail' };
                    var canvasName = canvasNames[this.canvasAI] || 'espace de travail';
                    var canvasLine = 'Crée un nouveau ' + canvasName + ' pour ta réponse.';
                    // 2026-05-05 #104 : format custom universel (formatMode) - dispo pour les 4 IA
                    var fmt = this.formatMode === 'custom' ? this.canvasCustomFormat : this.canvasFormat;
                    if (fmt) canvasLine += ' Format de sortie : ' + fmt + '.';
                    constraints.push(canvasLine);
                }
                if (this.constraintChainOfThought) constraints.push('Montre ton raisonnement complet étape par étape avant de formuler ta réponse finale.');
                if (this.constraintAskIfUnclear) constraints.push('Si un élément de ma demande est ambigu ou manque de contexte, pose-moi des questions de clarification avant de commencer. Ne devine pas — demande.');
                if (this.constraintCustom) constraints.push(this.constraintCustom);
                if (constraints.length > 0) {
                    sections.push('Contraintes à respecter :\n- ' + constraints.join('\n- '));
                }

                // === CRITÈRES DE QUALITÉ ===
                var quality = [];
                if (this.tone) quality.push('le ton demandé est respecté du début à la fin');
                if (this.audienceText) quality.push('le contenu est adapté à l\'audience cible');
                if (this.length) quality.push('la longueur correspond à ce qui est demandé');
                if (this.constraintAntiAI) quality.push('le texte ne ressemble pas à du contenu généré par IA');
                if (quality.length > 0) {
                    sections.push('Avant de finaliser, vérifie que :\n- ' + quality.join('\n- '));
                }

                // === DÉLIMITEURS ===
                if (this.useDelimiters) {
                    sections.push('Utilise des délimiteurs ### pour séparer clairement chaque section de ta réponse.');
                }

                // === TECHNIQUE ===
                if (this.technique === 'zero-shot-cot') {
                    sections.push('Avant de répondre, réfléchis étape par étape à ta stratégie (ne montre pas ce raisonnement dans ta réponse finale).');
                }
                if ((this.technique === 'few-shot' || this.technique === 'few-shot-cot') && this.examples) {
                    sections.push('Voici des exemples pour guider ta réponse :\n\n' + this.examples);
                    if (this.technique === 'few-shot-cot') {
                        sections.push('Applique le même type de raisonnement détaillé que dans les exemples ci-dessus.');
                    }
                }
                if (this.technique === 'iterative') {
                    sections.push('Procède étape par étape. Après chaque étape majeure, présente ton travail et demande ma validation avant de continuer.');
                }

                return sections.join('\n\n');
            },

            get wizardParams() {
                return { personaType: this.personaType, personaPreset: this.personaPreset, personaCustom: this.personaCustom, verbType: this.verbType, verb: this.verb, verbCustom: this.verbCustom, taskObject: this.taskObject, audienceType: this.audienceType, audiencePreset: this.audiencePreset, audiencePresets: this.audiencePresets, audienceCustom: this.audienceCustom, format: this.format, length: this.length, tone: this.tone, language: this.language, technique: this.technique, constraintAntiAI: this.constraintAntiAI, constraintCanvas: this.constraintCanvas, canvasAI: this.canvasAI, canvasFormat: this.canvasFormat, formatMode: this.formatMode, canvasCustomFormat: this.canvasCustomFormat };
            },
            _headers: function() {
                return { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' };
            },
            init: function() {
                var self = this;
                if (this.isAuthenticated) {
                    fetch('/api/prompts', { headers: this._headers() })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            self.history = (data.data || []).map(function(item) {
                                return { id: item.public_id || item.id, prompt: item.prompt_text, name: item.name, date: new Date(item.created_at).toLocaleString('fr-CA'), params: item.params };
                            });
                            if (localStorage.getItem('pb_history')) self.hasLocalData = true;
                        })
                        .catch(function() {
                            try { self.history = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { self.history = []; }
                        });
                    // Charger un prompt existant pour edition (?edit=ID)
                    var editId = new URLSearchParams(window.location.search).get('edit');
                    if (editId) {
                        fetch('/api/prompts', { headers: self._headers() })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                var found = (data.data || []).find(function(p) { return (p.public_id || p.id) == editId; });
                                if (found && found.params) {
                                    var p = found.params;
                                    if (p.personaType) self.personaType = p.personaType;
                                    if (p.personaPreset) self.personaPreset = p.personaPreset;
                                    if (p.personaCustom) { self.personaCustom = p.personaCustom; self.personaType = 'custom'; }
                                    if (p.verbType) self.verbType = p.verbType;
                                    if (p.verb) self.verb = p.verb;
                                    if (p.verbCustom) { self.verbCustom = p.verbCustom; self.verbType = 'custom'; }
                                    if (p.taskObject) self.taskObject = p.taskObject;
                                    if (p.audienceType) self.audienceType = p.audienceType;
                                    if (Array.isArray(p.audiencePresets)) { self.audiencePresets = p.audiencePresets; } else if (p.audiencePreset) { self.audiencePresets = [p.audiencePreset]; }
                                    if (p.audiencePreset) self.audiencePreset = p.audiencePreset;
                                    if (p.audienceCustom) { self.audienceCustom = p.audienceCustom; self.audienceType = 'custom'; }
                                    if (p.format) self.format = p.format;
                                    if (p.length) self.length = p.length;
                                    if (p.tone) self.tone = p.tone;
                                    if (p.language) self.language = p.language;
                                    if (p.technique) self.technique = p.technique;
                                    if (p.constraintAntiAI !== undefined) self.constraintAntiAI = p.constraintAntiAI;
                                    if (p.constraintCanvas) self.constraintCanvas = p.constraintCanvas;
                                    if (p.canvasAI) {
                                        // 2026-05-05 #104 : migration anciens prompts canvasAI='custom' → canvasAI='chatgpt' + formatMode='custom'
                                        if (p.canvasAI === 'custom') { self.canvasAI = 'chatgpt'; self.formatMode = 'custom'; }
                                        else self.canvasAI = p.canvasAI;
                                    }
                                    if (p.canvasFormat) self.canvasFormat = p.canvasFormat;
                                    if (p.canvasCustomFormat) self.canvasCustomFormat = p.canvasCustomFormat;
                                    if (p.formatMode) self.formatMode = p.formatMode;
                                    self.saveName = found.name;
                                    // Prompt existant chargé pour édition : on saute l'étape « objectif »
                                    // (déjà répondue par un précédent passage) et on ouvre directement
                                    // les réglages avancés, car un prompt sauvegardé utilise typiquement
                                    // des valeurs personnalisées qui vivent dans ce panneau.
                                    self.selectedTask = self.selectedTask || 'autre';
                                    self.showAdvanced = true;
                                    self.step = 2;
                                    self._editingId = found.id;
                                }
                            });
                    }
                } else {
                    try { this.history = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { this.history = []; }
                }
            },

            // Phase 1 : clic sur une carte d'objectif → pré-sélection intelligente de la persona et
            // du verbe (mapping simple, pas d'IA), puis avance à l'étape suivante. Le générateur de
            // prompt (get prompt()) n'est jamais touché : on ne fait qu'assigner ses entrées en amont.
            selectTask: function(card) {
                this.selectedTask = card.id;
                if (card.personaValue) { this.personaType = 'preset'; this.personaPreset = card.personaValue; }
                if (card.verb) { this.verbType = 'preset'; this.verb = card.verb; }
                this.nextStep();
            },

            nextStep: function() {
                if (this.step === 1 && !this.selectedTask) { this.showValidation = true; return; }
                this.showValidation = false;
                if (this.step < 2) this.step++;
            },
            canGoToStep: function(s) {
                if (s <= 1) return true;
                if (s >= 2 && !this.selectedTask) return false;
                return true;
            },
            goToStep: function(s) {
                if (this.canGoToStep(s)) { this.showValidation = false; this.step = s; }
                else { this.showValidation = true; }
            },
            prevStep: function() { if (this.step > 1) this.step--; },

            copy: function() {
                var self = this;
                navigator.clipboard.writeText(this.prompt);
                this.track('prompt_copy', { tool: 'constructeur-prompts' });
                this.copied = true;
                try { window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: 'Prompt copié !', variant: 'success', duration: 2000 } })); } catch (e) {}
                setTimeout(function() { self.copied = false; }, 2000);
            },

            track: function(event, params) {
                try {
                    if (typeof window.gtag === 'function') {
                        window.gtag('event', event, params || {});
                    }
                } catch (e) {}
            },

            openIn: function(target) {
                if (!this.prompt) return;
                try {
                    navigator.clipboard.writeText(this.prompt);
                } catch (e) {}
                var baseUrl = '';
                switch (target) {
                    case 'chatgpt':
                        baseUrl = 'https://chatgpt.com/?q=';
                        break;
                    case 'claude':
                        baseUrl = 'https://claude.ai/new?q=';
                        break;
                    case 'perplexity':
                        baseUrl = 'https://www.perplexity.ai/search?q=';
                        break;
                    case 'gemini':
                        baseUrl = 'https://gemini.google.com/app';
                        break;
                    default:
                        return;
                }
                var encodedPrompt = encodeURIComponent(this.prompt);
                var url = baseUrl;
                var msg = 'Prompt copié — ouverture de la conversation…';
                if (target === 'gemini') {
                    // Gemini ne pré-remplit pas via URL → on ouvre l'app, le prompt est copié.
                    msg = 'Prompt copié — colle-le dans Gemini (Ctrl/Cmd + V).';
                } else if (encodedPrompt.length <= 4000) {
                    url += encodedPrompt;
                } else {
                    msg = 'Prompt trop long pour le lien : il est copié, colle-le (Ctrl/Cmd + V).';
                }
                try { window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: msg, variant: 'info', duration: 3500 } })); } catch (e) {}
                this.track('prompt_open_in', { tool: 'constructeur-prompts', target: target });
                window.open(url, '_blank', 'noopener');
            },

            copyText: function(text) { window.copyToClipboard(text, (window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.promptCopied) || 'Prompt copié'); },

            armReset: function() {
                if (this.resetArmed) { this.resetAll(); return; }
                this.resetArmed = true;
                var self = this;
                setTimeout(function() { self.resetArmed = false; }, 4000);
            },
            resetAll: function() { window.location.href = window.location.pathname; },

            addToHistory: function() {
                if (this.saving) return;
                var self = this;
                var title = this.saveName.trim() || this.personaText || 'Prompt';
                if (this.isAuthenticated) {
                    this.saving = true;
                    var isEdit = !!this._editingId;
                    var url = isEdit ? '/api/prompts/' + this._editingId : '/api/prompts';
                    var method = isEdit ? 'PUT' : 'POST';
                    fetch(url, {
                        method: method, headers: this._headers(),
                        body: JSON.stringify({ name: title, prompt_text: this.prompt, params: this.wizardParams })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (isEdit) {
                            var pid = data.public_id || data.id;
                            var idx = self.history.findIndex(function(h) { return h.id == pid; });
                            if (idx >= 0) self.history[idx] = { id: pid, prompt: data.prompt_text, name: data.name, date: new Date(data.updated_at).toLocaleString('fr-CA'), params: data.params };
                            self._editingId = null;
                        } else {
                            self.history.unshift({ id: data.public_id || data.id, prompt: data.prompt_text, name: data.name, date: new Date(data.created_at).toLocaleString('fr-CA'), params: data.params });
                        }
                        self.saveName = '';
                        self.saving = false;
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: (window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.promptSaved) || 'Prompt sauvegardé' } }));
                    })
                    .catch(function() { self.saving = false; self.saveError = (window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.saveError) || 'Erreur de sauvegarde. Réessayez.'; setTimeout(function() { self.saveError = ''; }, 4000); });
                } else {
                    this.$dispatch('open-auth-modal');
                }
            },
            deletePrompt: function(id, index) {
                var self = this;
                if (this.isAuthenticated && id) {
                    fetch('/api/prompts/' + id, { method: 'DELETE', headers: this._headers() })
                        .then(function() { self.history.splice(index, 1); })
                        .catch(console.error);
                } else {
                    this.history.splice(index, 1);
                    localStorage.setItem('pb_history', JSON.stringify(this.history));
                }
            },
            importLocalStorage: function() {
                var self = this;
                var local = [];
                try { local = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { return; }
                var promises = local.map(function(item) {
                    return fetch('/api/prompts', {
                        method: 'POST', headers: self._headers(),
                        body: JSON.stringify({ name: item.name || 'Prompt importé', prompt_text: item.prompt, params: {} })
                    }).then(function(r) { return r.json(); });
                });
                Promise.all(promises).then(function(results) {
                    results.forEach(function(data) {
                        self.history.push({ id: data.id, prompt: data.prompt_text, name: data.name, date: new Date(data.created_at).toLocaleString('fr-CA'), params: data.params });
                    });
                    localStorage.removeItem('pb_history');
                    self.hasLocalData = false;
                });
            },
            clearHistory: function() { this.history = []; if (!this.isAuthenticated) localStorage.removeItem('pb_history'); },

            exportPrompt: function() {
                var blob = new Blob([this.prompt], { type: 'text/plain' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'prompt.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        };
    });
});
