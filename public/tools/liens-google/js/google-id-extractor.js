/**
 * Module GoogleIdExtractor - Extraction simple et robuste des IDs Google
 * Keep it simple - Une seule responsabilité : extraire l'ID
 */

class GoogleIdExtractor {
    constructor() {
        // Patterns pour chaque service Google
        this.patterns = {
            // Google Forms - capturer l'ID après /d/ ou /d/e/
            forms: {
                pattern: /\/forms\/d\/(?:e\/)?([a-zA-Z0-9_-]+)/,
                service: 'forms'
            },
            // Google Docs
            docs: {
                pattern: /\/document\/d\/([a-zA-Z0-9_-]+)/,
                service: 'docs'
            },
            // Google Sheets
            sheets: {
                pattern: /\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/,
                service: 'sheets'
            },
            // Google Slides
            slides: {
                pattern: /\/presentation\/d\/([a-zA-Z0-9_-]+)/,
                service: 'slides'
            },
            // Google Drive - unifié pour fichiers directs et redirections
            drive: {
                pattern: /(?:\/(?:file|drive)\/d\/([a-zA-Z0-9_-]+)|drive\.google\.com\/(?:open|uc)\?.*id=([a-zA-Z0-9_-]+))/,
                service: 'drive'
            },
            // YouTube
            youtube: {
                pattern: /(?:v=|\/)([\w-]{11})(?:[?&]|$)/,
                service: 'youtube'
            }
        };
    }

    /**
     * Extrait l'ID d'une URL Google
     * @param {string} url - L'URL à analyser
     * @returns {Object|null} { id, service, isPublishedForm }
     */
    extract(url) {
        if (!url || typeof url !== 'string') {
            return null;
        }

        // Tester chaque pattern
        for (const [key, config] of Object.entries(this.patterns)) {
            const match = url.match(config.pattern);
            if (match) {
                // L'ID est dans le premier groupe capturé non-null
                const id = match[1] || match[2];
                
                if (!id) continue;

                // Déterminer si c'est un formulaire publié
                let isPublishedForm = false;
                if (key === 'forms') {
                    // Un formulaire est publié si :
                    // 1. L'URL contient /d/e/
                    // 2. L'ID commence par 1FAIpQLSc, 1FAIpQLSd, ou 1FAIpQLSf
                    isPublishedForm = url.includes('/d/e/') || 
                                     id.startsWith('1FAIpQLSc') || 
                                     id.startsWith('1FAIpQLSd') || 
                                     id.startsWith('1FAIpQLSf');
                }

                return {
                    id: id,
                    service: config.service,
                    isPublishedForm: isPublishedForm,
                    originalUrl: url
                };
            }
        }

        return null;
    }

    /**
     * Teste si une URL est un service Google supporté
     * @param {string} url - L'URL à tester
     * @returns {boolean}
     */
    isGoogleUrl(url) {
        return this.extract(url) !== null;
    }

    /**
     * Obtient le service Google depuis une URL
     * @param {string} url - L'URL à analyser
     * @returns {string|null} Le nom du service ou null
     */
    getService(url) {
        const result = this.extract(url);
        return result ? result.service : null;
    }

    /**
     * Obtient uniquement l'ID depuis une URL
     * @param {string} url - L'URL à analyser
     * @returns {string|null} L'ID ou null
     */
    getId(url) {
        const result = this.extract(url);
        return result ? result.id : null;
    }
}

// Export singleton
const googleIdExtractor = new GoogleIdExtractor();

// Export pour les tests
if (typeof module !== 'undefined' && module.exports) {
    module.exports = googleIdExtractor;
}