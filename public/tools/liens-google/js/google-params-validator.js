/**
 * Module GoogleParamsValidator - Validation des paramètres Google Export (2025)
 * Keep it simple - Valide les paramètres selon l'API Google 2025
 * Réutilisable pour tous les exports Google
 */

class GoogleParamsValidator {
    constructor() {
        // Paramètres valides selon l'API 2025
        this.validParams = {
            // Format et type de fichier
            format: ['pdf', 'xlsx', 'csv', 'docx', 'pptx', 'txt', 'html'],
            exportFormat: ['pdf'],
            
            // Configuration de page
            size: ['a3', 'a4', 'a5', 'b4', 'b5', 'letter', 'tabloid', 'legal', 'statement', 'executive', 'folio'],
            portrait: ['true', 'false'],
            scale: ['1', '2', '3', '4'], // 1=Normal, 2=Largeur, 3=Hauteur, 4=Page
            
            // Marges (doivent être numériques)
            top_margin: 'numeric',
            bottom_margin: 'numeric',
            left_margin: 'numeric',
            right_margin: 'numeric',
            
            // Options d'affichage
            gridlines: ['true', 'false'],
            printnotes: ['true', 'false'],
            printtitle: ['true', 'false'],
            sheetnames: ['true', 'false'],
            
            // Mise en page et alignement
            pageorder: ['1', '2'], // 1=Bas puis droite, 2=Droite puis bas
            horizontal_alignment: ['LEFT', 'CENTER', 'RIGHT'],
            vertical_alignment: ['TOP', 'MIDDLE', 'BOTTOM'],
            pagenum: ['LEFT', 'CENTER', 'RIGHT', 'UNDEFINED'],
            
            // Lignes/colonnes figées
            fzr: ['true', 'false'],
            fzc: ['true', 'false'],
            
            // Sélection de feuille
            gid: 'numeric',
            
            // Sélection de plage
            r1: 'numeric', // Ligne début (0-indexed)
            c1: 'numeric', // Colonne début (0-indexed)
            r2: 'numeric', // Ligne fin (1-indexed)
            c2: 'numeric', // Colonne fin (1-indexed)
            
            // Méthode de livraison
            attachment: ['true', 'false'],
            
            // Paramètres spécifiques Slides
            slides: ['1', '2', '4', '6', '9'],
            skipHidden: ['true', 'false'],
            
            // Paramètres spécifiques Docs
            includeComments: ['true', 'false']
        };
        
        // Paramètres par défaut recommandés
        this.defaults = {
            format: 'pdf',
            portrait: 'true',
            scale: '1',
            gridlines: 'false',
            pagenum: 'RIGHT',
            fzr: 'false',
            fzc: 'false',
            attachment: 'false',
            top_margin: '0.75',
            bottom_margin: '0.75',
            left_margin: '0.70',
            right_margin: '0.70'
        };
        
        // Paramètres requis ensemble (si l'un est défini, tous doivent l'être)
        this.requiredTogether = {
            margins: ['top_margin', 'bottom_margin', 'left_margin', 'right_margin'],
            range: ['r1', 'c1', 'r2', 'c2']
        };
    }
    
    /**
     * Valide un ensemble de paramètres
     * @param {Object} params - Les paramètres à valider
     * @param {string} service - Le service (sheets, docs, slides)
     * @returns {Object} { valid: boolean, cleaned: Object, errors: Array }
     */
    validate(params, service = 'sheets') {
        const errors = [];
        const cleaned = {};
        
        // Parcourir chaque paramètre
        for (const [key, value] of Object.entries(params)) {
            const validation = this.validateParam(key, value, service);
            
            if (validation.valid) {
                cleaned[key] = validation.value;
            } else if (validation.error) {
                errors.push(validation.error);
            }
        }
        
        // Vérifier les paramètres requis ensemble
        this.checkRequiredTogether(cleaned, errors);
        
        // Ajouter les valeurs par défaut si nécessaire
        this.addDefaults(cleaned, service);
        
        return {
            valid: errors.length === 0,
            cleaned,
            errors
        };
    }
    
