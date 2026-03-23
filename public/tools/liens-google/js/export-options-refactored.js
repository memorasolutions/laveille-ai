/**
 * Module ExportOptions Refactorisé - Utilise les modules CommonPdfParams et ParamTypeRenderer
 * Keep it simple - Réutilise les modules au lieu de dupliquer le code
 */

class ExportOptionsRefactored {
    constructor() {
        // Charger les modules
        this.commonPdf = typeof commonPdfParams !== 'undefined' ? commonPdfParams : null;
        this.renderer = typeof paramTypeRenderer !== 'undefined' ? paramTypeRenderer : null;
        
        if (!this.commonPdf) {
            console.warn('Module CommonPdfParams non chargé');
        }
        
        if (!this.renderer) {
            console.warn('Module ParamTypeRenderer non chargé');
        }
        
        // Options spécifiques par service (non communes)
        this.serviceSpecific = {
            sheets: this.getSheetsSpecific(),
            docs: this.getDocsSpecific(),
            slides: this.getSlidesSpecific()
        };
    }
    
    /**
     * Options spécifiques à Sheets (non communes)
     */
    getSheetsSpecific() {
        return {
            gid: {
                label: 'Feuille spécifique',
                type: 'text',
                icon: '📑',
                placeholder: 'ex: 0',
                priority: 1,
                description: 'GID de la feuille (visible dans l\'URL après #gid=)'
            },
            
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
            
            fitw: {
                label: 'Ajuster à la largeur',
                type: 'toggle',
                icon: '↔️',
                options: [
                    { value: 'true', label: 'Ajuster', icon: '🔳' },
                    { value: 'false', label: 'Taille réelle', icon: '📐' }
                ],
                default: 'false',
                priority: 2
            },
            
            pageorder: {
                label: 'Ordre des pages',
                type: 'toggle',
                icon: '📄',
                options: [
                    { value: '1', label: 'Bas puis droite', icon: '↓→' },
                    { value: '2', label: 'Droite puis bas', icon: '→↓' }
                ],
                default: '1',
                priority: 3,
                description: 'Comment les pages continuent lors de l\'impression sur plusieurs pages'
            },
            
            
            range: {
                label: 'Plage de cellules',
                type: 'range',
                icon: '📊',
                placeholder: 'Ex: A1:D10',
                pattern: '^[A-Z]+[0-9]+:[A-Z]+[0-9]+$',
                priority: 3,
                description: 'Exporter uniquement une plage spécifique'
            },
            
            // Groupe "Mise en page"
            layout_group: {
                label: 'Mise en page',
                type: 'group',
                icon: '📐',
                priority: 2,
                options: {
                    horizontal_alignment: {
                        label: 'Alignement H',
                        type: 'select',
                        icon: '↔️',
                        options: [
                            { value: 'LEFT', label: 'Gauche' },
                            { value: 'CENTER', label: 'Centre' },
                            { value: 'RIGHT', label: 'Droite' }
                        ],
                        default: 'LEFT'
                    },
                    
                    vertical_alignment: {
                        label: 'Alignement V',
                        type: 'select',
                        icon: '↕️',
                        options: [
                            { value: 'TOP', label: 'Haut' },
                            { value: 'MIDDLE', label: 'Milieu' },
                            { value: 'BOTTOM', label: 'Bas' }
                        ],
                        default: 'TOP'
                    }
                }
            },
            
            // Groupe "Contenu du document"
            content_group: {
                label: 'Contenu du document',
                type: 'group',
                icon: '📄',
                priority: 3,
                options: {
                    printtitle: {
                        label: 'Titre du document',
                        type: 'checkbox',
                        icon: '📝',
                        default: false
                    },
                    
                    sheetnames: {
                        label: 'Noms des feuilles',
                        type: 'checkbox',
                        icon: '🏷️',
                        default: false
                    }
                }
            },
            
            // Groupe "Éléments figés"
            frozen_group: {
                label: 'Éléments figés',
                type: 'group',
                icon: '❄️',
                priority: 3,
                options: {
                    fzr: {
                        label: 'Lignes figées',
                        type: 'checkbox',
                        icon: '❄️',
                        default: false,
                        description: 'Inclure les lignes figées'
                    },
                    
                    fzc: {
                        label: 'Colonnes figées',
                        type: 'checkbox',
                        icon: '❄️',
                        default: false,
                        description: 'Inclure les colonnes figées'
                    }
                }
            }
        };
    }
    
    /**
     * Options spécifiques à Docs
     */
    getDocsSpecific() {
        // Pas d'options spécifiques pour Docs
        // Les commentaires sont toujours inclus dans l'export PDF
        return {};
    }
    
    /**
     * Options spécifiques à Slides
     */
    getSlidesSpecific() {
        // Pas d'options spécifiques pour Slides via URL
        // Les options slides par page et skipHidden ne sont disponibles que via l'interface
        return {};
    }
    
