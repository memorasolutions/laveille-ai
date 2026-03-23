/**
 * Module Transformer - Cœur métier transformations
 * Tendances 2025 : Template engine, transformations dynamiques
 * Utilisé pour : transformations principales, preview, export batch
 */

class LinkTransformer {
    constructor() {
        this.transformations = this.loadDefaultTransformations();
        this.customTransformations = new Map();
        this.history = [];
        this.maxHistory = 50;
    }

    /**
     * Transformations par défaut selon devis
     */
    loadDefaultTransformations() {
        return {
            google_docs: [
                {
                    id: 'preview',
                    name: 'Prévisualisation',
                    icon: '👁️',
                    description: 'Affichage sans menus ni barres',
                    template: 'https://docs.google.com/document/d/{ID}/preview',
                    category: 'view',
                    popular: true
                },
                {
                    id: 'pdf',
                    name: 'Export PDF',
                    icon: '📄',
                    description: 'Téléchargement PDF direct',
                    template: 'https://docs.google.com/document/d/{ID}/export?format=pdf',
                    category: 'export',
                    popular: true
                },
                {
                    id: 'word',
                    name: 'Export Word',
                    icon: '📝',
                    description: 'Format Microsoft Word',
                    template: 'https://docs.google.com/document/d/{ID}/export?format=docx',
                    category: 'export'
                },
                {
                    id: 'copy',
                    name: 'Créer copie',
                    icon: '📋',
                    description: 'Force création de copie',
                    template: 'https://docs.google.com/document/d/{ID}/copy',
                    category: 'share'
                },
                {
                    id: 'edit',
                    name: 'Mode édition',
                    icon: '✏️',
                    description: 'Ouvrir en édition',
                    template: 'https://docs.google.com/document/d/{ID}/edit',
                    category: 'edit'
                },
                {
                    id: 'txt',
                    name: 'Export texte',
                    icon: '📃',
                    description: 'Texte brut',
                    template: 'https://docs.google.com/document/d/{ID}/export?format=txt',
                    category: 'export'
                },
                {
                    id: 'html',
                    name: 'Export HTML',
                    icon: '🌐',
                    description: 'Format HTML',
                    template: 'https://docs.google.com/document/d/{ID}/export?format=html',
                    category: 'export'
                },
                {
                    id: 'epub',
                    name: 'Export EPUB',
                    icon: '📚',
                    description: 'Format ebook',
                    template: 'https://docs.google.com/document/d/{ID}/export?format=epub',
                    category: 'export'
                }
            ],
            google_sheets: [
                {
                    id: 'excel',
                    name: 'Export Excel',
                    icon: '📊',
                    description: 'Format Microsoft Excel',
                    template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=xlsx',
                    category: 'export',
                    popular: true
                },
                {
                    id: 'csv',
                    name: 'Export CSV',
                    icon: '📈',
                    description: 'Valeurs séparées',
                    template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=csv',
                    category: 'export',
                    popular: true
                },
                {
                    id: 'pdf',
                    name: 'Export PDF',
                    icon: '📄',
                    description: 'Document PDF',
                    template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=pdf',
                    category: 'export'
                },
                {
                    id: 'gid',
                    name: 'Feuille spécifique',
                    icon: '📑',
                    description: 'Accès feuille par GID',
                    template: 'https://docs.google.com/spreadsheets/d/{ID}/edit#gid={GID}',
                    category: 'view',
                    requiresInput: { gid: 'Numéro GID de la feuille' }
                },
                {
                    id: 'csv_gid',
                    name: 'CSV feuille spécifique',
                    icon: '📊',
                    description: 'Export CSV d\'une feuille',
                    template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=csv&gid={GID}',
                    category: 'export',
                    requiresInput: { gid: 'Numéro GID' }
                },
                {
                    id: 'copy',
                    name: 'Créer copie',
                    icon: '📋',
                    description: 'Dupliquer le document',
                    template: 'https://docs.google.com/spreadsheets/d/{ID}/copy',
                    category: 'share'
                },
                {
                    id: 'preview',
                    name: 'Prévisualisation',
                    icon: '👁️',
                    description: 'Vue sans édition',
                    template: 'https://docs.google.com/spreadsheets/d/{ID}/preview',
                    category: 'view'
                }
            ],
            google_slides: [
                {
                    id: 'present',
                    name: 'Mode présentation',
                    icon: '🎯',
                    description: 'Lancer présentation',
                    template: 'https://docs.google.com/presentation/d/{ID}/present',
                    category: 'view',
                    popular: true
                },
                {
                    id: 'pdf',
                    name: 'Export PDF',
                    icon: '📄',
                    description: 'Document PDF',
                    template: 'https://docs.google.com/presentation/d/{ID}/export/pdf',
                    category: 'export',
                    popular: true
                },
                {
                    id: 'pptx',
                    name: 'Export PowerPoint',
                    icon: '🎞️',
                    description: 'Format PowerPoint',
                    template: 'https://docs.google.com/presentation/d/{ID}/export/pptx',
                    category: 'export'
                },
                {
                    id: 'copy',
                    name: 'Créer copie',
                    icon: '📋',
                    description: 'Dupliquer présentation',
                    template: 'https://docs.google.com/presentation/d/{ID}/copy',
                    category: 'share'
                },
                {
                    id: 'preview',
                    name: 'Prévisualisation',
                    icon: '👁️',
                    description: 'Vue sans édition',
                    template: 'https://docs.google.com/presentation/d/{ID}/preview',
                    category: 'view'
                },
                {
                    id: 'slide',
                    name: 'Slide spécifique',
                    icon: '🎯',
                    description: 'Aller à une slide',
                    template: 'https://docs.google.com/presentation/d/{ID}/present?slide={SLIDE}',
                    category: 'view',
                    requiresInput: { slide: 'Numéro de slide' }
                }
            ],
            youtube: [
                {
                    id: 'embed',
                    name: 'Code embed',
                    icon: '🎬',
                    description: 'Iframe intégration',
                    template: 'https://www.youtube.com/embed/{ID}',
                    category: 'embed',
                    popular: true
                },
                {
                    id: 'thumb_hq',
                    name: 'Miniature HD',
                    icon: '🖼️',
                    description: 'Image haute qualité',
                    template: 'https://img.youtube.com/vi/{ID}/maxresdefault.jpg',
                    category: 'media',
                    popular: true
                },
                {
                    id: 'thumb_mq',
                    name: 'Miniature moyenne',
                    icon: '🖼️',
                    description: 'Qualité moyenne',
                    template: 'https://img.youtube.com/vi/{ID}/mqdefault.jpg',
                    category: 'media'
                },
                {
                    id: 'iframe',
                    name: 'Iframe complet',
                    icon: '📱',
                    description: 'Code HTML iframe',
                    template: '<iframe width="{WIDTH}" height="{HEIGHT}" src="https://www.youtube.com/embed/{ID}" frameborder="0" allowfullscreen></iframe>',
                    category: 'embed',
                    requiresInput: { 
                        width: 'Largeur (px)',
                        height: 'Hauteur (px)'
                    },
                    defaultValues: { width: '560', height: '315' }
                },
                {
                    id: 'time',
                    name: 'Avec timestamp',
                    icon: '⏱️',
                    description: 'Démarrer à un moment',
                    template: 'https://www.youtube.com/watch?v={ID}&t={TIME}',
                    category: 'view',
                    requiresInput: { time: 'Secondes' }
                },
                {
                    id: 'short',
                    name: 'Lien court',
                    icon: '🔗',
                    description: 'URL raccourcie',
                    template: 'https://youtu.be/{ID}',
                    category: 'share'
                }
            ],
            google_drive: [
                {
                    id: 'preview',
                    name: 'Prévisualisation',
                    icon: '👁️',
                    description: 'Vue intégrée',
                    template: 'https://drive.google.com/file/d/{ID}/preview',
                    category: 'view',
                    popular: true
                },
                {
                    id: 'download',
                    name: 'Téléchargement',
                    icon: '⬇️',
                    description: 'Télécharger direct',
                    template: 'https://drive.google.com/uc?export=download&id={ID}',
                    category: 'download',
                    popular: true
                },
                {
                    id: 'view',
                    name: 'Vue web',
                    icon: '🌐',
                    description: 'Ouvrir dans Drive',
                    template: 'https://drive.google.com/file/d/{ID}/view',
                    category: 'view'
                },
                {
                    id: 'embed',
                    name: 'Embed iframe',
                    icon: '📱',
                    description: 'Code intégration',
                    template: '<iframe src="https://drive.google.com/file/d/{ID}/preview" width="{WIDTH}" height="{HEIGHT}"></iframe>',
                    category: 'embed',
                    requiresInput: {
                        width: 'Largeur',
                        height: 'Hauteur'
                    },
                    defaultValues: { width: '640', height: '480' }
                }
            ],
            google_forms: [
                {
                    id: 'view',
                    name: 'Formulaire',
                    icon: '📝',
                    description: 'Remplir formulaire',
                    template: 'https://docs.google.com/forms/d/{ID}/viewform',
                    category: 'view',
                    popular: true
                },
                {
                    id: 'edit',
                    name: 'Mode édition',
                    icon: '✏️',
                    description: 'Modifier formulaire',
                    template: 'https://docs.google.com/forms/d/{ID}/edit',
                    category: 'edit'
                },
                {
                    id: 'responses',
                    name: 'Voir réponses',
                    icon: '📊',
                    description: 'Analytics réponses',
                    template: 'https://docs.google.com/forms/d/{ID}/viewanalytics',
                    category: 'analytics'
                },
                {
                    id: 'prefill',
                    name: 'Pré-remplir',
                    icon: '✍️',
                    description: 'Lien pré-rempli',
                    template: 'https://docs.google.com/forms/d/{ID}/viewform?entry.{FIELD}={VALUE}',
                    category: 'share',
                    requiresInput: {
                        field: 'ID du champ',
                        value: 'Valeur'
                    }
                }
            ]
        };
    }