    /**
     * Valide un paramètre individuel
     */
    validateParam(key, value, service) {
        // Vérifier si le paramètre est connu
        if (!this.validParams.hasOwnProperty(key)) {
            return { 
                valid: false, 
                error: `Paramètre inconnu: ${key}` 
            };
        }
        
        const validValues = this.validParams[key];
        
        // Si c'est numérique
        if (validValues === 'numeric') {
            const numValue = parseFloat(value);
            if (isNaN(numValue)) {
                return { 
                    valid: false, 
                    error: `${key} doit être numérique, reçu: ${value}` 
                };
            }
            return { valid: true, value: value.toString() };
        }
        
        // Si c'est une liste de valeurs valides
        if (Array.isArray(validValues)) {
            const strValue = value.toString().toLowerCase();
            const validLower = validValues.map(v => v.toLowerCase());
            
            if (validLower.includes(strValue)) {
                // Retourner la valeur dans la casse correcte
                const index = validLower.indexOf(strValue);
                return { valid: true, value: validValues[index] };
            }
            
            return { 
                valid: false, 
                error: `${key} invalide: ${value}. Valeurs acceptées: ${validValues.join(', ')}` 
            };
        }
        
        return { valid: true, value };
    }
    
    /**
     * Vérifie les paramètres qui doivent être définis ensemble
     */
    checkRequiredTogether(params, errors) {
        for (const [group, keys] of Object.entries(this.requiredTogether)) {
            const defined = keys.filter(k => params.hasOwnProperty(k));
            
            if (defined.length > 0 && defined.length < keys.length) {
                const missing = keys.filter(k => !params.hasOwnProperty(k));
                errors.push(`${group}: Si défini, tous ces paramètres sont requis: ${missing.join(', ')}`);
            }
        }
    }
    
    /**
     * Ajoute les valeurs par défaut appropriées
     */
    addDefaults(params, service) {
        // N'ajouter que les defaults essentiels si aucun format n'est spécifié
        if (!params.format && !params.exportFormat) {
            params.format = 'pdf';
        }
    }
    
    /**
     * Obtient les paramètres valides pour un service
     */
    getValidParamsForService(service) {
        const common = [
            'format', 'size', 'portrait', 'scale', 
            'top_margin', 'bottom_margin', 'left_margin', 'right_margin',
            'pagenum', 'attachment'
        ];
        
        const serviceSpecific = {
            sheets: [
                ...common,
                'gid', 'gridlines', 'printnotes', 'printtitle', 
                'sheetnames', 'pageorder', 'horizontal_alignment', 
                'vertical_alignment', 'fzr', 'fzc', 'r1', 'c1', 'r2', 'c2'
            ],
            docs: [
                ...common,
                'includeComments'
            ],
            slides: [
                ...common,
                'slides', 'skipHidden'
            ]
        };
        
        return serviceSpecific[service] || common;
    }
    
    /**
     * Nettoie les paramètres en gardant seulement les valides
     */
    clean(params, service = 'sheets') {
        const validKeys = this.getValidParamsForService(service);
        const cleaned = {};
        
        for (const key of validKeys) {
            if (params.hasOwnProperty(key)) {
                cleaned[key] = params[key];
            }
        }
        
        return cleaned;
    }
    
    /**
     * Construit une URL d'export valide
     */
    buildExportUrl(baseUrl, params, service = 'sheets') {
        const validation = this.validate(params, service);
        
        if (!validation.valid) {
            console.warn('Paramètres invalides:', validation.errors);
        }
        
        const queryParams = new URLSearchParams();
        
        // Ajouter les paramètres validés
        for (const [key, value] of Object.entries(validation.cleaned)) {
            // Les marges doivent utiliser des underscores
            if (key.includes('_margin')) {
                queryParams.append(key, value);
            } else {
                queryParams.append(key, value);
            }
        }
        
        return `${baseUrl}?${queryParams.toString()}`;
    }
}

// Export singleton
const googleParamsValidator = new GoogleParamsValidator();

// Export pour les tests
if (typeof module !== 'undefined' && module.exports) {
    module.exports = googleParamsValidator;
}