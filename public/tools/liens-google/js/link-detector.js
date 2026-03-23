/**
 * Module de détection des liens Google
 * Détecte et analyse tous les types de liens Google
 */

class LinkDetector {
    constructor(patterns = null) {
        // Utiliser AppConfig si disponible
        if (!patterns && typeof AppConfig !== 'undefined' && AppConfig.patterns) {
            this.patterns = AppConfig.patterns;
        } else {
            this.patterns = patterns || this.getDefaultPatterns();
        }
    }

    getDefaultPatterns() {
        return {
            google_docs: {
                regex: /docs\.google\.com\/document\/d\/([a-zA-Z0-9-_]+)/,
                name: 'Google Docs',
                icon: '📄',
                category: 'document'
            },
            google_sheets: {
                regex: /docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/,
                name: 'Google Sheets',
                icon: '📊',
                category: 'spreadsheet',
                support_gid: true
            },
            google_slides: {
                regex: /docs\.google\.com\/presentation\/d\/([a-zA-Z0-9-_]+)/,
                name: 'Google Slides',
                icon: '🎯',
                category: 'presentation'
            },
            google_forms: {
                // Tous les formulaires Google
                regex: /docs\.google\.com\/forms\/d\/([^\/]+)/,
                name: 'Google Forms', 
                icon: '📝',
                category: 'form',
                extractId: (match, url) => {
                    // Si le match contient "e/ID", extraire juste l'ID
                    const captured = match[1];
                    if (captured.startsWith('e/')) {
                        return captured.substring(2);
                    }
                    return captured;
                }
            },
            google_drive: {
                regex: /drive\.google\.com\/(?:file\/d\/|open\?id=)([a-zA-Z0-9-_]+)/,
                name: 'Google Drive',
                icon: '💾',
                category: 'file'
            },
            youtube: {
                regex: /(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9-_]{11})/,
                name: 'YouTube',
                icon: '🎬',
                category: 'video',
                support_timestamp: true
            },
            youtube_shorts: {
                regex: /youtube\.com\/shorts\/([a-zA-Z0-9-_]{11})/,
                name: 'YouTube Shorts',
                icon: '📱',
                category: 'video'
            },
            google_maps: {
                regex: /(?:maps\.google\.com|goo\.gl\/maps)\/([a-zA-Z0-9-_]+)/,
                name: 'Google Maps',
                icon: '🗺️',
                category: 'maps'
            },
            google_photos: {
                regex: /photos\.google\.com\/share\/([a-zA-Z0-9-_]+)/,
                name: 'Google Photos',
                icon: '📷',
                category: 'media'
            },
            google_calendar: {
                regex: /calendar\.google\.com\/calendar\/(?:u\/0\/)?(?:embed\?src=|r\?cid=)([a-zA-Z0-9-_@.%]+)/,
                name: 'Google Calendar',
                icon: '📅',
                category: 'calendar'
            },
            google_meet: {
                regex: /meet\.google\.com\/([a-z]{3}-[a-z]{4}-[a-z]{3})/,
                name: 'Google Meet',
                icon: '📹',
                category: 'meeting'
            }
        };
    }

    detect(url) {
        if (!url || typeof url !== 'string') {
            return null;
        }

        // Nettoyer l'URL
        url = url.trim();
        
        // Essayer chaque pattern
        for (const [type, pattern] of Object.entries(this.patterns)) {
            const regex = pattern.regex instanceof RegExp 
                ? pattern.regex 
                : new RegExp(pattern.regex);
                
            const match = url.match(regex);
            
            if (match) {
                // Utiliser extractId si disponible, sinon utiliser match[1]
                const id = pattern.extractId ? pattern.extractId(match, url) : match[1];
                
                // Utiliser type_override si défini (pour google_forms_published -> google_forms)
                const finalType = pattern.type_override || type;
                
                const detection = {
                    type: finalType,
                    id: id,
                    url: url,
                    name: pattern.name,
                    icon: pattern.icon,
                    category: pattern.category,
                    metadata: {
                        ...pattern
                    }
                };

                // Extraire des métadonnées supplémentaires
                this.extractAdditionalMetadata(detection, url);
                
                return detection;
            }
        }

        return null;
    }

    extractAdditionalMetadata(detection, url) {
        // Extraire GID pour Google Sheets
        if (detection.type === 'google_sheets') {
            const gidMatch = url.match(/#gid=(\d+)/);
            if (gidMatch) {
                detection.gid = gidMatch[1];
                detection.metadata.gid = gidMatch[1];
            }
        }

        // Extraire timestamp pour YouTube
        if (detection.type === 'youtube') {
            const timeMatch = url.match(/[?&]t=(\d+)/);
            if (timeMatch) {
                detection.timestamp = timeMatch[1];
                detection.metadata.timestamp = timeMatch[1];
            }
        }

        // Extraire les paramètres de vue pour Google Docs
        if (detection.type === 'google_docs') {
            if (url.includes('/edit')) {
                detection.mode = 'edit';
            } else if (url.includes('/view')) {
                detection.mode = 'view';
            } else if (url.includes('/preview')) {
                detection.mode = 'preview';
            }
        }

        // Extraire le type de fichier pour Google Drive
        if (detection.type === 'google_drive') {
            if (url.includes('/folders/')) {
                detection.fileType = 'folder';
            } else if (url.includes('/file/')) {
                detection.fileType = 'file';
            }
        }

        // Extraire les coordonnées pour Google Maps
        if (detection.type === 'google_maps') {
            const coordsMatch = url.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
            if (coordsMatch) {
                detection.coordinates = {
                    lat: parseFloat(coordsMatch[1]),
                    lng: parseFloat(coordsMatch[2])
                };
            }
        }
    }

    validateUrl(url) {
        try {
            const urlObj = new URL(url);
            const allowedDomains = [
                'google.com',
                'googleapis.com',
                'googleusercontent.com',
                'youtube.com',
                'youtu.be',
                'goo.gl'
            ];
            
            return allowedDomains.some(domain => 
                urlObj.hostname.includes(domain)
            );
        } catch {
            return false;
        }
    }

    suggest(url) {
        const suggestions = [];
        
        // Vérifier si c'est presque un lien Google
        if (url.includes('google') || url.includes('youtube')) {
            suggestions.push('Vérifiez que l\'URL est complète');
        }
        
        // Vérifier les patterns communs mal formés
        if (url.includes('/d/') && !url.includes('docs.google.com')) {
            suggestions.push('Essayez avec https://docs.google.com/...');
        }
        
        if (url.includes('watch?v=') && !url.includes('youtube.com')) {
            suggestions.push('Essayez avec https://www.youtube.com/...');
        }
        
        return suggestions;
    }

    getAllTypes() {
        return Object.keys(this.patterns).map(type => ({
            type: type,
            ...this.patterns[type]
        }));
    }

    getTypeInfo(type) {
        return this.patterns[type] || null;
    }

    isSupported(url) {
        return this.detect(url) !== null;
    }

    extractId(url, type = null) {
        if (type && this.patterns[type]) {
            const regex = this.patterns[type].regex instanceof RegExp 
                ? this.patterns[type].regex 
                : new RegExp(this.patterns[type].regex);
            const match = url.match(regex);
            return match ? match[1] : null;
        }
        
        const detection = this.detect(url);
        return detection ? detection.id : null;
    }
}

// Export pour utilisation
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LinkDetector;
}