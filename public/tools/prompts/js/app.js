/**
 * Générateur de prompts IA v2
 * Toutes les données sont modifiables (y compris les valeurs par défaut)
 */

// ============================================
// STORAGE MANAGER
// ============================================
const StorageManager = {
    STORAGE_KEY: 'prompt_generator_data_v2',

    save(data) {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(data));
            return true;
        } catch (error) {
            console.error('Erreur de sauvegarde:', error);
            return false;
        }
    },

    load() {
        try {
            const data = localStorage.getItem(this.STORAGE_KEY);
            return data ? JSON.parse(data) : null;
        } catch (error) {
            console.error('Erreur de chargement:', error);
            return null;
        }
    },

    clear() {
        try {
            localStorage.removeItem(this.STORAGE_KEY);
            return true;
        } catch (error) {
            return false;
        }
    }
};

// ============================================
// DATA MANAGER
// ============================================
const DataManager = {
    // Valeurs par défaut (utilisées pour initialisation et reset)
    defaultData: {
        personas: [
            { id: 'p1', value: 'expert en marketing digital', label: 'Expert en marketing digital' },
            { id: 'p2', value: 'rédacteur web professionnel', label: 'Rédacteur web professionnel' },
            { id: 'p3', value: 'enseignant pédagogue', label: 'Enseignant pédagogue' },
            { id: 'p4', value: 'développeur senior', label: 'Développeur senior' },
            { id: 'p5', value: 'consultant en stratégie d\'entreprise', label: 'Consultant en stratégie' },
            { id: 'p6', value: 'graphiste créatif', label: 'Graphiste créatif' },
            { id: 'p7', value: 'analyste de données', label: 'Analyste de données' },
            { id: 'p8', value: 'gestionnaire de projet', label: 'Gestionnaire de projet' },
            { id: 'p9', value: 'coach professionnel', label: 'Coach professionnel' },
            { id: 'p10', value: 'journaliste d\'investigation', label: 'Journaliste d\'investigation' }
        ],
        verbes: [
            { id: 'v1', value: 'Rédige', label: 'Rédige' },
            { id: 'v2', value: 'Analyse', label: 'Analyse' },
            { id: 'v3', value: 'Crée', label: 'Crée' },
            { id: 'v4', value: 'Génère', label: 'Génère' },
            { id: 'v5', value: 'Explique', label: 'Explique' },
            { id: 'v6', value: 'Compare', label: 'Compare' },
            { id: 'v7', value: 'Résume', label: 'Résume' },
            { id: 'v8', value: 'Traduis', label: 'Traduis' },
            { id: 'v9', value: 'Optimise', label: 'Optimise' },
            { id: 'v10', value: 'Évalue', label: 'Évalue' },
            { id: 'v11', value: 'Développe', label: 'Développe' },
            { id: 'v12', value: 'Conçois', label: 'Conçois' }
        ],
        structures: [
            { id: 's1', value: 'liste à puces', label: 'Liste à puces' },
            { id: 's2', value: 'paragraphes détaillés', label: 'Paragraphes détaillés' },
            { id: 's3', value: 'tableau structuré', label: 'Tableau structuré' },
            { id: 's4', value: 'plan hiérarchisé', label: 'Plan hiérarchisé' },
            { id: 's5', value: 'format JSON', label: 'Format JSON' },
            { id: 's6', value: 'étapes numérotées', label: 'Étapes numérotées' }
        ],
        longueurs: [
            { id: 'l1', value: 'concis (100-200 mots)', label: 'Concis (100-200 mots)' },
            { id: 'l2', value: 'modéré (300-500 mots)', label: 'Modéré (300-500 mots)' },
            { id: 'l3', value: 'détaillé (500-800 mots)', label: 'Détaillé (500-800 mots)' },
            { id: 'l4', value: 'exhaustif (800+ mots)', label: 'Exhaustif (800+ mots)' },
            { id: 'l5', value: '3 à 5 points clés', label: '3 à 5 points clés' },
            { id: 'l6', value: '5 à 10 points clés', label: '5 à 10 points clés' }
        ],
        tons: [
            { id: 't1', value: 'professionnel', label: 'Professionnel' },
            { id: 't2', value: 'accessible et pédagogique', label: 'Accessible et pédagogique' },
            { id: 't3', value: 'technique et précis', label: 'Technique et précis' },
            { id: 't4', value: 'chaleureux et engageant', label: 'Chaleureux et engageant' },
            { id: 't5', value: 'formel et académique', label: 'Formel et académique' },
            { id: 't6', value: 'créatif et dynamique', label: 'Créatif et dynamique' },
            { id: 't7', value: 'conversationnel', label: 'Conversationnel' },
            { id: 't8', value: 'persuasif', label: 'Persuasif' }
        ],
        contraintes: [
            { id: 'c1', value: 'Éviter le jargon technique', label: 'Éviter le jargon technique' },
            { id: 'c2', value: 'Inclure des exemples concrets', label: 'Inclure des exemples concrets' },
            { id: 'c3', value: 'Respecter les normes SEO', label: 'Respecter les normes SEO' },
            { id: 'c4', value: 'Utiliser un vocabulaire simple', label: 'Utiliser un vocabulaire simple' },
            { id: 'c5', value: 'Structurer avec des sous-titres', label: 'Structurer avec des sous-titres' }
        ],
        audiences: [
            { id: 'a1', value: 'professionnels du secteur', label: 'Professionnels du secteur' },
            { id: 'a2', value: 'débutants sans connaissances préalables', label: 'Débutants' },
            { id: 'a3', value: 'entrepreneurs et dirigeants', label: 'Entrepreneurs et dirigeants' },
            { id: 'a4', value: 'étudiants universitaires', label: 'Étudiants universitaires' },
            { id: 'a5', value: 'grand public', label: 'Grand public' }
        ]
    },

    // Données actives (modifiables)
    data: null,

    /**
     * Initialise les données
     * Si aucune sauvegarde, utilise les valeurs par défaut
     */
    init() {
        var saved = StorageManager.load();
        if (saved && saved.data) {
            this.data = saved.data;
        } else {
            // Premier lancement: copier les valeurs par défaut
            this.data = JSON.parse(JSON.stringify(this.defaultData));
            this.save();
        }
    },

    /**
     * Récupère tous les éléments d'une catégorie
     */
    getAll(category) {
        return this.data[category] || [];
    },

    /**
     * Ajoute un élément
     */
    add(category, item) {
        if (!this.data[category]) {
            this.data[category] = [];
        }

        var newItem = {
            id: category + '_' + Date.now(),
            value: item.value,
            label: item.label || item.value
        };

        this.data[category].push(newItem);
        this.save();
        return newItem;
    },

    /**
     * Modifie un élément
     */
    update(category, id, updates) {
        var items = this.data[category];
        if (!items) return false;

        var index = items.findIndex(function(item) { return item.id === id; });
        if (index === -1) return false;

        items[index] = Object.assign({}, items[index], updates);
        this.save();
        return true;
    },

    /**
     * Supprime un élément
     */
    delete(category, id) {
        var items = this.data[category];
        if (!items) return false;

        var index = items.findIndex(function(item) { return item.id === id; });
        if (index === -1) return false;

        items.splice(index, 1);
        this.save();
        return true;
    },

    /**
     * Trouve un élément par ID
     */
    findById(category, id) {
        var items = this.data[category];
        if (!items) return null;
        return items.find(function(item) { return item.id === id; }) || null;
    },

    /**
     * Sauvegarde les données
     */
    save() {
        StorageManager.save({ data: this.data, version: '2.0' });
    },

    /**
     * Exporte toutes les données
     */
    export() {
        return {
            version: '2.0',
            exportDate: new Date().toISOString(),
            data: this.data
        };
    },

    /**
     * Importe des données
     */
    import(importedData, merge) {
        try {
            var dataToImport = importedData.data || importedData.custom;
            if (!dataToImport) return false;

            if (merge) {
                var self = this;
                Object.keys(dataToImport).forEach(function(category) {
                    if (!self.data[category]) {
                        self.data[category] = [];
                    }
                    dataToImport[category].forEach(function(item) {
                        var exists = self.data[category].some(function(existing) {
                            return existing.value === item.value;
                        });
                        if (!exists) {
                            self.data[category].push({
                                id: category + '_' + Date.now() + '_' + Math.random().toString(36).substring(2, 11),
                                value: item.value,
                                label: item.label || item.value
                            });
                        }
                    });
                });
            } else {
                this.data = dataToImport;
            }

            this.save();
            return true;
        } catch (error) {
            console.error('Erreur d\'import:', error);
            return false;
        }
    },

    /**
     * Réordonne les éléments d'une catégorie
     */
    reorder(category, fromIndex, toIndex) {
        var items = this.data[category];
        if (!items || fromIndex < 0 || toIndex < 0 || fromIndex >= items.length || toIndex >= items.length) {
            return false;
        }
        var item = items.splice(fromIndex, 1)[0];
        items.splice(toIndex, 0, item);
        this.save();
        return true;
    },

    /**
     * Réinitialise avec les valeurs par défaut
     */
    reset() {
        this.data = JSON.parse(JSON.stringify(this.defaultData));
        this.save();
    },

    /**
     * Vide toutes les données
     */
    clearAll() {
        this.data = {
            personas: [],
            verbes: [],
            structures: [],
            longueurs: [],
            tons: [],
            contraintes: [],
            audiences: []
        };
        this.save();
    }
};

