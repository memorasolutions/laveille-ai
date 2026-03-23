/**
 * Module ExportOptions - Gestion modulaire des options d'export
 * Réutilisable pour tous les types d'export Google (Sheets, Docs, Slides)
 * Keep it simple - Une option à la fois
 */

class ExportOptions {
    constructor() {
        // Utiliser IconRegistry si disponible
        this.icons = typeof iconRegistry !== 'undefined' ? iconRegistry : null;
        
        // Options communes à tous les exports
        this.commonOptions = {
            format: {
                label: 'Format',
                type: 'select',
                icon: this.getIcon('formats.pdf'),
                priority: 1
            }
        };

        // Options spécifiques par type
        this.optionsByType = {
            sheets_pdf: this.getSheetsPdfOptions(),
            docs_pdf: this.getDocsPdfOptions(),
            slides_pdf: this.getSlidesPdfOptions()
        };
    }

    /**
     * Helper pour obtenir une icône
     */
    getIcon(path, fallback) {
        if (this.icons) {
            return this.icons.get(path, fallback);
        }
        // Fallback si IconRegistry non chargé
        return fallback || '📌';
    }

    /**
     * Options PDF pour Google Sheets
     * Priorité aux options les plus utilisées
     */
    getSheetsPdfOptions() {
        return {
            // PRIORITÉ 1 - Options essentielles
            gid: {
                label: 'Feuille spécifique',
                type: 'text',
                icon: '📑',
                placeholder: 'ID de la feuille (ex: 0)',
                priority: 1,
                description: 'Exporter une seule feuille'
            },
            
            portrait: {
                label: 'Orientation',
                type: 'toggle',
                icon: '📐',
                options: [
                    { value: 'true', label: 'Portrait', icon: '📱' },
                    { value: 'false', label: 'Paysage', icon: '💻' }
                ],
                default: 'true',
                priority: 1
            },

            size: {
                label: 'Format papier',
                type: 'select',
                icon: '📏',
                options: [
                    { value: 'letter', label: 'Letter (8.5" × 11")' },
                    { value: 'a4', label: 'A4 (210 × 297mm)' },
                    { value: 'legal', label: 'Legal (8.5" × 14")' },
                    { value: 'tabloid', label: 'Tabloid (11" × 17")' },
                    { value: 'a3', label: 'A3 (297 × 420mm)' }
                ],
                default: 'letter',
                priority: 1
            },

            // PRIORITÉ 2 - Options d'affichage
            gridlines: {
                label: 'Quadrillage',
                type: 'toggle',
                icon: '📊',
                options: [
                    { value: 'true', label: 'Avec grille', icon: '⊞' },
                    { value: 'false', label: 'Sans grille', icon: '⊡' }
                ],
                default: 'false',
                priority: 2
            },

            scale: {
                label: 'Échelle',
                type: 'select',
                icon: '🔍',
                options: [
                    { value: '1', label: 'Normal 100%' },
                    { value: '2', label: 'Ajuster largeur' },
                    { value: '3', label: 'Ajuster hauteur' },
                    { value: '4', label: 'Ajuster page' }
                ],
                default: '1',
                priority: 2
            },
            
            pagenum: {
                label: 'Numéros de page',
                type: 'select',
                icon: '🔢',
                options: [
                    { value: 'UNDEFINED', label: 'Aucun' },
                    { value: 'RIGHT', label: 'Droite' },
                    { value: 'CENTER', label: 'Centre' },
                    { value: 'LEFT', label: 'Gauche' }
                ],
                default: 'RIGHT',
                priority: 2,
                description: 'Position des numéros de page'
            },

            // PRIORITÉ 3 - Marges
            margins: {
                label: 'Marges',
                type: 'preset',
                icon: '📐',
                presets: [
                    {
                        label: 'Normal',
                        values: { top: '0.75', bottom: '0.75', left: '0.70', right: '0.70' }
                    },
                    {
                        label: 'Étroit',
                        values: { top: '0.25', bottom: '0.25', left: '0.25', right: '0.25' }
                    },
                    {
                        label: 'Sans',
                        values: { top: '0.00', bottom: '0.00', left: '0.00', right: '0.00' }
                    }
                ],
                priority: 3
            },

            // PRIORITÉ 4 - Options avancées
            range: {
                label: 'Plage de cellules',
                type: 'range',
                icon: '📊',
                placeholder: 'Ex: A1:D10',
                priority: 4,
                advanced: true
            },

            printtitle: {
                label: 'Titre du document',
                type: 'checkbox',
                icon: '📝',
                default: false,
                priority: 4,
                advanced: true
            },

            sheetnames: {
                label: 'Noms des feuilles',
                type: 'checkbox',
                icon: '🏷️',
                default: false,
                priority: 4,
                advanced: true
            }
        };
    }

