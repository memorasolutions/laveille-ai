/**
 * Module GoogleTransformerSimple - Transformation simple des liens Google
 * Keep it simple - Une seule responsabilité : transformer les liens
 * Utilise GoogleIdExtractor pour l'extraction d'ID
 */

class GoogleTransformerSimple {
    constructor() {
        this.extractor = typeof googleIdExtractor !== 'undefined' ? googleIdExtractor : new GoogleIdExtractor();
        
        // Templates de transformation pour chaque service
        this.templates = {
            forms: this.getFormsTemplates(),
            docs: this.getDocsTemplates(),
            sheets: this.getSheetsTemplates(),
            slides: this.getSlidesTemplates(),
            drive: this.getDriveTemplates(),
            youtube: this.getYouTubeTemplates()
        };
    }

    getFormsTemplates() {
        return [
            { id: 'view', name: 'Voir formulaire', icon: '📝', category: 'view', popular: true, build: (id, isPub) => 
                isPub ? `https://docs.google.com/forms/d/e/${id}/viewform` 
                      : `https://docs.google.com/forms/d/${id}/viewform` },
            
            { id: 'edit', name: 'Mode édition', icon: '✏️', category: 'edit', build: (id) => 
                `https://docs.google.com/forms/d/${id}/edit` },
            
            { id: 'responses', name: 'Voir réponses', icon: '📊', category: 'view', popular: true, build: (id) => 
                `https://docs.google.com/forms/d/${id}/viewanalytics` },
            
            { id: 'submit', name: 'URL de soumission', icon: '📤', category: 'other', build: (id, isPub) => 
                isPub ? `https://docs.google.com/forms/d/e/${id}/formResponse` 
                      : `https://docs.google.com/forms/d/${id}/formResponse` }
        ];
    }

    getDocsTemplates() {
        return [
            { id: 'view', name: 'Prévisualisation', icon: '👁️', category: 'view', popular: true, build: (id) => 
                `https://docs.google.com/document/d/${id}/preview` },
            
            { id: 'edit', name: 'Mode édition', icon: '✏️', category: 'edit', popular: true, build: (id) => 
                `https://docs.google.com/document/d/${id}/edit` },
            
            { id: 'pdf', name: 'Export PDF', icon: '📥', category: 'export', popular: true, build: (id) => 
                `https://docs.google.com/document/d/${id}/export?format=pdf` },
            
            { id: 'copy', name: 'Créer copie', icon: '📋', category: 'edit', build: (id) => 
                `https://docs.google.com/document/d/${id}/copy` },
            
            { id: 'docx', name: 'Export Word', icon: '📄', category: 'export', build: (id) => 
                `https://docs.google.com/document/d/${id}/export?format=docx` },
            
            { id: 'txt', name: 'Export Texte', icon: '📃', category: 'export', build: (id) => 
                `https://docs.google.com/document/d/${id}/export?format=txt` }
        ];
    }

    getSheetsTemplates() {
        return [
            { id: 'edit', name: 'Mode édition', icon: '✏️', category: 'edit', popular: true, build: (id) => 
                `https://docs.google.com/spreadsheets/d/${id}/edit` },
            
            { id: 'pdf', name: 'Export PDF', icon: '📥', category: 'export', popular: true, build: (id) => 
                `https://docs.google.com/spreadsheets/d/${id}/export?format=pdf` },
            
            { id: 'excel', name: 'Export Excel', icon: '📊', category: 'export', build: (id) => 
                `https://docs.google.com/spreadsheets/d/${id}/export?format=xlsx` },
            
            { id: 'csv', name: 'Export CSV', icon: '📈', category: 'export', build: (id) => 
                `https://docs.google.com/spreadsheets/d/${id}/export?format=csv` },
            
            { id: 'copy', name: 'Créer copie', icon: '📋', category: 'edit', build: (id) => 
                `https://docs.google.com/spreadsheets/d/${id}/copy` },
            
            { id: 'preview', name: 'Aperçu HTML', icon: '🌐', category: 'view', build: (id) => 
                `https://docs.google.com/spreadsheets/d/${id}/preview` }
        ];
    }