// ============================================
// PROMPT GENERATOR
// ============================================
const PromptGenerator = {
    ANTI_AI_CONSTRAINT: "Style d'écriture : Adopte un style d'écriture authentiquement humain. Varie la longueur des phrases de manière naturelle. Utilise occasionnellement des expressions idiomatiques. Évite les formulations génériques et les listes systématiques. Privilégie un flux narratif naturel avec des transitions fluides. Ne commence jamais par \"Bien sûr\" ou \"Certainement\". Évite les superlatifs excessifs et les formulations trop polies. Sois direct et concis.",

    TYPO_RULES: "Règles typographiques : Majuscules uniquement en début de phrase et pour les noms propres. Pas de majuscules inutiles dans les titres. Orthographe et ponctuation correctes. Pas de tiret cadratin.",

    CANVAS_ARTIFACT: "Présente le résultat dans un nouveau Canvas ou Artefact dédié, distinct de tout contenu précédent.",

    generate(params) {
        var prompt = 'Agis comme un ' + params.persona + '. ' + params.verbe + ' ' + params.objet + '.';

        var formatParts = [];
        if (params.structure) formatParts.push(params.structure);
        if (params.longueur) formatParts.push(params.longueur);
        if (params.ton) formatParts.push('ton ' + params.ton);

        if (formatParts.length > 0) {
            prompt += ' Format : ' + formatParts.join(', ') + '.';
        }

        prompt += ' Audience : ' + params.audience + '.';

        if (params.contraintes && params.contraintes.trim()) {
            prompt += ' Contraintes : ' + params.contraintes + '.';
        }

        if (params.useTypoRules) {
            prompt += ' ' + this.TYPO_RULES;
        }

        if (params.useAntiAI) {
            prompt += ' ' + this.ANTI_AI_CONSTRAINT;
        }

        if (params.useCanvasArtifact) {
            prompt += ' ' + this.CANVAS_ARTIFACT;
        }

        return prompt;
    },

    validate(params) {
        var errors = [];

        if (!params.persona || params.persona.trim() === '') {
            errors.push({ field: 'persona', message: 'La persona est requise' });
        }

        if (!params.verbe || params.verbe.trim() === '') {
            errors.push({ field: 'verbe', message: 'Le verbe d\'action est requis' });
        }

        if (!params.objet || params.objet.trim() === '') {
            errors.push({ field: 'objet', message: 'L\'objet de la tâche est requis' });
        }

        if (!params.audience || params.audience.trim() === '') {
            errors.push({ field: 'audience', message: 'L\'audience cible est requise' });
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    },

    evaluateQuality(params) {
        var score = 0;
        var feedback = [];

        if (params.persona && params.persona.length > 10) {
            score += 20;
        } else if (params.persona) {
            score += 10;
            feedback.push('Persona plus détaillée = meilleur résultat');
        }

        if (params.verbe) {
            score += 15;
        }

        if (params.objet && params.objet.length > 50) {
            score += 25;
        } else if (params.objet && params.objet.length > 20) {
            score += 15;
            feedback.push('Objet plus détaillé recommandé');
        } else if (params.objet) {
            score += 5;
            feedback.push('Décrivez l\'objet plus précisément');
        }

        if (params.audience && params.audience.length > 20) {
            score += 15;
        } else if (params.audience) {
            score += 10;
            feedback.push('Audience plus précise recommandée');
        }

        if (params.structure || params.longueur || params.ton) {
            score += 10;
        } else {
            feedback.push('Ajoutez un format pour plus de précision');
        }

        if (params.contraintes && params.contraintes.length > 0) {
            score += 10;
        }

        if (params.useAntiAI) {
            score += 5;
        }

        return { score: score, feedback: feedback };
    }
};

// ============================================
// UI MANAGER
// ============================================
const UIManager = {
    /**
     * Échappe les caractères HTML pour prévenir les attaques XSS
     */
    escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    toast(message, type, duration) {
        type = type || 'success';
        duration = duration || 3000;

        var existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.className = 'toast-notification toast-' + type;
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(function() { toast.classList.add('show'); });

        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 300);
        }, duration);
    },

    openModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    },

    closeAllModals() {
        document.querySelectorAll('.modal.show').forEach(function(modal) {
            modal.classList.remove('show');
        });
        document.body.style.overflow = '';
    },

    // Modal de confirmation (remplace confirm() du navigateur)
    confirm(options) {
        return new Promise(function(resolve) {
            var modal = document.getElementById('confirmModal');
            var titleEl = document.getElementById('confirm-title');
            var messageEl = document.getElementById('confirm-message');
            var cancelBtn = document.getElementById('confirm-cancel');
            var okBtn = document.getElementById('confirm-ok');

            // Configuration
            titleEl.textContent = options.title || 'Confirmation';
            messageEl.textContent = options.message || 'Confirmer cette action?';
            cancelBtn.textContent = options.cancelText || 'Annuler';
            okBtn.textContent = options.confirmText || 'Confirmer';

            // Style du bouton selon le type
            okBtn.className = options.danger ? 'btn-danger-fill' : 'btn-primary';

            // Nettoyage des anciens handlers
            var newCancelBtn = cancelBtn.cloneNode(true);
            var newOkBtn = okBtn.cloneNode(true);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);

            // Handlers
            var closeAndResolve = function(result) {
                UIManager.closeModal('confirmModal');
                resolve(result);
            };

            newCancelBtn.addEventListener('click', function() { closeAndResolve(false); });
            newOkBtn.addEventListener('click', function() { closeAndResolve(true); });

            // Fermer avec Escape
            var escHandler = function(e) {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', escHandler);
                    closeAndResolve(false);
                }
            };
            document.addEventListener('keydown', escHandler);

            // Clic en dehors
            modal.addEventListener('click', function handler(e) {
                if (e.target === modal) {
                    modal.removeEventListener('click', handler);
                    closeAndResolve(false);
                }
            });

            UIManager.openModal('confirmModal');
            newOkBtn.focus();
        });
    },

    updateSelect(selectId, items, placeholder) {
        var select = document.getElementById(selectId);
        if (!select) return;

        var currentValue = select.value;

        select.innerHTML = '<option value="">' + placeholder + '</option>' +
            items.map(function(item) {
                var selectedAttr = item.value === currentValue ? 'selected' : '';
                return '<option value="' + item.value + '" ' + selectedAttr + '>' + item.label + '</option>';
            }).join('');
    },

    renderEditableList(options) {
        var category = options.category;
        var items = options.items;
        var emptyMessage = options.emptyMessage;
        var self = this;

        if (items.length === 0) {
            return '<p class="empty-message">' + self.escapeHtml(emptyMessage) + '</p>';
        }

        return '<ul class="editable-list" data-category="' + self.escapeHtml(category) + '">' +
            items.map(function(item) {
                return '<li class="editable-item" data-id="' + self.escapeHtml(item.id) + '">' +
                    '<span class="item-label">' + self.escapeHtml(item.label) + '</span>' +
                    '<div class="item-actions">' +
                        '<button type="button" class="btn-icon btn-edit" data-action="edit" data-category="' + self.escapeHtml(category) + '" data-id="' + self.escapeHtml(item.id) + '" title="Modifier">' +
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                                '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>' +
                                '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>' +
                            '</svg>' +
                        '</button>' +
                        '<button type="button" class="btn-icon btn-duplicate" data-action="duplicate" data-category="' + self.escapeHtml(category) + '" data-id="' + self.escapeHtml(item.id) + '" title="Dupliquer">' +
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                                '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>' +
                                '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>' +
                            '</svg>' +
                        '</button>' +
                        '<button type="button" class="btn-icon btn-delete" data-action="delete" data-category="' + self.escapeHtml(category) + '" data-id="' + self.escapeHtml(item.id) + '" title="Supprimer">' +
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                                '<polyline points="3 6 5 6 21 6"/>' +
                                '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>' +
                            '</svg>' +
                        '</button>' +
                    '</div>' +
                '</li>';
            }).join('') +
        '</ul>';
    },

    copyToClipboard(text) {
        return navigator.clipboard.writeText(text).then(function() {
            return true;
        }).catch(function() {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            var success = document.execCommand('copy');
            document.body.removeChild(textarea);
            return success;
        });
    },

    scrollTo(element) {
        var el = typeof element === 'string' ? document.getElementById(element) : element;
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
};