    /**
     * Options PDF pour Google Docs
     */
    getDocsPdfOptions() {
        return {
            // Options simples pour Docs
            pagenum: {
                label: 'Numéros de page',
                type: 'select',
                icon: '🔢',
                options: [
                    { value: 'UNDEFINED', label: 'Aucun' },
                    { value: 'RIGHT', label: 'Droite' },
                    { value: 'CENTER', label: 'Centre' },
                    { value: 'LEFT', label: 'Gauche' }
                ],
                default: 'RIGHT',
                priority: 1
            },
            
            includeComments: {
                label: 'Inclure commentaires',
                type: 'checkbox',
                icon: '💬',
                default: false,
                priority: 2
            }
        };
    }

    /**
     * Options PDF pour Google Slides
     */
    getSlidesPdfOptions() {
        return {
            // Options simples pour Slides
            slides: {
                label: 'Slides par page',
                type: 'select',
                icon: '🎯',
                options: [
                    { value: '1', label: '1 slide' },
                    { value: '2', label: '2 slides' },
                    { value: '4', label: '4 slides' },
                    { value: '6', label: '6 slides' }
                ],
                default: '1',
                priority: 1
            },
            
            skipHidden: {
                label: 'Ignorer slides masqués',
                type: 'checkbox',
                icon: '👁️',
                default: true,
                priority: 2
            },
            
            pagenum: {
                label: 'Numéros de page',
                type: 'select',
                icon: '🔢',
                options: [
                    { value: 'UNDEFINED', label: 'Aucun' },
                    { value: 'RIGHT', label: 'Droite' },
                    { value: 'CENTER', label: 'Centre' },
                    { value: 'LEFT', label: 'Gauche' }
                ],
                default: 'RIGHT',
                priority: 2
            }
        };
    }

    /**
     * Obtenir les options pour un type donné
     * @param {string} type - Type d'export (ex: 'sheets_pdf')
     * @param {number} maxPriority - Priorité maximale à afficher
     */
    getOptions(type, maxPriority = 2) {
        const options = this.optionsByType[type] || {};
        
        // Filtrer par priorité
        return Object.entries(options)
            .filter(([key, opt]) => opt.priority <= maxPriority)
            .reduce((acc, [key, opt]) => {
                acc[key] = opt;
                return acc;
            }, {});
    }

    /**
     * Créer l'interface pour une option
     * @param {string} key - Clé de l'option
     * @param {Object} option - Configuration de l'option
     * @param {Object} value - Valeur actuelle
     */
    createOptionElement(key, option, value = null) {
        const div = document.createElement('div');
        div.className = 'export-option';
        div.dataset.optionKey = key;
        div.dataset.priority = option.priority;

        // Label
        const label = document.createElement('label');
        label.className = 'export-option-label';
        label.innerHTML = `<span class="option-icon">${option.icon}</span> ${option.label}`;
        div.appendChild(label);

        // Créer le contrôle selon le type
        let control;
        switch (option.type) {
            case 'select':
                control = this.createSelect(key, option, value);
                break;
            case 'toggle':
                control = this.createToggle(key, option, value);
                break;
            case 'checkbox':
                control = this.createCheckbox(key, option, value);
                break;
            case 'text':
                control = this.createText(key, option, value);
                break;
            case 'range':
                control = this.createRange(key, option, value);
                break;
            case 'preset':
                control = this.createPreset(key, option, value);
                break;
            default:
                control = document.createElement('span');
        }

        div.appendChild(control);

        // Description si présente
        if (option.description) {
            const desc = document.createElement('small');
            desc.className = 'export-option-description';
            desc.textContent = option.description;
            div.appendChild(desc);
        }

        return div;
    }

