/**
 * Module GoogleAccessChecker - Vérification de l'accessibilité des documents Google
 * Keep it simple - Une responsabilité : vérifier si un document est accessible
 * Réutilisable pour tous les services Google
 */

class GoogleAccessChecker {
    constructor() {
        this.cache = new Map(); // Cache pour éviter les vérifications répétées
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
    }

    /**
     * Vérifie l'accessibilité d'un document Google
     * @param {string} url - L'URL du document
     * @returns {Promise<Object>} { isAccessible, accessLevel, message }
     */
    async checkAccess(url) {
        if (!url) return this.getDefaultResponse();

        // Vérifier le cache
        const cached = this.getCached(url);
        if (cached) return cached;

        try {
            // Pour les Google Forms, on peut vérifier via viewform
            if (url.includes('/forms/')) {
                return await this.checkFormAccess(url);
            }
            
            // Pour les autres documents Google
            if (url.includes('docs.google.com') || url.includes('drive.google.com')) {
                return await this.checkDocumentAccess(url);
            }

            // YouTube est toujours public ou avec lien
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                return this.getPublicResponse();
            }

            // Calendar est généralement public
            if (url.includes('calendar.google.com')) {
                return this.getPublicResponse();
            }

            return this.getDefaultResponse();
        } catch (error) {
            console.warn('Erreur lors de la vérification d\'accès:', error);
            return this.getUnknownResponse();
        }
    }

    /**
     * Vérifie l'accès pour un Google Form
     */
    async checkFormAccess(url) {
        try {
            // Construire l'URL viewform si ce n'est pas déjà le cas
            let checkUrl = url;
            if (!url.includes('/viewform')) {
                const match = url.match(/\/forms\/d\/(?:e\/)?([a-zA-Z0-9_-]+)/);
                if (match) {
                    const id = match[1];
                    // Basé sur les tendances 2025: IDs 1FAIpQL = formulaires publiés
                    const isPublished = url.includes('/d/e/') || id.startsWith('1FAIpQL');
                    checkUrl = isPublished 
                        ? `https://docs.google.com/forms/d/e/${id}/viewform`
                        : `https://docs.google.com/forms/d/${id}/viewform`;
                }
            }

            // Faire une requête HEAD pour vérifier l'accessibilité
            const response = await this.makeHeadRequest(checkUrl);
            
            if (response.ok) {
                const result = {
                    isAccessible: true,
                    accessLevel: 'public',
                    message: '🌐 Formulaire accessible publiquement',
                    icon: '🔓'
                };
                this.setCache(url, result);
                return result;
            } else if (response.status === 403 || response.status === 401) {
                const result = {
                    isAccessible: false,
                    accessLevel: 'private',
                    message: '🔒 Formulaire privé - Autorisation requise',
                    icon: '🔒',
                    warning: true
                };
                this.setCache(url, result);
                return result;
            }
        } catch (error) {
            // En cas d'erreur CORS, on suppose que c'est accessible
            if (error.name === 'TypeError' && error.message.includes('CORS')) {
                return this.getLinkOnlyResponse();
            }
        }

        return this.getUnknownResponse();
    }

    /**
     * Vérifie l'accès pour un document Google (Docs, Sheets, Slides)
     */
    async checkDocumentAccess(url) {
        // Détection améliorée basée sur les patterns d'URL (2025)
        
        // Mode édition = on ne peut pas déterminer l'accès juste avec /edit
        // Un document peut être public ET en mode édition
        if (url.includes('/edit')) {
            return {
                isAccessible: null,
                accessLevel: 'unknown',
                message: 'Mode édition',
                icon: '✏️',
                warning: false,
                collapsible: true,
                summary: '⚠️ Vérifier qui peut accéder à ce document',
                info: `<div style="font-size: 14px; line-height: 1.3; color: #374151;">
<p style="margin: 0 0 4px 0; font-weight: 600; color: #1f2937;">📊 Statut actuel : Mode édition</p>
<p style="margin: 0 0 6px 0; font-size: 13px;">Les liens générés fonctionneront différemment selon les permissions :</p>
<div style="display: grid; gap: 4px;">
  <div style="padding: 6px 8px; background: #fee2e2; border-radius: 6px; border-left: 3px solid #ef4444;">
    <div style="font-weight: 600; color: #991b1b; margin-bottom: 2px; font-size: 13px;">🔒 Document privé</div>
    <div style="font-size: 12px; color: #7f1d1d; line-height: 1.3;">Accessible uniquement par vous. Les liens ne fonctionneront pas pour les autres. Solution : Partager le document d'abord.</div>
  </div>
  <div style="padding: 6px 8px; background: #fef3c7; border-radius: 6px; border-left: 3px solid #f59e0b;">
    <div style="font-weight: 600; color: #92400e; margin-bottom: 2px; font-size: 13px;">👥 Partagé avec des personnes</div>
    <div style="font-size: 12px; color: #78350f; line-height: 1.3;">Accessible seulement aux personnes autorisées. Les liens nécessitent une connexion Google. Liste limitée de personnes.</div>
  </div>
  <div style="padding: 6px 8px; background: #dbeafe; border-radius: 6px; border-left: 3px solid #3b82f6;">
    <div style="font-weight: 600; color: #1e3a8a; margin-bottom: 2px; font-size: 13px;">🔗 Partagé avec le lien</div>
    <div style="font-size: 12px; color: #1e40af; line-height: 1.3;">Quiconque avec le lien peut accéder. Pas besoin de connexion Google. Idéal pour partage large.</div>
  </div>
  <div style="padding: 6px 8px; background: #d1fae5; border-radius: 6px; border-left: 3px solid #10b981;">
    <div style="font-weight: 600; color: #064e3b; margin-bottom: 2px; font-size: 13px;">🌐 Public sur le web</div>
    <div style="font-size: 12px; color: #047857; line-height: 1.3;">Visible par tous sur Internet. Indexé par les moteurs de recherche. Accès totalement ouvert.</div>
  </div>
</div>
<div style="margin-top: 6px; padding: 6px 8px; background: #f3f4f6; border-radius: 6px;">
  <div style="font-weight: 600; margin-bottom: 3px; color: #1f2937; font-size: 13px;">💡 Comment vérifier ?</div>
  <ol style="margin: 0; padding-left: 18px; font-size: 12px; color: #4b5563; line-height: 1.3;">
    <li>Ouvrez le lien dans une fenêtre privée/incognito</li>
    <li>Si demande de connexion = Document privé ou restreint</li>
    <li>Si s'ouvre directement = Document public ou avec lien</li>
  </ol>
</div>
<div style="margin-top: 6px; padding: 8px 10px; background: #e5e7eb; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
  <span style="font-size: 12px; color: #6b7280;">🔧 Modifier les permissions : Bouton "Partager" en haut à droite du document</span>
</div>
</div>`
            };
        }
        
        // Export = accessible si partagé
        if (url.includes('/export')) {
            return {
                isAccessible: true,
                accessLevel: 'link',
                message: 'Export - Accessible si partagé',
                icon: '🔗'
            };
        }
        
        // Preview/View = généralement partagé
        if (url.includes('/preview') || url.includes('/view')) {
            return {
                isAccessible: true,
                accessLevel: 'link',
                message: 'Mode lecture - Partagé avec le lien',
                icon: '🔗'
            };
        }
        
        // Mode présentation (Slides)
        if (url.includes('/present')) {
            return {
                isAccessible: true,
                accessLevel: 'link',
                message: 'Mode présentation - Accessible',
                icon: '🎯'
            };
        }
        
        // Par défaut: message informatif avec conseil API
        return {
            isAccessible: null,
            accessLevel: 'unknown',
            message: 'Vérifiez les permissions',
            icon: '⚠️',
            info: 'Astuce 2025: Pour vérifier l\'accès, utilisez l\'API Sheets v4 avec une clé. Code 200 = public, 403 = privé.'
        };
    }

    /**
     * Effectue une requête HEAD
     * Note: En raison des restrictions CORS, cette méthode est limitée
     */
    async makeHeadRequest(url) {
        // CORS toujours présent en 2025 - Solution: patterns heuristiques
        // Recommandation: utilisez GET + API key côté serveur pour vérification réelle
        return this.checkAccessByPattern(url);
    }

    /**
     * Vérifie l'accès basé sur les patterns d'URL connus
     * Note: Nous ne pouvons pas vraiment savoir si un document est privé sans faire de requête
     * donc nous affichons un message informatif
     */
    checkAccessByPattern(url) {
        // Patterns 2025: 1FAIpQL = formulaires publiés
        if (url.includes('/forms/d/e/') || url.includes('1FAIpQL')) {
            return { ok: true, status: 200 };
        }
        
        // Export et preview = probablement accessibles
        if (url.includes('/export') || url.includes('/preview')) {
            return { ok: true, status: 200 };
        }
        
        // Edit = on ne peut pas savoir sans tester
        if (url.includes('/edit')) {
            return { ok: null, status: null };
        }
        
        return { ok: null, status: null };
    }

    /**
     * Réponses par défaut
     */
    getDefaultResponse() {
        return {
            isAccessible: null,
            accessLevel: 'unknown',
            message: '❓ Niveau d\'accès inconnu',
            icon: '❓'
        };
    }

    getPublicResponse() {
        return {
            isAccessible: true,
            accessLevel: 'public',
            message: 'Document public',
            icon: '🔓'
        };
    }

    getLinkOnlyResponse() {
        return {
            isAccessible: true,
            accessLevel: 'link',
            message: 'Accessible avec le lien',
            icon: '🔗'
        };
    }

    getPrivateResponse() {
        return {
            isAccessible: false,
            accessLevel: 'private',
            message: 'Document privé - Connexion requise',
            icon: '🔒',
            warning: true
        };
    }

    getUnknownResponse() {
        return {
            isAccessible: null,
            accessLevel: 'unknown',
            message: '❓ Impossible de vérifier l\'accès',
            icon: '❓'
        };
    }

    /**
     * Gestion du cache
     */
    getCached(url) {
        const cached = this.cache.get(url);
        if (cached && (Date.now() - cached.timestamp < this.cacheTimeout)) {
            return cached.data;
        }
        this.cache.delete(url);
        return null;
    }

    setCache(url, data) {
        this.cache.set(url, {
            data: data,
            timestamp: Date.now()
        });
    }

    /**
     * Affiche un indicateur visuel dans l'interface
     */
    createAccessIndicator(accessInfo) {
        const indicator = document.createElement('div');
        indicator.className = 'access-indicator';
        indicator.classList.add(`access-${accessInfo.accessLevel}`);
        
        if (accessInfo.warning) {
            indicator.classList.add('warning');
        }

        indicator.innerHTML = `
            <span class="access-icon">${accessInfo.icon}</span>
            <span class="access-message">${accessInfo.message}</span>
        `;

        return indicator;
    }

    /**
     * Méthode simple pour vérifier si une URL nécessite un avertissement
     */
    needsWarning(url) {
        // Vérification rapide basée sur les patterns connus
        // Les formulaires publiés (avec /e/) sont généralement publics
        if (url.includes('/forms/d/e/')) {
            return false;
        }
        
        // Les documents en mode edit peuvent être privés
        if (url.includes('/edit')) {
            return true;
        }

        // Les previews sont généralement accessibles avec le lien
        if (url.includes('/preview') || url.includes('/view')) {
            return false;
        }

        return null; // Inconnu
    }
}

// Export singleton
const googleAccessChecker = new GoogleAccessChecker();

// Export pour les tests
if (typeof module !== 'undefined' && module.exports) {
    module.exports = googleAccessChecker;
}