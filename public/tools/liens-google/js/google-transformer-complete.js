/**
 * Module GoogleTransformerComplete - Transformation COMPLÈTE des liens Google (2025)
 * Keep it simple - Toutes les transformations possibles selon les dernières APIs
 * Basé sur les recherches internet de décembre 2025
 */

class GoogleTransformerComplete {
    constructor() {
        this.extractor = typeof googleIdExtractor !== 'undefined' ? googleIdExtractor : new GoogleIdExtractor();
        
        // Templates COMPLETS pour chaque service (2025)
        this.templates = {
            forms: this.getFormsTemplates(),
            docs: this.getDocsTemplates(),
            sheets: this.getSheetsTemplates(),
            slides: this.getSlidesTemplates(),
            drive: this.getDriveTemplates(),
            youtube: this.getYouTubeTemplates(),
            calendar: this.getCalendarTemplates()
        };
    }

    getFormsTemplates() {
        return [
            // Visualisation
            { id: 'view', name: 'Voir formulaire', icon: '📝', category: 'view', popular: true, 
                build: (id, isPub) => isPub ? `https://docs.google.com/forms/d/e/${id}/viewform` 
                                            : `https://docs.google.com/forms/d/${id}/viewform` },
            
            { id: 'prefill', name: 'Pré-remplir', icon: '✍️', category: 'view',
                build: (id, isPub) => isPub ? `https://docs.google.com/forms/d/e/${id}/viewform?entry.xxx=value` 
                                            : `https://docs.google.com/forms/d/${id}/viewform?entry.xxx=value`,
                note: 'Remplacer xxx par l\'ID du champ' },
            
            // Édition
            { id: 'edit', name: 'Mode édition', icon: '✏️', category: 'edit', 
                build: (id) => `https://docs.google.com/forms/d/${id}/edit` },
            
            { id: 'copy', name: 'Créer copie', icon: '📋', category: 'edit',
                build: (id) => `https://docs.google.com/forms/d/${id}/copy` },
            
            // Réponses
            { id: 'responses', name: 'Voir réponses', icon: '📊', category: 'view', popular: true,
                build: (id) => `https://docs.google.com/forms/d/${id}/viewanalytics` },
            
            { id: 'spreadsheet', name: 'Réponses Sheets', icon: '📈', category: 'view',
                build: (id) => `https://docs.google.com/forms/d/${id}/edit#responses`,
                note: 'Voir dans Google Sheets' },
            
            // Soumission
            { id: 'submit', name: 'URL soumission', icon: '📤', category: 'other',
                build: (id, isPub) => isPub ? `https://docs.google.com/forms/d/e/${id}/formResponse` 
                                            : `https://docs.google.com/forms/d/${id}/formResponse` }
        ];
    }

    getDocsTemplates() {
        return [
            // Visualisation
            { id: 'view', name: 'Prévisualisation', icon: '👁️', category: 'view', popular: true,
                build: (id) => `https://docs.google.com/document/d/${id}/preview` },
            
            { id: 'preview-minimal', name: 'Aperçu minimaliste', icon: '📄', category: 'view',
                build: (id) => `https://docs.google.com/document/d/${id}/preview?rm=minimal` },
            
            { id: 'preview-embedded', name: 'Aperçu intégré', icon: '🔲', category: 'view',
                build: (id) => `https://docs.google.com/document/d/${id}/preview?rm=embedded` },
            
            { id: 'mobilebasic', name: 'Vue mobile', icon: '📱', category: 'view',
                build: (id) => `https://docs.google.com/document/d/${id}/mobilebasic` },
            
            // Édition
            { id: 'edit', name: 'Mode édition', icon: '✏️', category: 'edit', popular: true,
                build: (id) => `https://docs.google.com/document/d/${id}/edit` },
            
            { id: 'edit-minimal', name: 'Édition minimale', icon: '✍️', category: 'edit',
                build: (id) => `https://docs.google.com/document/d/${id}/edit?rm=minimal` },
            
            { id: 'copy', name: 'Créer copie', icon: '📋', category: 'edit',
                build: (id) => `https://docs.google.com/document/d/${id}/copy` },
            
            { id: 'template', name: 'Utiliser comme modèle', icon: '📃', category: 'edit',
                build: (id) => `https://docs.google.com/document/d/${id}/template/preview` },
            
            // Export
            { id: 'pdf', name: 'Export PDF', icon: '📥', category: 'export', popular: true,
                build: (id) => `https://docs.google.com/document/d/${id}/export?format=pdf` },
            
            { id: 'docx', name: 'Export Word', icon: '📄', category: 'export', popular: true,
                build: (id) => `https://docs.google.com/document/d/${id}/export?format=docx` },
            
            { id: 'txt', name: 'Export Texte', icon: '📃', category: 'export',
                build: (id) => `https://docs.google.com/document/d/${id}/export?format=txt` },
            
            { id: 'rtf', name: 'Export RTF', icon: '📝', category: 'export',
                build: (id) => `https://docs.google.com/document/d/${id}/export?format=rtf` },
            
            { id: 'html', name: 'Export HTML', icon: '🌐', category: 'export',
                build: (id) => `https://docs.google.com/document/d/${id}/export?format=html` },
            
            { id: 'odt', name: 'Export OpenDocument', icon: '📑', category: 'export',
                build: (id) => `https://docs.google.com/document/d/${id}/export?format=odt` },
            
            { id: 'epub', name: 'Export EPUB', icon: '📖', category: 'export',
                build: (id) => `https://docs.google.com/document/d/${id}/export?format=epub` },
            
            { id: 'zip', name: 'Export ZIP (HTML)', icon: '🗜️', category: 'export',
                build: (id) => `https://docs.google.com/document/d/${id}/export?format=zip` }
        ];
    }