    /**
     * Applique une transformation
     * @param {Object} detection - Résultat de détection
     * @param {string} transformId - ID de transformation
     * @param {Object} inputs - Valeurs pour les variables
     * @returns {string} URL transformée
     */
    transform(detection, transformId, inputs = {}) {
        const transforms = this.getTransformations(detection.type);
        const transform = transforms.find(t => t.id === transformId);
        
        if (!transform) {
            throw new Error(`Transformation ${transformId} non trouvée`);
        }
        
        let result = transform.template;
        
        // Remplacer l'ID principal
        result = result.replace(/{ID}/g, detection.fileId);
        
        // Remplacer les métadonnées détectées
        if (detection.gid) {
            result = result.replace(/{GID}/g, detection.gid);
        }
        if (detection.timestamp) {
            result = result.replace(/{TIME}/g, detection.timestamp);
        }
        
        // Remplacer les inputs utilisateur
        Object.entries(inputs).forEach(([key, value]) => {
            const regex = new RegExp(`{${key.toUpperCase()}}`, 'g');
            result = result.replace(regex, value);
        });
        
        // Utiliser les valeurs par défaut si présentes
        if (transform.defaultValues) {
            Object.entries(transform.defaultValues).forEach(([key, value]) => {
                const regex = new RegExp(`{${key.toUpperCase()}}`, 'g');
                if (result.includes(`{${key.toUpperCase()}}`)) {
                    result = result.replace(regex, value);
                }
            });
        }
        
        // Ajouter à l'historique
        this.addToHistory({
            detection,
            transform,
            result,
            timestamp: Date.now()
        });
        
        return result;
    }

