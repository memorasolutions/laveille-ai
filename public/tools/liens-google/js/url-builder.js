/**
 * Module UrlBuilder - Construction d'URLs avec paramètres
 * Réutilisable pour : export, partage, embed, API
 * Keep it simple - Construction fluide d'URLs
 */

class UrlBuilder {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
        this.params = new Map();
        this.hash = '';
        this.parse();
    }

    /**
     * Parser l'URL de base pour extraire les paramètres existants
     */
    parse() {
        if (!this.baseUrl) return;
        
        try {
            const url = new URL(this.baseUrl);
            
            // Extraire les paramètres existants
            url.searchParams.forEach((value, key) => {
                this.params.set(key, value);
            });
            
            // Extraire le hash
            this.hash = url.hash;
            
            // Nettoyer l'URL de base
            this.baseUrl = url.origin + url.pathname;
        } catch (e) {
            // Si ce n'est pas une URL complète, la garder telle quelle
            const parts = this.baseUrl.split('?');
            if (parts.length > 1) {
                this.baseUrl = parts[0];
                const searchParams = new URLSearchParams(parts[1]);
                searchParams.forEach((value, key) => {
                    this.params.set(key, value);
                });
            }
        }
    }

    /**
     * Ajouter un paramètre
     * @param {string} key - Clé du paramètre
     * @param {string|number|boolean} value - Valeur
     * @returns {UrlBuilder} - Pour chaînage
     */
    addParam(key, value) {
        if (value !== null && value !== undefined && value !== '') {
            this.params.set(key, String(value));
        }
        return this;
    }

    /**
     * Ajouter plusieurs paramètres
     * @param {Object} params - Objet de paramètres
     * @returns {UrlBuilder} - Pour chaînage
     */
    addParams(params) {
        Object.entries(params).forEach(([key, value]) => {
            this.addParam(key, value);
        });
        return this;
    }

    /**
     * Supprimer un paramètre
     * @param {string} key - Clé à supprimer
     * @returns {UrlBuilder} - Pour chaînage
     */
    removeParam(key) {
        this.params.delete(key);
        return this;
    }

    /**
     * Supprimer plusieurs paramètres
     * @param {Array<string>} keys - Clés à supprimer
     * @returns {UrlBuilder} - Pour chaînage
     */
    removeParams(keys) {
        keys.forEach(key => this.params.delete(key));
        return this;
    }

    /**
     * Vider tous les paramètres
     * @returns {UrlBuilder} - Pour chaînage
     */
    clearParams() {
        this.params.clear();
        return this;
    }

    /**
     * Définir le hash
     * @param {string} hash - Hash (avec ou sans #)
     * @returns {UrlBuilder} - Pour chaînage
     */
    setHash(hash) {
        this.hash = hash.startsWith('#') ? hash : `#${hash}`;
        return this;
    }

    /**
     * Obtenir un paramètre
     * @param {string} key - Clé du paramètre
     * @returns {string|null} - Valeur ou null
     */
    getParam(key) {
        return this.params.get(key) || null;
    }

    /**
     * Vérifier si un paramètre existe
     * @param {string} key - Clé à vérifier
     * @returns {boolean}
     */
    hasParam(key) {
        return this.params.has(key);
    }

    /**
     * Obtenir tous les paramètres
     * @returns {Object}
     */
    getParams() {
        const obj = {};
        this.params.forEach((value, key) => {
            obj[key] = value;
        });
        return obj;
    }

    /**
     * Construire l'URL finale
     * @returns {string} - URL complète
     */
    build() {
        if (!this.baseUrl) return '';
        
        let url = this.baseUrl;
        
        // Ajouter les paramètres
        if (this.params.size > 0) {
            const searchParams = new URLSearchParams();
            this.params.forEach((value, key) => {
                searchParams.append(key, value);
            });
            url += '?' + searchParams.toString();
        }
        
        // Ajouter le hash
        if (this.hash) {
            url += this.hash;
        }
        
        return url;
    }

    /**
     * Alias pour build()
     */
    toString() {
        return this.build();
    }

    /**
     * Cloner le builder
     * @returns {UrlBuilder} - Nouveau builder avec les mêmes paramètres
     */
    clone() {
        const cloned = new UrlBuilder(this.baseUrl);
        this.params.forEach((value, key) => {
            cloned.params.set(key, value);
        });
        cloned.hash = this.hash;
        return cloned;
    }

    /**
     * Méthodes statiques utilitaires
     */
    
    /**
     * Créer un builder depuis une URL
     * @param {string} url - URL complète
     * @returns {UrlBuilder}
     */
    static from(url) {
        return new UrlBuilder(url);
    }

    /**
     * Construire une URL Google Sheets PDF avec options
     * @param {string} spreadsheetId - ID du spreadsheet
     * @param {Object} options - Options d'export
     * @returns {string} - URL complète
     */
    static buildGoogleSheetsPdf(spreadsheetId, options = {}) {
        const baseUrl = `https://docs.google.com/spreadsheets/d/${spreadsheetId}/export`;
        
        const defaultOptions = {
            format: 'pdf',
            portrait: 'true',
            size: 'a4',
            gridlines: 'false',
            printtitle: 'false',
            sheetnames: 'false',
            pageorder: '2',
            horizontal_alignment: 'CENTER',
            vertical_alignment: 'TOP',
            top_margin: '0.75',
            bottom_margin: '0.75',
            left_margin: '0.70',
            right_margin: '0.70'
        };
        
        return new UrlBuilder(baseUrl)
            .addParams({ ...defaultOptions, ...options })
            .build();
    }

    /**
     * Construire une URL Google Docs avec format
     * @param {string} documentId - ID du document
     * @param {string} format - Format d'export
     * @returns {string} - URL complète
     */
    static buildGoogleDocsExport(documentId, format = 'pdf') {
        const baseUrl = `https://docs.google.com/document/d/${documentId}/export`;
        return new UrlBuilder(baseUrl)
            .addParam('format', format)
            .build();
    }

    /**
     * Construire une URL Google Slides avec format
     * @param {string} presentationId - ID de la présentation
     * @param {string} format - Format d'export
     * @returns {string} - URL complète
     */
    static buildGoogleSlidesExport(presentationId, format = 'pdf') {
        const baseUrl = `https://docs.google.com/presentation/d/${presentationId}/export`;
        return new UrlBuilder(baseUrl)
            .addParam('format', format)
            .build();
    }

    /**
     * Construire une URL YouTube embed avec options
     * @param {string} videoId - ID de la vidéo
     * @param {Object} options - Options (autoplay, start, end, etc.)
     * @returns {string} - URL complète
     */
    static buildYouTubeEmbed(videoId, options = {}) {
        const baseUrl = `https://www.youtube.com/embed/${videoId}`;
        return new UrlBuilder(baseUrl)
            .addParams(options)
            .build();
    }

    /**
     * Extraire l'ID depuis une URL Google
     * @param {string} url - URL Google
     * @returns {string|null} - ID ou null
     */
    static extractGoogleId(url) {
        const patterns = [
            /\/d\/([a-zA-Z0-9-_]+)/,
            /id=([a-zA-Z0-9-_]+)/,
            /\/([a-zA-Z0-9-_]{11})(?:\?|$|#)/, // YouTube
        ];
        
        for (const pattern of patterns) {
            const match = url.match(pattern);
            if (match) return match[1];
        }
        
        return null;
    }
}

// Export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UrlBuilder;
}