    getSheetsTemplates() {
        return [
            // Les plus importants en premier avec popular: true
            { id: 'edit', name: 'Mode édition', icon: '✏️', category: 'edit', popular: true,
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/edit` },
            
            { id: 'preview', name: 'Aperçu', icon: '👁️', category: 'view', popular: true,
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/preview` },
            
            { id: 'pdf', name: 'Export PDF', icon: '📥', category: 'export', popular: true,
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/export?format=pdf` },
            
            { id: 'excel', name: 'Export Excel', icon: '📊', category: 'export', popular: true,
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/export?format=xlsx` },
            
            { id: 'csv', name: 'Export CSV', icon: '📈', category: 'export', popular: true,
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/export?format=csv` },
            
            { id: 'copy', name: 'Créer copie', icon: '📋', category: 'edit', popular: true,
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/copy` },
            
            // htmlview et pubhtml supprimés - utilisez EndpointValidator pour validation
            
            // Autres formats d'export
            { id: 'tsv', name: 'Export TSV', icon: '📉', category: 'export',
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/export?format=tsv` },
            
            { id: 'ods', name: 'Export OpenDocument', icon: '📑', category: 'export',
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/export?format=ods` },
            
            { id: 'zip', name: 'Export ZIP', icon: '🗜️', category: 'export',
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/export?format=zip` },
            
            // Spécifique feuille (gardé car utile pour CSV avec gid spécifique)
            { id: 'csv-sheet', name: 'CSV feuille active', icon: '📊', category: 'advanced',
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/export?format=csv&gid=0`,
                note: 'Remplacer gid=0 par l\'ID de la feuille' },
            
            { id: 'template', name: 'Utiliser comme modèle', icon: '📃', category: 'edit',
                build: (id) => `https://docs.google.com/spreadsheets/d/${id}/template/preview` }
        ];
    }

    getSlidesTemplates() {
        return [
            // Présentation
            { id: 'present', name: 'Mode présentation', icon: '🎯', category: 'view', popular: true,
                build: (id) => `https://docs.google.com/presentation/d/${id}/present` },
            
            { id: 'present-auto', name: 'Présentation auto', icon: '▶️', category: 'view',
                build: (id) => `https://docs.google.com/presentation/d/${id}/present?start=true&loop=true&delayms=5000`,
                note: 'Défile automatiquement toutes les 5 secondes' },
            
            { id: 'embed', name: 'Vue intégrée', icon: '🔲', category: 'view',
                build: (id) => `https://docs.google.com/presentation/d/${id}/embed` },
            
            { id: 'preview', name: 'Aperçu', icon: '👁️', category: 'view',
                build: (id) => `https://docs.google.com/presentation/d/${id}/preview` },
            
            // Édition
            { id: 'edit', name: 'Mode édition', icon: '✏️', category: 'edit', popular: true,
                build: (id) => `https://docs.google.com/presentation/d/${id}/edit` },
            
            { id: 'copy', name: 'Créer copie', icon: '📋', category: 'edit',
                build: (id) => `https://docs.google.com/presentation/d/${id}/copy` },
            
            { id: 'template', name: 'Utiliser comme modèle', icon: '📃', category: 'edit',
                build: (id) => `https://docs.google.com/presentation/d/${id}/template/preview` },
            
            // Export
            { id: 'pdf', name: 'Export PDF', icon: '📥', category: 'export', popular: true,
                build: (id) => `https://docs.google.com/presentation/d/${id}/export?format=pdf` },
            
            { id: 'pptx', name: 'Export PowerPoint', icon: '📁', category: 'export', popular: true,
                build: (id) => `https://docs.google.com/presentation/d/${id}/export?format=pptx` },
            
            { id: 'odp', name: 'Export OpenDocument', icon: '📑', category: 'export',
                build: (id) => `https://docs.google.com/presentation/d/${id}/export?format=odp` },
            
            { id: 'txt', name: 'Export Texte', icon: '📃', category: 'export',
                build: (id) => `https://docs.google.com/presentation/d/${id}/export?format=txt` },
            
            { id: 'jpeg', name: 'Export JPEG (1ère)', icon: '🖼️', category: 'export',
                build: (id) => `https://docs.google.com/presentation/d/${id}/export?format=jpeg`,
                note: 'Exporte uniquement la première diapositive' },
            
            { id: 'png', name: 'Export PNG (1ère)', icon: '🎨', category: 'export',
                build: (id) => `https://docs.google.com/presentation/d/${id}/export?format=png`,
                note: 'Exporte uniquement la première diapositive' },
            
            { id: 'svg', name: 'Export SVG (1ère)', icon: '🎭', category: 'export',
                build: (id) => `https://docs.google.com/presentation/d/${id}/export?format=svg`,
                note: 'Exporte uniquement la première diapositive' }
        ];
    }

