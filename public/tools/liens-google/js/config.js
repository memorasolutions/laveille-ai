/**
 * Configuration JavaScript - Transformateur Google
 * Version JavaScript pure pour fonctionnement sans serveur PHP
 */

const AppConfig = {
    app: {
        name: 'Transformateur Google',
        version: '2.0.0',
        description: 'Transformez vos liens Google instantanément',
        mode: 'production',
        usage: 'ponctuel'
    },

    patterns: {
        google_docs: {
            regex: /docs\.google\.com\/document\/d\/([a-zA-Z0-9-_]+)/,
            name: 'Google Docs',
            icon: '📄',
            category: 'document',
            auto_detect: true
        },
        
        google_sheets: {
            regex: /docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/,
            name: 'Google Sheets',
            icon: '📊',
            category: 'spreadsheet',
            auto_detect: true,
            support_gid: true
        },
        
        google_slides: {
            regex: /docs\.google\.com\/presentation\/d\/([a-zA-Z0-9-_]+)/,
            name: 'Google Slides',
            icon: '🎯',
            category: 'presentation',
            auto_detect: true
        },
        
        google_forms: {
            regex: /docs\.google\.com\/forms\/d\/([a-zA-Z0-9-_]+)/,
            name: 'Google Forms',
            icon: '📝',
            category: 'form',
            auto_detect: true
        },
        
        google_drive: {
            regex: /drive\.google\.com\/(?:file\/d\/|open\?id=|drive\/folders\/|folderview\?id=)([a-zA-Z0-9-_]+)/,
            name: 'Google Drive',
            icon: '💾',
            category: 'file',
            auto_detect: true,
            requires_type_selection: true
        },
        
        youtube: {
            regex: /(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9-_]{11})/,
            name: 'YouTube',
            icon: '🎬',
            category: 'video',
            auto_detect: true,
            support_timestamp: true
        },
        
        youtube_shorts: {
            regex: /youtube\.com\/shorts\/([a-zA-Z0-9-_]{11})/,
            name: 'YouTube Shorts',
            icon: '📱',
            category: 'video',
            auto_detect: true
        },
        
        google_photos: {
            regex: /photos\.google\.com\/share\/([a-zA-Z0-9-_]+)/,
            name: 'Google Photos',
            icon: '📷',
            category: 'media',
            auto_detect: true
        },
        
        google_calendar: {
            regex: /calendar\.google\.com\/calendar\/(?:u\/0\/)?(?:embed\?src=|r\?cid=)([a-zA-Z0-9-_@.%]+)/,
            name: 'Google Calendar',
            icon: '📅',
            category: 'calendar',
            auto_detect: true
        },
    },

    transformations: {
        google_docs: [
            { id: 'preview', name: 'Prévisualisation', icon: '👁️', 
              template: 'https://docs.google.com/document/d/{ID}/preview',
              popular: true, category: 'view' },
            { id: 'pdf', name: 'Export PDF', icon: '📥',
              template: 'https://docs.google.com/document/d/{ID}/export?format=pdf',
              popular: true, category: 'export' },
            { id: 'word', name: 'Export Word', icon: '📝',
              template: 'https://docs.google.com/document/d/{ID}/export?format=docx',
              category: 'export' },
            { id: 'copy', name: 'Créer copie', icon: '📋',
              template: 'https://docs.google.com/document/d/{ID}/copy',
              category: 'edit' },
            { id: 'edit', name: 'Mode édition', icon: '✏️',
              template: 'https://docs.google.com/document/d/{ID}/edit',
              category: 'edit' },
            { id: 'txt', name: 'Export texte', icon: '📃',
              template: 'https://docs.google.com/document/d/{ID}/export?format=txt',
              category: 'export' },
            { id: 'html', name: 'Export HTML', icon: '🌐',
              template: 'https://docs.google.com/document/d/{ID}/export?format=html',
              category: 'export' },
            { id: 'epub', name: 'Export EPUB', icon: '📚',
              template: 'https://docs.google.com/document/d/{ID}/export?format=epub',
              category: 'export' }
        ],
        
        google_sheets: [
            { id: 'excel', name: 'Export Excel', icon: '📊',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=xlsx',
              popular: true, category: 'export' },
            { id: 'csv', name: 'Export CSV', icon: '📈',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=csv',
              popular: true, category: 'export' },
            { id: 'pdf', name: 'Export PDF', icon: '📥',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=pdf',
              category: 'export' },
            { id: 'copy', name: 'Créer copie', icon: '📋',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/copy',
              category: 'edit' },
            { id: 'preview', name: 'Prévisualisation', icon: '👁️',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/preview',
              category: 'view' },
            { id: 'gid', name: 'Feuille spécifique', icon: '📑',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/edit#gid={GID}',
              category: 'view', requires_input: ['gid', 'Numéro GID'] }
        ],
        
        google_slides: [
            { id: 'present', name: 'Mode présentation', icon: '🎯',
              template: 'https://docs.google.com/presentation/d/{ID}/present',
              popular: true, category: 'view' },
            { id: 'pdf', name: 'Export PDF', icon: '📥',
              template: 'https://docs.google.com/presentation/d/{ID}/export/pdf',
              popular: true, category: 'export' },
            { id: 'pptx', name: 'Export PowerPoint', icon: '🎞️',
              template: 'https://docs.google.com/presentation/d/{ID}/export/pptx',
              category: 'export' },
            { id: 'copy', name: 'Créer copie', icon: '📋',
              template: 'https://docs.google.com/presentation/d/{ID}/copy',
              category: 'edit' },
            { id: 'preview', name: 'Prévisualisation', icon: '👁️',
              template: 'https://docs.google.com/presentation/d/{ID}/preview',
              category: 'view' }
        ],
        
        youtube: [
            { id: 'embed', name: 'Code embed', icon: '🎬',
              template: 'https://www.youtube.com/embed/{ID}',
              popular: true, category: 'embed' },
            { id: 'thumb_hq', name: 'Miniature HD', icon: '🖼️',
              template: 'https://img.youtube.com/vi/{ID}/maxresdefault.jpg',
              popular: true, category: 'media' },
            { id: 'thumb_mq', name: 'Miniature moyenne', icon: '🖼️',
              template: 'https://img.youtube.com/vi/{ID}/mqdefault.jpg',
              category: 'media' },
            { id: 'short', name: 'Lien court', icon: '🔗',
              template: 'https://youtu.be/{ID}',
              category: 'share' },
            { id: 'time', name: 'Avec timestamp', icon: '⏱️',
              template: 'https://www.youtube.com/watch?v={ID}&t={TIME}',
              category: 'share', requires_input: ['time', 'Temps en secondes'] }
        ],
        
        youtube_shorts: [
            { id: 'watch', name: 'Mode normal', icon: '📺',
              template: 'https://www.youtube.com/watch?v={ID}',
              popular: true, category: 'view' },
            { id: 'embed', name: 'Code embed', icon: '🎬',
              template: 'https://www.youtube.com/embed/{ID}',
              category: 'embed' },
            { id: 'thumb', name: 'Miniature', icon: '🖼️',
              template: 'https://img.youtube.com/vi/{ID}/maxresdefault.jpg',
              category: 'media' }
        ],
        
        google_drive: [
            { id: 'preview', name: 'Prévisualisation', icon: '👁️',
              template: 'https://drive.google.com/file/d/{ID}/preview',
              popular: true, category: 'view' },
            { id: 'download', name: 'Téléchargement', icon: '⬇️',
              template: 'https://drive.google.com/uc?export=download&id={ID}',
              popular: true, category: 'download' },
            { id: 'view', name: 'Vue web', icon: '🌐',
              template: 'https://drive.google.com/file/d/{ID}/view',
              category: 'view' }
        ],
        
        // Transformations spécifiques pour Drive selon le type de fichier
        google_drive_document: [
            { id: 'open_docs', name: 'Ouvrir dans Docs', icon: '🔗',
              template: 'https://docs.google.com/document/d/{ID}/edit',
              popular: true, category: 'edit' },
            { id: 'preview', name: 'Prévisualisation', icon: '👁️',
              template: 'https://docs.google.com/document/d/{ID}/preview',
              popular: true, category: 'view' },
            { id: 'pdf', name: 'Export PDF', icon: '📥',
              template: 'https://docs.google.com/document/d/{ID}/export?format=pdf',
              popular: true, category: 'export' },
            { id: 'word', name: 'Export Word', icon: '📝',
              template: 'https://docs.google.com/document/d/{ID}/export?format=docx',
              category: 'export' },
            { id: 'copy', name: 'Créer copie', icon: '📋',
              template: 'https://docs.google.com/document/d/{ID}/copy',
              category: 'edit' }
        ],
        
        google_drive_spreadsheet: [
            { id: 'open_sheets', name: 'Ouvrir dans Sheets', icon: '🔗',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/edit',
              popular: true, category: 'edit' },
            { id: 'excel', name: 'Export Excel', icon: '📊',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=xlsx',
              popular: true, category: 'export' },
            { id: 'csv', name: 'Export CSV', icon: '📈',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=csv',
              popular: true, category: 'export' },
            { id: 'pdf', name: 'Export PDF', icon: '📥',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/export?format=pdf',
              category: 'export' },
            { id: 'copy', name: 'Créer copie', icon: '📋',
              template: 'https://docs.google.com/spreadsheets/d/{ID}/copy',
              category: 'edit' }
        ],
        
        google_drive_presentation: [
            { id: 'open_slides', name: 'Ouvrir dans Slides', icon: '🔗',
              template: 'https://docs.google.com/presentation/d/{ID}/edit',
              popular: true, category: 'edit' },
            { id: 'present', name: 'Mode présentation', icon: '🎯',
              template: 'https://docs.google.com/presentation/d/{ID}/present',
              popular: true, category: 'view' },
            { id: 'pdf', name: 'Export PDF', icon: '📥',
              template: 'https://docs.google.com/presentation/d/{ID}/export/pdf',
              popular: true, category: 'export' },
            { id: 'pptx', name: 'Export PowerPoint', icon: '🎞️',
              template: 'https://docs.google.com/presentation/d/{ID}/export/pptx',
              category: 'export' },
            { id: 'copy', name: 'Créer copie', icon: '📋',
              template: 'https://docs.google.com/presentation/d/{ID}/copy',
              category: 'edit' }
        ],
        
        google_drive_form: [
            { id: 'open_form', name: 'Ouvrir le formulaire', icon: '🔗',
              template: 'https://docs.google.com/forms/d/e/{ID}/viewform',
              popular: true, category: 'view' },
            { id: 'edit', name: 'Mode édition', icon: '✏️',
              template: 'https://docs.google.com/forms/d/{ID}/edit',
              category: 'edit' },
            { id: 'responses', name: 'Voir réponses', icon: '📊',
              template: 'https://docs.google.com/forms/d/{ID}/viewanalytics',
              category: 'view' }
        ],
        
        google_drive_image: [
            { id: 'view', name: 'Voir l\'image', icon: '🖼️',
              template: 'https://drive.google.com/file/d/{ID}/view',
              popular: true, category: 'view' },
            { id: 'download', name: 'Télécharger', icon: '⬇️',
              template: 'https://drive.google.com/uc?export=download&id={ID}',
              popular: true, category: 'download' },
            { id: 'preview', name: 'Prévisualisation', icon: '👁️',
              template: 'https://drive.google.com/file/d/{ID}/preview',
              category: 'view' },
            { id: 'embed', name: 'Code embed', icon: '📎',
              template: '<img src="https://drive.google.com/uc?export=view&id={ID}" alt="Image">',
              category: 'embed' }
        ],
        
        google_drive_video: [
            { id: 'stream', name: 'Regarder', icon: '🎬',
              template: 'https://drive.google.com/file/d/{ID}/view',
              popular: true, category: 'view' },
            { id: 'download', name: 'Télécharger', icon: '⬇️',
              template: 'https://drive.google.com/uc?export=download&id={ID}',
              popular: true, category: 'download' },
            { id: 'preview', name: 'Prévisualisation', icon: '👁️',
              template: 'https://drive.google.com/file/d/{ID}/preview',
              category: 'view' },
            { id: 'embed', name: 'Code embed', icon: '📹',
              template: '<iframe src="https://drive.google.com/file/d/{ID}/preview" width="640" height="480"></iframe>',
              category: 'embed' }
        ],
        
        google_drive_pdf: [
            { id: 'view', name: 'Voir le PDF', icon: '👁️',
              template: 'https://drive.google.com/file/d/{ID}/view',
              popular: true, category: 'view' },
            { id: 'download', name: 'Télécharger', icon: '⬇️',
              template: 'https://drive.google.com/uc?export=download&id={ID}',
              popular: true, category: 'download' },
            { id: 'preview', name: 'Prévisualisation', icon: '👁️',
              template: 'https://drive.google.com/file/d/{ID}/preview',
              category: 'view' },
            { id: 'embed', name: 'Code embed', icon: '📎',
              template: '<iframe src="https://drive.google.com/file/d/{ID}/preview" width="640" height="480"></iframe>',
              category: 'embed' }
        ],
        
        google_forms: [
            { id: 'view', name: 'Formulaire', icon: '📝',
              template: 'https://docs.google.com/forms/d/e/{ID}/viewform',
              popular: true, category: 'view' },
            { id: 'edit', name: 'Mode édition', icon: '✏️',
              template: 'https://docs.google.com/forms/d/{ID}/edit',
              category: 'edit' },
            { id: 'responses', name: 'Voir réponses', icon: '📊',
              template: 'https://docs.google.com/forms/d/{ID}/viewanalytics',
              category: 'view' }
        ],
        
        google_photos: [
            { id: 'view', name: 'Voir album', icon: '📷',
              template: 'https://photos.google.com/share/{ID}',
              popular: true, category: 'view' }
        ],
        
        google_calendar: [
            { id: 'view', name: 'Voir calendrier', icon: '📅',
              template: 'https://calendar.google.com/calendar/embed?src={ID}',
              popular: true, category: 'view' }
        ],
    },

    messages: {
        welcome: 'Transformez vos liens Google instantanément',
        placeholder: 'Collez votre lien Google ici (Docs, Sheets, YouTube, Drive...)',
        detecting: 'Détection en cours...',
        success: '{type} détecté avec succès',
        error: 'Lien Google non reconnu',
        copied: 'Lien copié dans le presse-papier !',
        copy_all: 'Tous les liens copiés !',
        no_transformations: 'Aucune transformation disponible'
    },

    ui: {
        theme: 'light',
        compact_mode: true,
        show_descriptions: true,
        animations: true,
        copy_feedback: true,
        group_by_category: true,
        show_popular_first: true
    },

    features: {
        history: false,
        persistence: false,
        analytics: false,
        user_accounts: false,
        custom_transformations: false,
        api: false,
        export_import: true,
        copy_all: true,
        suggestions: true
    }
};

// Export pour utilisation
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AppConfig;
}