/**
 * Module GoogleFormsParser - Parser spécialisé pour Google Forms
 * Approche simple et robuste pour 2025
 * Keep it simple - Une responsabilité : parser les URLs Google Forms
 */

class GoogleFormsParser {
    constructor() {
        // Les formulaires Google ont toujours cette structure de base
        this.basePattern = /docs\.google\.com\/forms\/d\/([^\/]+)/;
    }

    /**
     * Parse une URL Google Forms et extrait toutes les informations
     * @param {string} url - L'URL complète du formulaire
     * @returns {Object|null} Les informations parsées ou null
     */
    parse(url) {
        if (!url || !url.includes('docs.google.com/forms')) {
            return null;
        }

        // Extraire le chemin après /forms/d/
        const match = url.match(this.basePattern);
        if (!match) return null;

        // Le groupe capturé contient soit "ID" soit "e/ID"
        const captured = match[1];
        
        // Déterminer si c'est un formulaire publié (avec /e/)
        let formId;
        let isPublished = false;
        
        if (captured.startsWith('e/')) {
            // Format publié: /d/e/ID
            formId = captured.substring(2);
            isPublished = true;
        } else if (captured === 'e') {
            // Cas spécial où on a capturé juste "e", l'ID est après
            const extendedMatch = url.match(/\/d\/e\/([^\/]+)/);
            if (extendedMatch) {
                formId = extendedMatch[1];
                isPublished = true;
            } else {
                return null;
            }
        } else {
            // Format normal: /d/ID
            formId = captured;
            isPublished = false;
        }

        // Extraire l'action (viewform, edit, viewanalytics, etc.)
        let action = 'viewform'; // Par défaut
        const actionMatch = url.match(/\/([^\/\?]+)(?:\?|$)/);
        if (actionMatch) {
            const possibleAction = actionMatch[1];
            if (['viewform', 'edit', 'viewanalytics', 'formResponse', 'prefill'].includes(possibleAction)) {
                action = possibleAction;
            }
        }

        // Extraire les paramètres
        const params = {};
        const urlObj = new URL(url);
        urlObj.searchParams.forEach((value, key) => {
            params[key] = value;
        });

        return {
            formId: formId,
            isPublished: isPublished,
            action: action,
            params: params,
            originalUrl: url
        };
    }

    /**
     * Construit une URL Google Forms à partir des composants
     * @param {string} formId - L'ID du formulaire
     * @param {Object} options - Options pour construire l'URL
     * @returns {string} L'URL construite
     */
    buildUrl(formId, options = {}) {
        const {
            action = 'viewform',
            isPublished = formId.startsWith('1FAIpQLSf') || formId.startsWith('1FAIpQLSc'),
            params = {}
        } = options;

        let url = 'https://docs.google.com/forms/d/';
        
        // Pour les formulaires publiés
        if (isPublished) {
            // viewform et prefill utilisent /d/e/
            if (action === 'viewform' || action === 'prefill' || action === 'formResponse') {
                url += `e/${formId}/${action}`;
            } else {
                // edit et viewanalytics utilisent /d/ sans /e/
                url += `${formId}/${action}`;
            }
        } else {
            // Formulaires non publiés : toujours /d/ID/action
            url += `${formId}/${action}`;
        }

        // Ajouter les paramètres
        const urlParams = new URLSearchParams(params);
        const paramString = urlParams.toString();
        if (paramString) {
            url += '?' + paramString;
        }

        return url;
    }

    /**
     * Génère toutes les variantes d'URL pour un formulaire
     * @param {string} url - L'URL originale
     * @returns {Array} Liste des transformations possibles
     */
    generateVariants(url) {
        const parsed = this.parse(url);
        if (!parsed) return [];

        const { formId, isPublished } = parsed;
        
        const variants = [
            {
                id: 'view',
                name: 'Voir formulaire',
                icon: '📝',
                url: this.buildUrl(formId, { action: 'viewform', isPublished })
            },
            {
                id: 'edit',
                name: 'Mode édition',
                icon: '✏️',
                url: this.buildUrl(formId, { action: 'edit', isPublished })
            },
            {
                id: 'responses',
                name: 'Voir réponses',
                icon: '📊',
                url: this.buildUrl(formId, { action: 'viewanalytics', isPublished })
            },
            {
                id: 'submit',
                name: 'URL de soumission',
                icon: '📤',
                url: this.buildUrl(formId, { action: 'formResponse', isPublished })
            }
        ];

        // Ajouter l'option prefill si des paramètres entry existent
        const hasEntryParams = Object.keys(parsed.params).some(key => key.startsWith('entry.'));
        if (hasEntryParams) {
            variants.push({
                id: 'prefill',
                name: 'Lien pré-rempli',
                icon: '✍️',
                url: this.buildUrl(formId, { 
                    action: 'viewform', 
                    isPublished,
                    params: parsed.params
                })
            });
        }

        return variants;
    }

    /**
     * Test si une URL est un Google Forms valide
     * @param {string} url - L'URL à tester
     * @returns {boolean} true si c'est un Google Forms
     */
    isGoogleForm(url) {
        return this.parse(url) !== null;
    }
}

// Export singleton
const googleFormsParser = new GoogleFormsParser();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = googleFormsParser;
}