// ============================================
// EXPORT MANAGER
// ============================================
const ExportManager = {
    exportJSON(data, filename) {
        filename = filename || 'prompt-generator-config';
        var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);

        var link = document.createElement('a');
        link.href = url;
        link.download = filename + '-' + this.getDateString() + '.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        URL.revokeObjectURL(url);
    },

    importJSON() {
        return new Promise(function(resolve, reject) {
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';

            input.onchange = function(e) {
                var file = e.target.files[0];
                if (!file) {
                    reject(new Error('Aucun fichier sélectionné'));
                    return;
                }

                var reader = new FileReader();

                reader.onload = function(event) {
                    try {
                        var data = JSON.parse(event.target.result);
                        resolve(data);
                    } catch (error) {
                        reject(new Error('Format de fichier invalide'));
                    }
                };

                reader.onerror = function() { reject(new Error('Erreur de lecture du fichier')); };
                reader.readAsText(file);
            };

            input.click();
        });
    },

    getDateString() {
        var now = new Date();
        return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
    },

    validate(data) {
        var errors = [];

        if (!data) {
            errors.push('Données vides ou invalides');
            return { isValid: false, errors: errors };
        }

        if (!data.data && !data.custom) {
            errors.push('Données manquantes');
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
};

// ============================================
// APPLICATION PRINCIPALE
// ============================================
const App = {
    currentEditCategory: null,
    currentEditId: null,

    init() {
        DataManager.init();
        this.initSidebar();
        this.initGeneratorForm();
        this.initCustomization();
        this.initModals();
        this.initExportImport();
        this.initFullscreen();
        this.refreshAllSelects();
        console.log('Application initialisée');
    },

    // Mode plein écran
    initFullscreen() {
        var self = this;
        var btn = document.getElementById('btn-fullscreen');
        if (!btn) return;

        btn.addEventListener('click', function() {
            self.toggleFullscreen();
        });

        // Écouter les changements de plein écran (Escape, etc.)
        document.addEventListener('fullscreenchange', function() {
            self.updateFullscreenButton();
        });
        document.addEventListener('webkitfullscreenchange', function() {
            self.updateFullscreenButton();
        });
    },

    toggleFullscreen() {
        var doc = document.documentElement;

        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            // Activer le plein écran
            if (doc.requestFullscreen) {
                doc.requestFullscreen();
            } else if (doc.webkitRequestFullscreen) {
                doc.webkitRequestFullscreen();
            }
            document.body.classList.add('fullscreen-mode');
        } else {
            // Désactiver le plein écran
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
            document.body.classList.remove('fullscreen-mode');
        }
    },

    updateFullscreenButton() {
        var btn = document.getElementById('btn-fullscreen');
        if (!btn) return;

        var isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;
        var iconExpand = btn.querySelector('.icon-expand');
        var iconCompress = btn.querySelector('.icon-compress');

        if (isFullscreen) {
            btn.classList.add('active');
            if (iconExpand) iconExpand.style.display = 'none';
            if (iconCompress) iconCompress.style.display = 'block';
            document.body.classList.add('fullscreen-mode');
        } else {
            btn.classList.remove('active');
            if (iconExpand) iconExpand.style.display = 'block';
            if (iconCompress) iconCompress.style.display = 'none';
            document.body.classList.remove('fullscreen-mode');
        }
    },

    // Sidebar de personnalisation
    initSidebar() {
        var self = this;
        var overlay = document.getElementById('sidebar-overlay');
        var settingsBtn = document.getElementById('btn-settings');
        var closeBtn = document.getElementById('btn-close-sidebar');

        // Ouvrir le sidebar
        if (settingsBtn) {
            settingsBtn.addEventListener('click', function() {
                self.openSidebar();
            });
        }

        // Fermer le sidebar
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                self.closeSidebar();
            });
        }

        // Fermer en cliquant sur l'overlay
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    self.closeSidebar();
                }
            });
        }

        // Fermer avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay.classList.contains('active')) {
                self.closeSidebar();
            }
        });

        // Accordéons des sections
        document.querySelectorAll('.sidebar-section-header').forEach(function(header) {
            header.addEventListener('click', function(e) {
                // Ne pas toggler si on clique sur le bouton +
                if (e.target.closest('.btn-add-item')) return;

                var section = header.closest('.sidebar-section');
                section.classList.toggle('open');
            });
        });
    },

    openSidebar() {
        var overlay = document.getElementById('sidebar-overlay');
        if (overlay) {
            // Fermer tous les accordéons
            document.querySelectorAll('.sidebar-section.open').forEach(function(section) {
                section.classList.remove('open');
            });

            overlay.classList.add('active');
            document.body.classList.add('sidebar-open');
            document.body.style.overflow = 'hidden';
            this.refreshCustomLists();
        }
    },

    closeSidebar() {
        var overlay = document.getElementById('sidebar-overlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            document.body.style.overflow = '';
        }
    },

    // Formulaire générateur
    initGeneratorForm() {
        var self = this;
        var form = document.getElementById('promptForm');
        if (!form) return;

        document.querySelectorAll('input[name="persona-type"]').forEach(function(radio) {
            radio.addEventListener('change', function() { self.togglePersonaType(); });
        });

        document.querySelectorAll('input[name="audience-type"]').forEach(function(radio) {
            radio.addEventListener('change', function() { self.toggleAudienceType(); });
        });

        var advancedToggle = document.querySelector('.toggle-button');
        if (advancedToggle) {
            advancedToggle.addEventListener('click', function() { self.toggleAdvanced(); });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            self.generatePrompt();
        });

        var copyBtn = document.querySelector('.copy-button');
        if (copyBtn) {
            copyBtn.addEventListener('click', function() { self.copyPrompt(); });
        }
    },

    togglePersonaType() {
        var type = document.querySelector('input[name="persona-type"]:checked').value;
        var predefinedWrapper = document.getElementById('predefined-wrapper');
        var customWrapper = document.getElementById('custom-wrapper');
        var predefinedSelect = document.getElementById('predefined-persona');
        var customInput = document.getElementById('custom-persona');

        if (type === 'predefined') {
            predefinedWrapper.style.display = 'block';
            customWrapper.style.display = 'none';
            customInput.value = '';
            // Gérer l'attribut required
            predefinedSelect.required = true;
            customInput.required = false;
        } else {
            predefinedWrapper.style.display = 'none';
            customWrapper.style.display = 'block';
            predefinedSelect.value = '';
            // Gérer l'attribut required
            predefinedSelect.required = false;
            customInput.required = true;
        }
    },

    toggleAudienceType() {
        var type = document.querySelector('input[name="audience-type"]:checked').value;
        var predefinedWrapper = document.getElementById('audience-predefined-wrapper');
        var customWrapper = document.getElementById('audience-custom-wrapper');
        var predefinedSelect = document.getElementById('predefined-audience');
        var customInput = document.getElementById('custom-audience');

        if (type === 'predefined') {
            if (predefinedWrapper) predefinedWrapper.style.display = 'block';
            if (customWrapper) customWrapper.style.display = 'none';
            if (customInput) customInput.value = '';
            // Gérer l'attribut required
            if (predefinedSelect) predefinedSelect.required = true;
            if (customInput) customInput.required = false;
        } else {
            if (predefinedWrapper) predefinedWrapper.style.display = 'none';
            if (customWrapper) customWrapper.style.display = 'block';
            if (predefinedSelect) predefinedSelect.value = '';
            // Gérer l'attribut required
            if (predefinedSelect) predefinedSelect.required = false;
            if (customInput) customInput.required = true;
        }
    },

    toggleAdvanced() {
        var btn = document.querySelector('.toggle-button');
        var options = document.getElementById('advanced-options');

        btn.classList.toggle('active');
        options.classList.toggle('show');
    },

    generatePrompt() {
        var self = this;
        var personaType = document.querySelector('input[name="persona-type"]:checked').value;
        var persona = personaType === 'predefined'
            ? document.getElementById('predefined-persona').value
            : document.getElementById('custom-persona').value.trim();

        var params = {
            persona: persona,
            verbe: document.getElementById('task-verb').value,
            objet: document.getElementById('task-object').value.trim(),
            audience: this.getAudienceValue(),
            structure: document.getElementById('format-structure').value,
            longueur: document.getElementById('format-length').value,
            ton: document.getElementById('format-tone').value,
            contraintes: document.getElementById('context-constraints').value.trim(),
            useTypoRules: document.getElementById('uppercase-rules').checked,
            useAntiAI: document.getElementById('anti-ai-style').checked,
            useCanvasArtifact: document.getElementById('canvas-artifact').checked
        };

        var validation = PromptGenerator.validate(params);
        if (!validation.isValid) {
            validation.errors.forEach(function(error) {
                self.showFieldError(error.field, error.message);
            });
            return;
        }

        var prompt = PromptGenerator.generate(params);

        document.getElementById('generatedPrompt').value = prompt;
        var resultContainer = document.getElementById('resultContainer');
        resultContainer.classList.add('show');

        var quality = PromptGenerator.evaluateQuality(params);
        this.updateQualityIndicator(quality);

        setTimeout(function() { UIManager.scrollTo(resultContainer); }, 100);
    },

    getAudienceValue() {
        var audienceType = document.querySelector('input[name="audience-type"]:checked');
        var type = audienceType ? audienceType.value : 'predefined';

        if (type === 'predefined') {
            return document.getElementById('predefined-audience').value;
        }
        return document.getElementById('custom-audience').value.trim();
    },

    updateQualityIndicator(quality) {
        var indicator = document.getElementById('quality-indicator');
        if (!indicator) return;

        var feedbackHtml = '';
        if (quality.feedback.length > 0) {
            feedbackHtml = '<ul class="quality-feedback">' +
                quality.feedback.map(function(f) { return '<li>' + f + '</li>'; }).join('') +
            '</ul>';
        }

        indicator.innerHTML = '<div class="quality-score">' +
            '<span class="score-value">' + quality.score + '%</span>' +
            '<span class="score-label">Qualité du prompt</span>' +
        '</div>' + feedbackHtml;
        indicator.style.display = 'block';
    },

    showFieldError(fieldId, message) {
        var fieldMap = {
            'persona': 'predefined-persona',
            'verbe': 'task-verb',
            'objet': 'task-object',
            'audience': 'predefined-audience'
        };

        // Gestion dynamique pour persona et audience selon le type sélectionné
        if (fieldId === 'persona') {
            var personaType = document.querySelector('input[name="persona-type"]:checked');
            if (personaType && personaType.value === 'custom') {
                fieldMap['persona'] = 'custom-persona';
            }
        }
        if (fieldId === 'audience') {
            var audienceType = document.querySelector('input[name="audience-type"]:checked');
            if (audienceType && audienceType.value === 'custom') {
                fieldMap['audience'] = 'custom-audience';
            }
        }

        var field = document.getElementById(fieldMap[fieldId] || fieldId);
        if (!field) return;

        field.classList.add('is-invalid');

        var existingError = field.parentElement.querySelector('.form-error');
        if (existingError) existingError.remove();

        var errorSpan = document.createElement('span');
        errorSpan.className = 'form-error';
        errorSpan.textContent = message;
        field.parentElement.appendChild(errorSpan);

        field.addEventListener('focus', function() {
            field.classList.remove('is-invalid');
            errorSpan.remove();
        }, { once: true });
    },

    copyPrompt() {
        var prompt = document.getElementById('generatedPrompt').value;
        UIManager.copyToClipboard(prompt).then(function(success) {
            if (success) {
                var copyBtn = document.querySelector('.copy-button');
                copyBtn.innerHTML = '<span>Copié!</span>';
                copyBtn.classList.add('copied');

                UIManager.toast('Prompt copié dans le presse-papiers!', 'success');

                setTimeout(function() {
                    copyBtn.innerHTML = '<span>Copier</span>';
                    copyBtn.classList.remove('copied');
                }, 2000);
            }
        });
    },

    // Personnalisation
    initCustomization() {
        var self = this;

        document.querySelectorAll('.btn-add-item').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var category = btn.dataset.category;
                self.openAddModal(category);
            });
        });

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action]');
            if (!btn) return;

            var action = btn.dataset.action;
            var category = btn.dataset.category;
            var id = btn.dataset.id;

            if (action === 'edit') {
                self.openEditModal(category, id);
            } else if (action === 'duplicate') {
                self.duplicateItem(category, id);
            } else if (action === 'delete') {
                self.deleteItem(category, id);
            }
        });

        // Initialiser SortableJS pour le drag-and-drop
        this.initSortable();
    },

    // Instances SortableJS actives
    sortableInstances: [],

    initSortable() {
        var self = this;

        // Détruire les anciennes instances
        this.sortableInstances.forEach(function(instance) {
            instance.destroy();
        });
        this.sortableInstances = [];

        // Créer une instance pour chaque liste
        document.querySelectorAll('.editable-list').forEach(function(list) {
            var category = list.dataset.category;

            var sortable = new Sortable(list, {
                animation: 150,
                easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                filter: '.btn-icon, .item-actions',
                preventOnFilter: false,
                onEnd: function(evt) {
                    if (evt.oldIndex !== evt.newIndex) {
                        DataManager.reorder(category, evt.oldIndex, evt.newIndex);
                        self.refreshAllSelects();
                    }
                }
            });

            self.sortableInstances.push(sortable);
        });
    },

    refreshCustomLists() {
        var self = this;
        var categories = ['personas', 'verbes', 'structures', 'longueurs', 'tons', 'contraintes', 'audiences'];

        categories.forEach(function(category) {
            var container = document.getElementById('list-' + category);
            if (!container) return;

            // Afficher TOUS les elements (pas seulement les custom)
            var allItems = DataManager.getAll(category);
            container.innerHTML = UIManager.renderEditableList({
                category: category,
                items: allItems,
                emptyMessage: 'Aucun élément'
            });
        });

        // Réinitialiser SortableJS après le rendu
        this.initSortable();
    },

    refreshAllSelects() {
        UIManager.updateSelect('predefined-persona', DataManager.getAll('personas'), '-- Sélectionnez une persona --');
        UIManager.updateSelect('task-verb', DataManager.getAll('verbes'), '-- Sélectionnez un verbe --');
        UIManager.updateSelect('format-structure', DataManager.getAll('structures'), '-- Aucun format spécifique --');
        UIManager.updateSelect('format-length', DataManager.getAll('longueurs'), '-- Aucune longueur spécifique --');
        UIManager.updateSelect('format-tone', DataManager.getAll('tons'), '-- Aucun ton spécifique --');
        UIManager.updateSelect('predefined-audience', DataManager.getAll('audiences'), '-- Sélectionnez une audience --');
    },

    openAddModal(category) {
        this.currentEditCategory = category;
        this.currentEditId = null;

        var title = document.getElementById('modal-title');
        var input = document.getElementById('modal-input');

        title.textContent = 'Ajouter - ' + this.getCategoryLabel(category);
        input.value = '';

        UIManager.openModal('itemModal');
        input.focus();
    },

    openEditModal(category, id) {
        // Chercher dans TOUTES les donnees
        var item = DataManager.findById(category, id);

        if (!item) return;

        this.currentEditCategory = category;
        this.currentEditId = id;

        var title = document.getElementById('modal-title');
        var input = document.getElementById('modal-input');

        title.textContent = 'Modifier - ' + this.getCategoryLabel(category);
        input.value = item.label || item.value;

        UIManager.openModal('itemModal');
        input.focus();
    },

    saveItem() {
        var input = document.getElementById('modal-input');
        var value = input.value.trim();

        if (!value) {
            UIManager.toast('Veuillez entrer une valeur', 'error');
            return;
        }

        if (this.currentEditId) {
            DataManager.update(this.currentEditCategory, this.currentEditId, { value: value, label: value });
            UIManager.toast('Élément modifié', 'success');
        } else {
            DataManager.add(this.currentEditCategory, { value: value, label: value });
            UIManager.toast('Élément ajouté', 'success');
        }

        UIManager.closeModal('itemModal');
        this.refreshCustomLists();
        this.refreshAllSelects();
    },

    deleteItem(category, id) {
        var self = this;
        UIManager.confirm({
            title: 'Supprimer',
            message: 'Supprimer cet élément?',
            confirmText: 'Supprimer',
            danger: true
        }).then(function(confirmed) {
            if (!confirmed) return;
            DataManager.delete(category, id);
            UIManager.toast('Élément supprimé', 'success');
            self.refreshCustomLists();
            self.refreshAllSelects();
        });
    },

    duplicateItem(category, id) {
        var item = DataManager.findById(category, id);
        if (!item) return;

        // Ouvrir le modal en mode ajout avec la valeur pré-remplie
        this.currentEditCategory = category;
        this.currentEditId = null;

        var title = document.getElementById('modal-title');
        var input = document.getElementById('modal-input');

        title.textContent = 'Dupliquer - ' + this.getCategoryLabel(category);
        input.value = (item.label || item.value) + ' (copie)';

        UIManager.openModal('itemModal');
        input.focus();
        input.select();
    },

    getCategoryLabel(category) {
        var labels = {
            personas: 'Persona',
            verbes: 'Verbe d\'action',
            structures: 'Structure',
            longueurs: 'Longueur',
            tons: 'Ton',
            contraintes: 'Contrainte',
            audiences: 'Audience'
        };
        return labels[category] || category;
    },

    // Modals
    initModals() {
        var self = this;

        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal || e.target.classList.contains('modal-close')) {
                    UIManager.closeModal(modal.id);
                }
            });
        });

        var saveBtn = document.getElementById('modal-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function() { self.saveItem(); });
        }

        var cancelBtn = document.getElementById('modal-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() { UIManager.closeModal('itemModal'); });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                UIManager.closeAllModals();
            }
        });

        var modalInput = document.getElementById('modal-input');
        if (modalInput) {
            modalInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    self.saveItem();
                }
            });
        }
    },

    // Export/Import
    initExportImport() {
        var self = this;

        var exportBtn = document.getElementById('btn-export');
        var importBtn = document.getElementById('btn-import');
        var resetBtn = document.getElementById('btn-reset');
        var clearBtn = document.getElementById('btn-clear');

        if (exportBtn) {
            exportBtn.addEventListener('click', function() { self.exportData(); });
        }

        if (importBtn) {
            importBtn.addEventListener('click', function() { self.importData(); });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function() { self.resetData(); });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() { self.clearData(); });
        }
    },

    exportData() {
        var data = DataManager.export();
        ExportManager.exportJSON(data, 'prompt-generator-config');
        UIManager.toast('Configuration exportée', 'success');
    },

    importData() {
        var self = this;
        ExportManager.importJSON().then(function(data) {
            var validation = ExportManager.validate(data);
            if (!validation.isValid) {
                UIManager.toast('Fichier invalide: ' + validation.errors.join(', '), 'error');
                return;
            }

            UIManager.confirm({
                title: 'Importer',
                message: 'Comment souhaitez-vous importer ces données?',
                confirmText: 'Fusionner',
                cancelText: 'Remplacer'
            }).then(function(merge) {
                DataManager.import(data, merge);
                self.refreshCustomLists();
                self.refreshAllSelects();
                UIManager.toast('Configuration importée', 'success');
            });
        }).catch(function(error) {
            UIManager.toast(error.message, 'error');
        });
    },

    resetData() {
        var self = this;
        UIManager.confirm({
            title: 'Réinitialiser',
            message: 'Réinitialiser toutes les données aux valeurs par défaut?\n\nCette action est irréversible.',
            confirmText: 'Réinitialiser',
            danger: true
        }).then(function(confirmed) {
            if (!confirmed) return;
            DataManager.reset();
            self.refreshCustomLists();
            self.refreshAllSelects();
            UIManager.toast('Données réinitialisées', 'success');
        });
    },

    clearData() {
        var self = this;
        UIManager.confirm({
            title: 'Vider tout',
            message: 'Vider toutes les listes?\n\nToutes les données seront supprimées. Cette action est irréversible.',
            confirmText: 'Vider tout',
            danger: true
        }).then(function(confirmed) {
            if (!confirmed) return;
            DataManager.clearAll();
            self.refreshCustomLists();
            self.refreshAllSelects();
            UIManager.toast('Toutes les données ont été supprimées', 'success');
        });
    }
};

// Démarrer l'application
document.addEventListener('DOMContentLoaded', function() {
    App.init();
});