    getDriveTemplates() {
        return [
            // Visualisation
            { id: 'view', name: 'Voir', icon: '👁️', category: 'view', popular: true,
                build: (id) => `https://drive.google.com/file/d/${id}/view` },
            
            { id: 'preview', name: 'Prévisualisation', icon: '🔍', category: 'view',
                build: (id) => `https://drive.google.com/file/d/${id}/preview` },
            
            // Téléchargement
            { id: 'download', name: 'Télécharger direct', icon: '⬇️', category: 'download', popular: true,
                build: (id) => `https://drive.google.com/uc?export=download&id=${id}` },
            
            { id: 'view-embed', name: 'Vue intégrée', icon: '🔲', category: 'view',
                build: (id) => `https://drive.google.com/uc?export=view&id=${id}` },
            
            // Images (Formats 2025)
            { id: 'image-direct', name: 'Image directe', icon: '🖼️', category: 'media', popular: true,
                build: (id) => `https://drive.google.com/uc?id=${id}`,
                note: 'Lien direct pour intégration web' },
            
            { id: 'image-size', name: 'Image redimensionnée', icon: '📐', category: 'media', 
                requiresOptions: true, optionsType: 'image-size',
                build: (id) => `https://drive.google.com/thumbnail?id=${id}&sz=w1000`,
                note: 'Largeur personnalisable (proportions conservées)' },
            
            // Partage
            { id: 'copy', name: 'Créer copie', icon: '📋', category: 'edit',
                build: (id) => `https://drive.google.com/file/d/${id}/copy` },
            
            { id: 'open-with', name: 'Ouvrir avec...', icon: '🔧', category: 'edit',
                build: (id) => `https://drive.google.com/file/d/${id}/edit` }
        ];
    }