    /**
     * Créer un select
     */
    createSelect(key, option, value) {
        const select = document.createElement('select');
        select.className = 'export-option-select';
        select.name = key;
        select.value = value || option.default || '';

        option.options.forEach(opt => {
            const optionEl = document.createElement('option');
            optionEl.value = opt.value;
            optionEl.textContent = opt.label;
            if (opt.value === (value || option.default)) {
                optionEl.selected = true;
            }
            select.appendChild(optionEl);
        });

        return select;
    }

    /**
     * Créer un toggle (2 boutons)
     */
    createToggle(key, option, value) {
        const toggle = document.createElement('div');
        toggle.className = 'export-option-toggle';
        
        option.options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'toggle-btn';
            btn.dataset.value = opt.value;
            btn.innerHTML = `${opt.icon || ''} ${opt.label}`;
            
            if (opt.value === (value || option.default)) {
                btn.classList.add('active');
            }
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                toggle.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
            
            toggle.appendChild(btn);
        });

        return toggle;
    }

    /**
     * Créer une checkbox
     */
    createCheckbox(key, option, value) {
        const wrapper = document.createElement('div');
        wrapper.className = 'export-option-checkbox';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = key;
        checkbox.checked = value !== null ? value : option.default;
        
        wrapper.appendChild(checkbox);
        
        return wrapper;
    }

    /**
     * Créer un champ texte
     */
    createText(key, option, value) {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'export-option-text';
        input.name = key;
        input.placeholder = option.placeholder || '';
        input.value = value || '';
        
        return input;
    }

    /**
     * Créer un sélecteur de plage
     */
    createRange(key, option, value) {
        const wrapper = document.createElement('div');
        wrapper.className = 'export-option-range';
        
        const input = document.createElement('input');
        input.type = 'text';
        input.name = key;
        input.placeholder = option.placeholder || 'A1:Z100';
        input.value = value || '';
        input.pattern = '[A-Z]+[0-9]+:[A-Z]+[0-9]+';
        
        wrapper.appendChild(input);
        
        return wrapper;
    }

    /**
     * Créer des presets (marges)
     */
    createPreset(key, option, value) {
        const wrapper = document.createElement('div');
        wrapper.className = 'export-option-preset';
        
        option.presets.forEach(preset => {
            const btn = document.createElement('button');
            btn.className = 'preset-btn';
            btn.textContent = preset.label;
            btn.dataset.values = JSON.stringify(preset.values);
            
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                wrapper.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
            
            wrapper.appendChild(btn);
        });
        
        // Sélectionner le premier par défaut
        wrapper.querySelector('.preset-btn')?.classList.add('active');
        
        return wrapper;
    }

    /**
     * Obtenir les valeurs depuis l'interface
     */
    getValues(container) {
        const values = {};
        
        container.querySelectorAll('.export-option').forEach(option => {
            const key = option.dataset.optionKey;
            
            // Select
            const select = option.querySelector('select');
            if (select) {
                values[key] = select.value;
                return;
            }
            
            // Toggle
            const activeToggle = option.querySelector('.toggle-btn.active');
            if (activeToggle) {
                values[key] = activeToggle.dataset.value;
                return;
            }
            
            // Checkbox
            const checkbox = option.querySelector('input[type="checkbox"]');
            if (checkbox) {
                values[key] = checkbox.checked ? 'true' : 'false';
                return;
            }
            
            // Text/Range
            const input = option.querySelector('input[type="text"]');
            if (input && input.value) {
                values[key] = input.value;
                return;
            }
            
            // Preset
            const activePreset = option.querySelector('.preset-btn.active');
            if (activePreset) {
                const presetValues = JSON.parse(activePreset.dataset.values);
                Object.assign(values, presetValues);
            }
        });
        
        return values;
    }

    /**
     * Construire l'URL avec les options
     */
    buildUrl(baseUrl, options) {
        const params = new URLSearchParams();
        
        Object.entries(options).forEach(([key, value]) => {
            if (value !== null && value !== '') {
                params.append(key, value);
            }
        });
        
        const separator = baseUrl.includes('?') ? '&' : '?';
        return baseUrl + separator + params.toString();
    }
}

// Export singleton
const exportOptions = new ExportOptions();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = exportOptions;
}