    /**
     * Obtient les options pour un type d'export
     * @param {string} type - Type d'export (sheets_pdf, docs_pdf, slides_pdf)
     * @param {number} maxPriority - Priorité maximale
     */
    getOptions(type, maxPriority = 2) {
        // Extraire le service du type (sheets_pdf -> sheets)
        const service = type.split('_')[0];
        
        if (!this.commonPdf) {
            console.error('Module CommonPdfParams non disponible');
            return {};
        }
        
        // Obtenir les options communes
        const commonOptions = this.commonPdf.getOptionsByPriority(maxPriority);
        
        // Obtenir les options spécifiques
        const specificOptions = this.serviceSpecific[service] || {};
        
        // Filtrer par priorité
        const filteredSpecific = {};
        for (const [key, option] of Object.entries(specificOptions)) {
            if (!option.priority || option.priority <= maxPriority) {
                // Pour les groupes, on les inclut mais on peut filtrer les options internes
                if (option.type === 'group') {
                    const filteredGroup = { ...option };
                    const filteredGroupOptions = {};
                    
                    for (const [groupKey, groupOption] of Object.entries(option.options)) {
                        if (!groupOption.priority || groupOption.priority <= maxPriority) {
                            filteredGroupOptions[groupKey] = groupOption;
                        }
                    }
                    
                    if (Object.keys(filteredGroupOptions).length > 0) {
                        filteredGroup.options = filteredGroupOptions;
                        filteredSpecific[key] = filteredGroup;
                    }
                } else {
                    filteredSpecific[key] = option;
                }
            }
        }
        
        // Fusionner
        return { ...commonOptions, ...filteredSpecific };
    }
    
    /**
     * Crée l'élément DOM pour une option
     * @param {string} key - Clé de l'option
     * @param {Object} option - Configuration de l'option
     * @param {*} value - Valeur actuelle
     */
    createOptionElement(key, option, value = null) {
        if (!this.renderer) {
            console.error('Module ParamTypeRenderer non disponible');
            return document.createElement('div');
        }
        
        return this.renderer.render(key, option, value);
    }
    
    /**
     * Extrait les valeurs d'un conteneur
     * @param {HTMLElement} container - Conteneur DOM
     */
    getValues(container) {
        if (!this.renderer) {
            console.error('Module ParamTypeRenderer non disponible');
            return {};
        }
        
        return this.renderer.extractValues(container);
    }
    
    /**
     * Applique un preset de marges
     * @param {string} presetName - Nom du preset
     */
    applyMarginPreset(presetName) {
        if (!this.commonPdf) {
            console.error('Module CommonPdfParams non disponible');
            return {};
        }
        
        return this.commonPdf.applyMarginPreset(presetName);
    }
    
    /**
     * Construit les paramètres d'URL pour l'export
     * @param {Object} userParams - Paramètres utilisateur
     * @param {string} service - Service (sheets, docs, slides)
     */
    buildExportParams(userParams, service) {
        if (!this.commonPdf) {
            console.error('Module CommonPdfParams non disponible');
            return userParams;
        }
        
        // Utiliser le module pour construire les params
        const baseParams = this.commonPdf.buildPdfParams(userParams);
        
        // Ajouter les paramètres spécifiques au service
        const specificOptions = this.serviceSpecific[service] || {};
        
        for (const [key, option] of Object.entries(specificOptions)) {
            if (option.type === 'group') {
                // Pour les groupes, on ajoute les paramètres des options internes
                for (const groupKey of Object.keys(option.options)) {
                    if (userParams.hasOwnProperty(groupKey)) {
                        baseParams[groupKey] = userParams[groupKey];
                    }
                }
            } else {
                // Option normale
                if (userParams.hasOwnProperty(key)) {
                    baseParams[key] = userParams[key];
                }
            }
        }
        
        return baseParams;
    }
    
    /**
     * Valide les paramètres
     * @param {Object} params - Paramètres à valider
     * @param {string} service - Service
     */
    validateParams(params, service) {
        const errors = [];
        
        // Valider les marges avec le module commun
        if (this.commonPdf && !this.commonPdf.validateMargins(params)) {
            errors.push('Si des marges sont définies, toutes doivent l\'être (top, bottom, left, right)');
        }
        
        // Valider la plage pour Sheets
        if (service === 'sheets' && params.range) {
            const rangePattern = /^[A-Z]+[0-9]+:[A-Z]+[0-9]+$/;
            if (!rangePattern.test(params.range)) {
                errors.push('Format de plage invalide. Utilisez le format A1:D10');
            }
        }
        
        return {
            valid: errors.length === 0,
            errors
        };
    }
    
    /**
     * Obtient les valeurs par défaut pour un service
     * @param {string} service - Service
     */
    getDefaults(service) {
        const defaults = this.commonPdf ? this.commonPdf.getDefaults() : {};
        
        // Ajouter les defaults spécifiques
        const specific = this.serviceSpecific[service] || {};
        
        for (const [key, option] of Object.entries(specific)) {
            if (option.type === 'group') {
                // Pour les groupes, prendre les defaults des options internes
                for (const [groupKey, groupOption] of Object.entries(option.options)) {
                    if (groupOption.default !== undefined) {
                        defaults[groupKey] = groupOption.default;
                    }
                }
            } else {
                // Option normale
                if (option.default !== undefined) {
                    defaults[key] = option.default;
                }
            }
        }
        
        return defaults;
    }
}

// Export singleton
const exportOptionsRefactored = new ExportOptionsRefactored();

// Export pour les tests
if (typeof module !== 'undefined' && module.exports) {
    module.exports = exportOptionsRefactored;
}