    getSlidesTemplates() {
        return [
            { id: 'present', name: 'Mode présentation', icon: '🎯', category: 'view', popular: true, build: (id) => 
                `https://docs.google.com/presentation/d/${id}/present` },
            
            { id: 'edit', name: 'Mode édition', icon: '✏️', category: 'edit', popular: true, build: (id) => 
                `https://docs.google.com/presentation/d/${id}/edit` },
            
            { id: 'pdf', name: 'Export PDF', icon: '📥', category: 'export', popular: true, build: (id) => 
                `https://docs.google.com/presentation/d/${id}/export?format=pdf` },
            
            { id: 'pptx', name: 'Export PowerPoint', icon: '📁', category: 'export', build: (id) => 
                `https://docs.google.com/presentation/d/${id}/export?format=pptx` },
            
            { id: 'copy', name: 'Créer copie', icon: '📋', category: 'edit', build: (id) => 
                `https://docs.google.com/presentation/d/${id}/copy` }
        ];
    }

    getDriveTemplates() {
        return [
            { id: 'view', name: 'Voir', icon: '👁️', build: (id) => 
                `https://drive.google.com/file/d/${id}/view` },
            
            { id: 'download', name: 'Télécharger', icon: '⬇️', build: (id) => 
                `https://drive.google.com/uc?export=download&id=${id}` },
            
            { id: 'preview', name: 'Prévisualisation', icon: '🔍', build: (id) => 
                `https://drive.google.com/file/d/${id}/preview` }
        ];
    }

    getYouTubeTemplates() {
        return [
            { id: 'watch', name: 'Regarder', icon: '▶️', build: (id) => 
                `https://www.youtube.com/watch?v=${id}` },
            
            { id: 'embed', name: 'Code embed', icon: '📎', build: (id) => 
                `https://www.youtube.com/embed/${id}` },
            
            { id: 'thumbnail', name: 'Miniature', icon: '🖼️', build: (id) => 
                `https://img.youtube.com/vi/${id}/maxresdefault.jpg` }
        ];
    }

    /**
     * Transforme une URL Google selon le template demandé
     * @param {string} url - L'URL à transformer
     * @param {string} templateId - L'ID du template (edit, pdf, etc.)
     * @returns {string|null} L'URL transformée ou null
     */
    transform(url, templateId) {
        // Extraire l'ID et le service
        const extracted = this.extractor.extract(url);
        if (!extracted) return null;

        // Obtenir les templates pour ce service
        const serviceTemplates = this.templates[extracted.service];
        if (!serviceTemplates) return null;

        // Trouver le template demandé
        const template = serviceTemplates.find(t => t.id === templateId);
        if (!template) return null;

        // Construire l'URL
        return template.build(extracted.id, extracted.isPublishedForm);
    }

    /**
     * Obtient toutes les transformations possibles pour une URL
     * @param {string} url - L'URL à analyser
     * @returns {Array} Liste des transformations disponibles
     */
    getAllTransformations(url) {
        const extracted = this.extractor.extract(url);
        if (!extracted) return [];

        const serviceTemplates = this.templates[extracted.service];
        if (!serviceTemplates) return [];

        return serviceTemplates.map(template => ({
            id: template.id,
            name: template.name,
            icon: template.icon,
            category: template.category || 'other',
            popular: template.popular || false,
            url: template.build(extracted.id, extracted.isPublishedForm)
        }));
    }

    /**
     * Détecte le type de service Google
     * @param {string} url - L'URL à analyser
     * @returns {Object|null} Information sur le service détecté
     */
    detect(url) {
        const extracted = this.extractor.extract(url);
        if (!extracted) return null;

        // Mapper les noms de service pour l'affichage
        const serviceNames = {
            'forms': 'Google Forms',
            'docs': 'Google Docs',
            'sheets': 'Google Sheets',
            'slides': 'Google Slides',
            'drive': 'Google Drive',
            'youtube': 'YouTube'
        };

        return {
            type: `google_${extracted.service}`,
            id: extracted.id,
            service: extracted.service,
            name: serviceNames[extracted.service] || 'Google',
            icon: this.getServiceIcon(extracted.service),
            isPublishedForm: extracted.isPublishedForm,
            originalUrl: url
        };
    }

    /**
     * Obtient l'icône pour un service
     */
    getServiceIcon(service) {
        const icons = {
            'forms': '📝',
            'docs': '📄',
            'sheets': '📊',
            'slides': '🎯',
            'drive': '💾',
            'youtube': '🎬'
        };
        return icons[service] || '🔗';
    }
}

// Export singleton
const googleTransformerSimple = new GoogleTransformerSimple();

// Export pour les tests
if (typeof module !== 'undefined' && module.exports) {
    module.exports = googleTransformerSimple;
}