    /**
     * Obtient les transformations pour un type
     * @param {string} type - Type de lien
     * @returns {Array} Liste des transformations
     */
    getTransformations(type) {
        const defaults = this.transformations[type] || [];
        const customs = this.customTransformations.get(type) || [];
        return [...defaults, ...customs];
    }

    /**
     * Obtient les transformations populaires
     * @param {string} type - Type de lien
     * @returns {Array} Transformations populaires
     */
    getPopularTransformations(type) {
        return this.getTransformations(type).filter(t => t.popular);
    }

    /**
     * Obtient les transformations par catégorie
     * @param {string} type - Type de lien
     * @returns {Object} Transformations groupées
     */
    getTransformationsByCategory(type) {
        const transforms = this.getTransformations(type);
        const grouped = {};
        
        transforms.forEach(t => {
            if (!grouped[t.category]) {
                grouped[t.category] = [];
            }
            grouped[t.category].push(t);
        });
        
        return grouped;
    }

    /**
     * Transforme en batch (plusieurs transformations)
     * @param {Object} detection - Résultat de détection
     * @param {Array} transformIds - IDs des transformations
     * @returns {Array} Résultats transformés
     */
    transformBatch(detection, transformIds) {
        return transformIds.map(id => {
            try {
                return {
                    id,
                    success: true,
                    result: this.transform(detection, id)
                };
            } catch (error) {
                return {
                    id,
                    success: false,
                    error: error.message
                };
            }
        });
    }

