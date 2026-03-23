/**
 * BACKUP des endpoints - Créé le 2025-09-05
 * Conservé au cas où certains endpoints deviendraient disponibles
 * ou si des problèmes surviennent après suppression
 */

const BACKUP_ENDPOINTS = {
    sheets: {
        // Endpoints fonctionnels
        htmlview: { 
            status: 'FUNCTIONAL',
            build: (id) => `https://docs.google.com/spreadsheets/d/${id}/htmlview` 
        },
        
        // Endpoints nécessitant publication
        pubhtml: { 
            status: 'REQUIRES_PUBLISH',
            note: 'Nécessite que le document soit publié sur le web',
            build: (id) => `https://docs.google.com/spreadsheets/d/${id}/pubhtml` 
        }
    },
    
    docs: {
        // Endpoints non existants pour Docs
        htmlview: { 
            status: 'NOT_EXISTS',
            reason: 'Google Docs ne supporte pas /htmlview',
            original: (id) => `https://docs.google.com/document/d/${id}/htmlview`
        },
        pubhtml: { 
            status: 'NOT_EXISTS',
            reason: 'Google Docs ne supporte pas /pubhtml',
            original: (id) => `https://docs.google.com/document/d/${id}/pubhtml`
        }
    },
    
    slides: {
        // Endpoints non existants pour Slides
        htmlview: { 
            status: 'NOT_EXISTS',
            reason: 'Google Slides ne supporte pas /htmlview',
            original: (id) => `https://docs.google.com/presentation/d/${id}/htmlview`
        },
        pubhtml: { 
            status: 'NOT_EXISTS',
            reason: 'Google Slides ne supporte pas /pubhtml',
            original: (id) => `https://docs.google.com/presentation/d/${id}/pubhtml`
        },
        
        // Alternative pour Slides
        pub: {
            status: 'REQUIRES_PUBLISH',
            note: 'Version publiée - nécessite publication',
            build: (id) => `https://docs.google.com/presentation/d/${id}/pub`
        }
    },
    
    forms: {
        // Forms n'a ni htmlview ni pubhtml
        htmlview: { 
            status: 'NOT_EXISTS',
            reason: 'Google Forms ne supporte pas /htmlview'
        },
        pubhtml: { 
            status: 'NOT_EXISTS',
            reason: 'Google Forms ne supporte pas /pubhtml'
        }
    }
};

// Date de backup
const BACKUP_DATE = '2025-09-05';
const BACKUP_REASON = 'Nettoyage des endpoints non fonctionnels suite à vérification';

// Export pour référence future
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BACKUP_ENDPOINTS, BACKUP_DATE, BACKUP_REASON };
}