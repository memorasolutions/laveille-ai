/**
 * Module de transformation des liens Google
 * Génère toutes les variantes possibles d'un lien
 */

class LinkTransformer {
    constructor(transformations = null) {
        // Utiliser AppConfig si disponible
        if (!transformations && typeof AppConfig !== 'undefined' && AppConfig.transformations) {
            this.transformations = AppConfig.transformations;
        } else {
            this.transformations = transformations || this.getDefaultTransformations();
        }
    }

    getDefaultTransformations() {
        return {
            google_docs: [
                { id: 'preview', name: 'Prévisualisation', icon: '👁️', template: 'https://docs.google.com/document/d/{ID}/preview', popular: true, category: 'view' },
                { id: 'pdf', name: 'Export PDF', icon: '📄', template: 'https://docs.google.com/document/d/{ID}/export?format=pdf', popular: true, category: 'export' },
                { id: 'word', name: 'Export Word', icon: '📝', template: 'https://docs.google.com/document/d/{ID}/export?format=docx', category: 'export' },
                { id: 'copy', name: 'Créer copie', icon: '📋', template: 'https://docs.google.com/document/d/{ID}/copy', category: 'edit' },
                { id: 'edit', name: 'Mode édition', icon: '✏️', template: 'https://docs.google.com/document/d/{ID}/edit', category: 'edit' },
                { id: 'txt', name: 'Export texte', icon: '📃', template: 'https://docs.google.com/document/d/{ID}/export?format=txt', category: 'export' },
                { id: 'html', name: 'Export HTML', icon: '🌐', template: 'https://docs.google.com/document/d/{ID}/export?format=html', category: 'export' },
                { id: 'epub', name: 'Export EPUB', icon: '📚', template: 'https://docs.google.com/document/d/{ID}/export?format=epub', category: 'export' }
            ],
            
            google_sheets: [
                { id: 'excel', name: 'Export Excel', icon: '📊', template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=xlsx', popular: true, category: 'export' },
                { id: 'csv', name: 'Export CSV', icon: '📈', template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=csv', popular: true, category: 'export' },
                { id: 'pdf', name: 'Export PDF', icon: '📄', template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=pdf', category: 'export' },
                { id: 'copy', name: 'Créer copie', icon: '📋', template: 'https://docs.google.com/spreadsheets/d/{ID}/copy', category: 'edit' },
                { id: 'preview', name: 'Prévisualisation', icon: '👁️', template: 'https://docs.google.com/spreadsheets/d/{ID}/preview', category: 'view' },
                { id: 'gid', name: 'Feuille spécifique', icon: '📑', template: 'https://docs.google.com/spreadsheets/d/{ID}/edit#gid={GID}', category: 'view', requires_input: true }
            ],
            
            google_slides: [
                { id: 'present', name: 'Mode présentation', icon: '🎯', template: 'https://docs.google.com/presentation/d/{ID}/present', popular: true, category: 'view' },
                { id: 'pdf', name: 'Export PDF', icon: '📄', template: 'https://docs.google.com/presentation/d/{ID}/export/pdf', popular: true, category: 'export' },
                { id: 'pptx', name: 'Export PowerPoint', icon: '🎞️', template: 'https://docs.google.com/presentation/d/{ID}/export/pptx', category: 'export' },
                { id: 'copy', name: 'Créer copie', icon: '📋', template: 'https://docs.google.com/presentation/d/{ID}/copy', category: 'edit' },
                { id: 'preview', name: 'Prévisualisation', icon: '👁️', template: 'https://docs.google.com/presentation/d/{ID}/preview', category: 'view' }
            ],
            
            youtube: [
                { id: 'embed', name: 'Code embed', icon: '🎬', template: 'https://www.youtube.com/embed/{ID}', popular: true, category: 'embed' },
                { id: 'thumb_hq', name: 'Miniature HD', icon: '🖼️', template: 'https://img.youtube.com/vi/{ID}/maxresdefault.jpg', popular: true, category: 'media' },
                { id: 'thumb_mq', name: 'Miniature moyenne', icon: '🖼️', template: 'https://img.youtube.com/vi/{ID}/mqdefault.jpg', category: 'media' },
                { id: 'short', name: 'Lien court', icon: '🔗', template: 'https://youtu.be/{ID}', category: 'share' },
                { id: 'time', name: 'Avec timestamp', icon: '⏱️', template: 'https://www.youtube.com/watch?v={ID}&t={TIME}', category: 'share', requires_input: true }
            ],
            
            youtube_shorts: [
                { id: 'watch', name: 'Mode normal', icon: '📺', template: 'https://www.youtube.com/watch?v={ID}', popular: true, category: 'view' },
                { id: 'embed', name: 'Code embed', icon: '🎬', template: 'https://www.youtube.com/embed/{ID}', category: 'embed' },
                { id: 'thumb', name: 'Miniature', icon: '🖼️', template: 'https://img.youtube.com/vi/{ID}/maxresdefault.jpg', category: 'media' }
            ],
            
            google_drive: [
                { id: 'preview', name: 'Prévisualisation', icon: '👁️', template: 'https://drive.google.com/file/d/{ID}/preview', popular: true, category: 'view' },
                { id: 'download', name: 'Téléchargement', icon: '⬇️', template: 'https://drive.google.com/uc?export=download&id={ID}', popular: true, category: 'download' },
                { id: 'view', name: 'Vue web', icon: '🌐', template: 'https://drive.google.com/file/d/{ID}/view', category: 'view' },
                { id: 'embed', name: 'Intégrer', icon: '📎', template: '<iframe src="https://drive.google.com/file/d/{ID}/preview" width="640" height="480"></iframe>', category: 'embed' }
            ],
            
            google_forms: [
                { id: 'view', name: 'Formulaire', icon: '📝', template: 'https://docs.google.com/forms/d/e/{ID}/viewform', popular: true, category: 'view' },
                { id: 'edit', name: 'Mode édition', icon: '✏️', template: 'https://docs.google.com/forms/d/{ID}/edit', category: 'edit' },
                { id: 'responses', name: 'Voir réponses', icon: '📊', template: 'https://docs.google.com/forms/d/{ID}/viewanalytics', category: 'view' },
                { id: 'prefill', name: 'Pré-remplir', icon: '✍️', template: 'https://docs.google.com/forms/d/e/{ID}/viewform?entry.field={VALUE}', category: 'share', requires_input: true }
            ],
            
            google_maps: [
                { id: 'view', name: 'Voir carte', icon: '🗺️', template: 'https://maps.google.com/maps?q={ID}', popular: true, category: 'view' },
                { id: 'embed', name: 'Intégrer', icon: '📍', template: '<iframe src="https://maps.google.com/maps?q={ID}&output=embed" width="600" height="450"></iframe>', category: 'embed' },
                { id: 'directions', name: 'Itinéraire', icon: '🧭', template: 'https://maps.google.com/maps?daddr={ID}', category: 'view' }
            ],
            
            google_photos: [
                { id: 'view', name: 'Voir album', icon: '📷', template: 'https://photos.google.com/share/{ID}', popular: true, category: 'view' },
                { id: 'download', name: 'Télécharger', icon: '⬇️', template: 'https://photos.google.com/share/{ID}?download', category: 'download' }
            ],
            
            google_calendar: [
                { id: 'view', name: 'Voir calendrier', icon: '📅', template: 'https://calendar.google.com/calendar/embed?src={ID}', popular: true, category: 'view' },
                { id: 'add', name: 'Ajouter événement', icon: '➕', template: 'https://calendar.google.com/calendar/render?action=TEMPLATE&src={ID}', category: 'edit' }
            ],
            
            google_meet: [
                { id: 'join', name: 'Rejoindre', icon: '📹', template: 'https://meet.google.com/{ID}', popular: true, category: 'view' },
                { id: 'dial', name: 'Appeler', icon: '📞', template: 'https://meet.google.com/{ID}?pli=1', category: 'view' }
            ]
        };
    }

    getTransformations(type) {
        return this.transformations[type] || [];
    }

    getPopularTransformations(type) {
        const transforms = this.getTransformations(type);
        return transforms.filter(t => t.popular === true);
    }

    getTransformationsByCategory(type) {
        const transforms = this.getTransformations(type);
        const byCategory = {};
        
        transforms.forEach(t => {
            const category = t.category || 'other';
            if (!byCategory[category]) {
                byCategory[category] = [];
            }
            byCategory[category].push(t);
        });
        
        return byCategory;
    }

    mapTransformIdToAction(transformId) {
        // Mapper les IDs de transformation vers les actions Google Forms
        const mapping = {
            'view': 'viewform',
            'open_form': 'viewform',
            'edit': 'edit',
            'responses': 'viewanalytics',
            'prefill': 'viewform',
            'submit': 'formResponse'
        };
        
        return mapping[transformId] || 'viewform';
    }

    transform(detection, transformId, inputs = {}) {
        if (!detection || !transformId) {
            throw new Error('Detection et transformId requis');
        }

        // APPROCHE SIMPLE ET DIRECTE POUR GOOGLE FORMS
        if (detection.type === 'google_forms' || detection.type === 'google_drive_form') {
            const formId = detection.id;
            const baseUrl = 'https://docs.google.com/forms/d/';
            
            // Déterminer si c'est un formulaire publié (ID long commençant par 1FAIpQLSf ou 1FAIpQLSc)
            const isPublishedForm = formId.startsWith('1FAIpQLSf') || formId.startsWith('1FAIpQLSc') || formId.startsWith('1FAIpQLSd');
            
            // Construire l'URL selon l'action demandée
            switch(transformId) {
                case 'view':
                case 'open_form':
                case 'prefill':
                    // Les formulaires publiés utilisent /e/ pour viewform
                    return isPublishedForm 
                        ? `${baseUrl}e/${formId}/viewform`
                        : `${baseUrl}${formId}/viewform`;
                
                case 'edit':
                    // Edit n'utilise JAMAIS /e/, même pour les formulaires publiés
                    return `${baseUrl}${formId}/edit`;
                
                case 'responses':
                    // Responses n'utilise JAMAIS /e/
                    return `${baseUrl}${formId}/viewanalytics`;
                
                case 'submit':
                    // Form response utilise /e/ pour les formulaires publiés
                    return isPublishedForm
                        ? `${baseUrl}e/${formId}/formResponse`
                        : `${baseUrl}${formId}/formResponse`;
                
                default:
                    // Par défaut, viewform
                    return isPublishedForm 
                        ? `${baseUrl}e/${formId}/viewform`
                        : `${baseUrl}${formId}/viewform`;
            }
        }

        // Logique normale pour les autres types
        const transforms = this.getTransformations(detection.type);
        const transform = transforms.find(t => t.id === transformId);
        
        if (!transform) {
            throw new Error(`Transformation ${transformId} non trouvée pour ${detection.type}`);
        }

        let result = transform.template;
        
        // Remplacer l'ID principal
        result = result.replace(/{ID}/g, detection.id);
        
        // Remplacer les paramètres additionnels
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
        
        return result;
    }

    getAllTransformations(detection) {
        if (!detection) return [];
        
        const transforms = this.getTransformations(detection.type);
        const results = [];
        
        transforms.forEach(transform => {
            // Ignorer les transformations qui nécessitent des inputs
            if (!transform.requires_input) {
                try {
                    const url = this.transform(detection, transform.id);
                    results.push({
                        ...transform,
                        url: url
                    });
                } catch (error) {
                    console.warn(`Erreur transformation ${transform.id}:`, error);
                }
            }
        });
        
        return results;
    }

    generateEmbedCode(detection, options = {}) {
        const { width = 640, height = 480, responsive = false } = options;
        
        const embedTemplates = {
            youtube: `<iframe width="${width}" height="${height}" src="https://www.youtube.com/embed/{ID}" frameborder="0" allowfullscreen></iframe>`,
            google_drive: `<iframe src="https://drive.google.com/file/d/{ID}/preview" width="${width}" height="${height}"></iframe>`,
            google_maps: `<iframe src="https://maps.google.com/maps?q={ID}&output=embed" width="${width}" height="${height}"></iframe>`,
            google_docs: `<iframe src="https://docs.google.com/document/d/{ID}/preview" width="${width}" height="${height}"></iframe>`,
            google_sheets: `<iframe src="https://docs.google.com/spreadsheets/d/{ID}/preview" width="${width}" height="${height}"></iframe>`,
            google_slides: `<iframe src="https://docs.google.com/presentation/d/{ID}/embed" width="${width}" height="${height}"></iframe>`
        };
        
        let template = embedTemplates[detection.type];
        if (!template) return null;
        
        template = template.replace(/{ID}/g, detection.id);
        
        if (responsive) {
            template = `<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;">
                ${template.replace(/width="\d+" height="\d+"/, 'style="position:absolute;top:0;left:0;width:100%;height:100%;"')}
            </div>`;
        }
        
        return template;
    }

    getSupportedFormats(type) {
        const formats = {
            google_docs: ['pdf', 'docx', 'txt', 'html', 'epub', 'odt', 'rtf'],
            google_sheets: ['xlsx', 'csv', 'pdf', 'ods', 'tsv', 'html'],
            google_slides: ['pdf', 'pptx', 'odp', 'txt', 'png', 'jpg', 'svg'],
            google_forms: ['csv', 'zip']
        };
        
        return formats[type] || [];
    }

    getDirectDownloadUrl(detection, format = null) {
        const templates = {
            google_docs: 'https://docs.google.com/document/d/{ID}/export?format={FORMAT}',
            google_sheets: 'https://docs.google.com/spreadsheets/d/{ID}/export?format={FORMAT}',
            google_slides: 'https://docs.google.com/presentation/d/{ID}/export?format={FORMAT}',
            google_drive: 'https://drive.google.com/uc?export=download&id={ID}',
            youtube: 'https://img.youtube.com/vi/{ID}/maxresdefault.jpg'
        };
        
        let template = templates[detection.type];
        if (!template) return null;
        
        template = template.replace(/{ID}/g, detection.id);
        
        if (format) {
            template = template.replace(/{FORMAT}/g, format);
        }
        
        return template;
    }
}

// Export pour utilisation
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LinkTransformer;
}