/**
 * Module Detector - Cœur métier centralisé
 * Tendances 2025 : Pattern matching avancé, extensibilité
 * Utilisé pour : détection principale, validation, preview
 */

class LinkDetector {
    constructor() {
        this.patterns = this.loadDefaultPatterns();
        this.cache = new Map();
        this.customPatterns = [];
    }

    /**
     * Patterns par défaut selon devis
     */
    loadDefaultPatterns() {
        return {
            google_docs: {
                regex: /docs\.google\.com\/document\/d\/([a-zA-Z0-9-_]+)/,
                name: 'Google Docs',
                icon: '📄',
                category: 'document',
                extractId: (match) => match[1],
                validate: (id) => id && id.length > 10
            },
            google_sheets: {
                regex: /docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/,
                name: 'Google Sheets', 
                icon: '📊',
                category: 'spreadsheet',
                extractId: (match) => match[1],
                validate: (id) => id && id.length > 10,
                // Support pour GID (feuille spécifique)
                extractGid: (url) => {
                    const gidMatch = url.match(/[#&]gid=([0-9]+)/);
                    return gidMatch ? gidMatch[1] : null;
                }
            },
            google_slides: {
                regex: /docs\.google\.com\/presentation\/d\/([a-zA-Z0-9-_]+)/,
                name: 'Google Slides',
                icon: '🎯',
                category: 'presentation',
                extractId: (match) => match[1],
                validate: (id) => id && id.length > 10
            },
            google_forms: {
                regex: /docs\.google\.com\/forms\/d\/([a-zA-Z0-9-_]+)/,
                name: 'Google Forms',
                icon: '📝',
                category: 'form',
                extractId: (match) => match[1],
                validate: (id) => id && id.length > 10
            },
            google_drive: {
                regex: /drive\.google\.com\/(?:file\/d\/|open\?id=)([a-zA-Z0-9-_]+)/,
                name: 'Google Drive',
                icon: '💾',
                category: 'file',
                extractId: (match) => match[1],
                validate: (id) => id && id.length > 10,
                requiresType: true // Nécessite sélection manuelle du type
            },
            youtube: {
                regex: /(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9-_]{11})/,
                name: 'YouTube',
                icon: '🎬',
                category: 'video',
                extractId: (match) => match[1],
                validate: (id) => id && id.length === 11,
                // Extraction du timestamp
                extractTime: (url) => {
                    const timeMatch = url.match(/[?&]t=([0-9]+)/);
                    return timeMatch ? timeMatch[1] : null;
                }
            },
            youtube_short: {
                regex: /youtube\.com\/shorts\/([a-zA-Z0-9-_]{11})/,
                name: 'YouTube Shorts',
                icon: '📱',
                category: 'video',
                extractId: (match) => match[1],
                validate: (id) => id && id.length === 11
            },
            google_maps: {
                regex: /(?:maps\.google\.com|goo\.gl\/maps)\/([a-zA-Z0-9-_]+)/,
                name: 'Google Maps',
                icon: '🗺️',
                category: 'maps',
                extractId: (match) => match[1],
                validate: (id) => id && id.length > 5
            },
            google_photos: {
                regex: /photos\.google\.com\/share\/([a-zA-Z0-9-_]+)/,
                name: 'Google Photos',
                icon: '📷',
                category: 'media',
                extractId: (match) => match[1],
                validate: (id) => id && id.length > 10
            },
            google_calendar: {
                regex: /calendar\.google\.com\/calendar\/(?:u\/0\/)?(?:embed\?src=|r\?cid=)([a-zA-Z0-9-_@.]+)/,
                name: 'Google Calendar',
                icon: '📅',
                category: 'calendar',
                extractId: (match) => decodeURIComponent(match[1]),
                validate: (id) => id && (id.includes('@') || id.length > 10)
            },
            google_meet: {
                regex: /meet\.google\.com\/([a-z]{3}-[a-z]{4}-[a-z]{3})/,
                name: 'Google Meet',
                icon: '📹',
                category: 'meeting',
                extractId: (match) => match[1],
                validate: (id) => id && /^[a-z]{3}-[a-z]{4}-[a-z]{3}$/.test(id)
            }
        };
    }

    /**
     * Détecte le type de lien Google
     * @param {string} url - URL à analyser
     * @returns {Object|null} Résultat de détection
     */
    detect(url) {
        if (!url || typeof url !== 'string') return null;

        // Vérifier le cache
        if (this.cache.has(url)) {
            return this.cache.get(url);
        }

        // Nettoyer l'URL
        const cleanUrl = this.cleanUrl(url);

        // Tester chaque pattern
        for (const [type, pattern] of Object.entries(this.patterns)) {
            const match = cleanUrl.match(pattern.regex);
            
            if (match) {
                const fileId = pattern.extractId(match);
                
                // Valider l'ID
                if (!pattern.validate(fileId)) continue;
                
                const result = {
                    type,
                    pattern,
                    fileId,
                    originalUrl: url,
                    cleanUrl,
                    metadata: {
                        name: pattern.name,
                        icon: pattern.icon,
                        category: pattern.category
                    }
                };

                // Extraire les métadonnées supplémentaires
                if (pattern.extractGid) {
                    result.gid = pattern.extractGid(url);
                }
                if (pattern.extractTime) {
                    result.timestamp = pattern.extractTime(url);
                }
                if (pattern.requiresType) {
                    result.requiresType = true;
                }

                // Mettre en cache
                this.cache.set(url, result);
                
                return result;
            }
        }

        // Tester les patterns personnalisés
        for (const customPattern of this.customPatterns) {
            if (customPattern.test(url)) {
                const result = customPattern.extract(url);
                this.cache.set(url, result);
                return result;
            }
        }

        return null;
    }

    /**
     * Détection multiple
     * @param {Array<string>} urls - Liste d'URLs
     * @returns {Array<Object>} Résultats de détection
     */
    detectMultiple(urls) {
        return urls.map(url => this.detect(url)).filter(Boolean);
    }

    /**
     * Vérifie si une URL est Google
     * @param {string} url - URL à vérifier
     * @returns {boolean}
     */
    isGoogleUrl(url) {
        const googleDomains = [
            'google.com',
            'googleapis.com',
            'googleusercontent.com',
            'youtube.com',
            'youtu.be',
            'goo.gl',
            'g.co'
        ];
        
        try {
            const urlObj = new URL(url);
            return googleDomains.some(domain => 
                urlObj.hostname.includes(domain)
            );
        } catch {
            return false;
        }
    }

    /**
     * Nettoie une URL
     * @private
     */
    cleanUrl(url) {
        // Enlever les espaces
        url = url.trim();
        
        // Ajouter https:// si manquant
        if (!url.startsWith('http')) {
            url = 'https://' + url;
        }
        
        // Enlever les paramètres inutiles
        const urlObj = new URL(url);
        const keepParams = ['v', 't', 'gid', 'id', 'src', 'cid'];
        const searchParams = new URLSearchParams();
        
        for (const [key, value] of urlObj.searchParams) {
            if (keepParams.includes(key)) {
                searchParams.set(key, value);
            }
        }
        
        urlObj.search = searchParams.toString();
        return urlObj.toString();
    }

    /**
     * Ajoute un pattern personnalisé
     * @param {Object} pattern - Pattern personnalisé
     */
    addCustomPattern(pattern) {
        if (!pattern.test || !pattern.extract) {
            throw new Error('Pattern personnalisé invalide');
        }
        this.customPatterns.push(pattern);
    }

    /**
     * Obtient tous les types supportés
     */
    getSupportedTypes() {
        return Object.keys(this.patterns);
    }

    /**
     * Obtient les infos d'un type
     */
    getTypeInfo(type) {
        return this.patterns[type] || null;
    }

    /**
     * Suggère des corrections pour une URL invalide
     * @param {string} url - URL à corriger
     * @returns {Array<string>} Suggestions
     */
    suggest(url) {
        const suggestions = [];
        
        // Essayer d'ajouter https://
        if (!url.startsWith('http')) {
            const withHttps = 'https://' + url;
            if (this.detect(withHttps)) {
                suggestions.push(withHttps);
            }
        }
        
        // Essayer de corriger les domaines courants
        const corrections = {
            'doc.google': 'docs.google',
            'drive.google': 'drive.google.com',
            'youtube': 'youtube.com',
            'youtu.be.com': 'youtu.be'
        };
        
        for (const [wrong, correct] of Object.entries(corrections)) {
            if (url.includes(wrong)) {
                const corrected = url.replace(wrong, correct);
                if (this.detect(corrected)) {
                    suggestions.push(corrected);
                }
            }
        }
        
        return suggestions;
    }

    /**
     * Efface le cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Statistiques d'utilisation
     */
    getStats() {
        const stats = {
            totalPatterns: Object.keys(this.patterns).length,
            customPatterns: this.customPatterns.length,
            cacheSize: this.cache.size,
            categories: {}
        };
        
        for (const pattern of Object.values(this.patterns)) {
            const category = pattern.category;
            stats.categories[category] = (stats.categories[category] || 0) + 1;
        }
        
        return stats;
    }
}

// Instance singleton
const Detector = new LinkDetector();

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Detector;
}

// API globale
window.Detector = Detector;