    /**
     * Obtient toutes les transformations d'un lien
     * @param {Object} detection - Résultat de détection
     * @returns {Array} Toutes les URLs transformées
     */
    getAllTransformations(detection) {
        const transforms = this.getTransformations(detection.type);
        const results = [];
        
        transforms.forEach(t => {
            // Skip les transformations qui nécessitent des inputs
            if (t.requiresInput && !t.defaultValues) return;
            
            try {
                const result = this.transform(detection, t.id);
                results.push({
                    ...t,
                    url: result
                });
            } catch (error) {
                console.warn(`Transformation ${t.id} échouée:`, error);
            }
        });
        
        return results;
    }

    /**
     * Ajoute une transformation personnalisée
     * @param {string} type - Type de lien
     * @param {Object} transformation - Nouvelle transformation
     */
    addCustomTransformation(type, transformation) {
        if (!this.customTransformations.has(type)) {
            this.customTransformations.set(type, []);
        }
        
        const customs = this.customTransformations.get(type);
        
        // Vérifier l'unicité de l'ID
        if (customs.some(t => t.id === transformation.id)) {
            throw new Error(`Transformation ${transformation.id} existe déjà`);
        }
        
        customs.push(transformation);
    }

    /**
     * Génère un code embed HTML
     * @param {string} url - URL à intégrer
     * @param {Object} options - Options d'intégration
     * @returns {string} Code HTML
     */
    generateEmbed(url, options = {}) {
        const {
            width = '100%',
            height = '500px',
            title = 'Document intégré',
            responsive = true
        } = options;
        
        if (responsive) {
            return `<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
    <iframe src="${url}" 
            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
            frameborder="0"
            title="${title}"
            allowfullscreen>
    </iframe>
</div>`;
        }
        
        return `<iframe src="${url}" 
        width="${width}" 
        height="${height}"
        frameborder="0"
        title="${title}"
        allowfullscreen>
</iframe>`;
    }

    /**
     * Ajoute à l'historique
     * @private
     */
    addToHistory(item) {
        this.history.unshift(item);
        if (this.history.length > this.maxHistory) {
            this.history = this.history.slice(0, this.maxHistory);
        }
    }

    /**
     * Obtient l'historique
     */
    getHistory() {
        return [...this.history];
    }

    /**
     * Statistiques d'utilisation
     */
    getStats() {
        const stats = {
            totalTransformations: 0,
            byType: {},
            byCategory: {},
            popularUsed: 0
        };
        
        this.history.forEach(item => {
            stats.totalTransformations++;
            
            const type = item.detection.type;
            stats.byType[type] = (stats.byType[type] || 0) + 1;
            
            const category = item.transform.category;
            stats.byCategory[category] = (stats.byCategory[category] || 0) + 1;
            
            if (item.transform.popular) {
                stats.popularUsed++;
            }
        });
        
        return stats;
    }
}

// Instance singleton
const Transformer = new LinkTransformer();

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Transformer;
}

// API globale
window.Transformer = Transformer;