    getYouTubeTemplates() {
        return [
            // Visualisation
            { id: 'watch', name: 'Regarder', icon: '▶️', category: 'view', popular: true,
                build: (id) => `https://www.youtube.com/watch?v=${id}` },
            
            { id: 'watch-time', name: 'Regarder à partir de...', icon: '⏱️', category: 'view', popular: true,
                build: (id, options = {}) => {
                    const time = options.startTime || '0';
                    return `https://www.youtube.com/watch?v=${id}&t=${time}`;
                },
                requiresOptions: true,
                optionsType: 'youtube-time' },
            
            // Intégration
            { id: 'embed', name: 'Code embed', icon: '📎', category: 'embed', popular: true,
                build: (id) => `https://www.youtube.com/embed/${id}` },
            
            { id: 'embed-autoplay', name: 'Embed autoplay', icon: '▶️', category: 'embed',
                build: (id) => `https://www.youtube.com/embed/${id}?autoplay=1&mute=1` },
            
            { id: 'embed-loop', name: 'Embed en boucle', icon: '🔁', category: 'embed',
                build: (id) => `https://www.youtube.com/embed/${id}?loop=1&playlist=${id}` },
            
            { id: 'embed-controls', name: 'Embed sans contrôles', icon: '🚫', category: 'embed',
                build: (id) => `https://www.youtube.com/embed/${id}?controls=0` },
            
            { id: 'embed-full', name: 'Embed complet', icon: '🎬', category: 'embed',
                build: (id) => `https://www.youtube.com/embed/${id}?autoplay=1&loop=1&playlist=${id}&controls=0&showinfo=0&modestbranding=1` },
            
            // Images
            { id: 'thumbnail', name: 'Miniature HD', icon: '🖼️', category: 'media', popular: true,
                build: (id) => `https://img.youtube.com/vi/${id}/maxresdefault.jpg` },
            
            { id: 'thumbnail-hq', name: 'Miniature HQ', icon: '📷', category: 'media',
                build: (id) => `https://img.youtube.com/vi/${id}/hqdefault.jpg` },
            
            { id: 'thumbnail-mq', name: 'Miniature MQ', icon: '🎞️', category: 'media',
                build: (id) => `https://img.youtube.com/vi/${id}/mqdefault.jpg` },
            
            { id: 'thumbnail-sd', name: 'Miniature SD', icon: '📹', category: 'media',
                build: (id) => `https://img.youtube.com/vi/${id}/sddefault.jpg` },
            
            // Partage
            { id: 'short', name: 'Lien court', icon: '🔗', category: 'share',
                build: (id) => `https://youtu.be/${id}` },
            
            { id: 'nocookie', name: 'Sans cookies', icon: '🍪', category: 'share',
                build: (id) => `https://www.youtube-nocookie.com/embed/${id}` }
        ];
    }

    getCalendarTemplates() {
        return [
            { id: 'view', name: 'Voir calendrier', icon: '📅', category: 'view',
                build: (id) => `https://calendar.google.com/calendar/u/0?cid=${id}` },
            
            { id: 'embed', name: 'Intégrer calendrier', icon: '🔲', category: 'embed',
                build: (id) => `https://calendar.google.com/calendar/embed?src=${id}` }
        ];
    }


    /**
     * Transforme une URL Google selon le template demandé
     */
    transform(url, templateId) {
        const extracted = this.extractor.extract(url);
        if (!extracted) return null;

        const serviceTemplates = this.templates[extracted.service];
        if (!serviceTemplates) return null;

        const template = serviceTemplates.find(t => t.id === templateId);
        if (!template) return null;

        return template.build(extracted.id, extracted.isPublishedForm);
    }

    /**
     * Obtient toutes les transformations possibles pour une URL
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
            note: template.note,
            requiresOptions: template.requiresOptions || false,
            optionsType: template.optionsType,
            build: template.build, // Passer la fonction build elle-même
            url: template.build(extracted.id, extracted.isPublishedForm)
        }));
    }

    /**
     * Détecte le type de service Google
     */
    detect(url) {
        const extracted = this.extractor.extract(url);
        if (!extracted) return null;

        const serviceNames = {
            'forms': 'Google Forms',
            'docs': 'Google Docs',
            'sheets': 'Google Sheets',
            'slides': 'Google Slides',
            'drive': 'Google Drive',
            'drive_redirect': 'Google Drive (Type inconnu)',
            'youtube': 'YouTube',
            'calendar': 'Google Calendar'
        };

        return {
            type: `google_${extracted.service}`,
            id: extracted.id,
            service: extracted.service,
            name: serviceNames[extracted.service] || 'Google',
            icon: this.getServiceIcon(extracted.service),
            isPublishedForm: extracted.isPublishedForm,
            originalUrl: url,
            needsTypeSelection: extracted.service === 'drive_redirect'
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
            'drive_redirect': '❓',
            'youtube': '🎬',
            'calendar': '📅'
        };
        return icons[service] || '🔗';
    }
    
    /**
     * Transforme un lien de redirection Drive en lien spécifique
     */
    transformRedirect(id, targetType) {
        switch(targetType) {
            case 'docs':
            case 'document':
                return `https://docs.google.com/document/d/${id}/edit`;
            case 'sheets':
            case 'spreadsheet':
                return `https://docs.google.com/spreadsheets/d/${id}/edit`;
            case 'slides':
            case 'presentation':
                return `https://docs.google.com/presentation/d/${id}/edit`;
            case 'forms':
            case 'form':
                return `https://docs.google.com/forms/d/${id}/edit`;
            case 'drive':
            case 'file':
            case 'general':
            case 'image':
            case 'video':
            case 'pdf':
            default:
                return `https://drive.google.com/file/d/${id}/view`;
        }
    }
}

// Export singleton
const googleTransformerComplete = new GoogleTransformerComplete();

// Export pour les tests
if (typeof module !== 'undefined' && module.exports) {
    module.exports = googleTransformerComplete;
}