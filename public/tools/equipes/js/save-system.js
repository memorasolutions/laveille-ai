/**
 * JavaScript - Système de sauvegarde
 * Fonctions préfixées "geq_" pour compatibilité iframe
 * 
 * @author MEMORA Solutions
 * @version 1.0.0
 * @date 1er août 2025
 */

// Vérification de la configuration
if (typeof window.GeqConfig === 'undefined') {
    console.error('GeqConfig n\'est pas défini dans save-system.js');
    window.GeqConfig = {
        features: {},
        limits: {},
        messages: {},
        api: {},
        export: { formats: ['pdf', 'csv', 'png'] },
        advanced: { max_json_size: 1048576 }
    };
}

// Créer l'alias local pour compatibilité
var GeqConfig = window.GeqConfig;

/**
 * Affiche la modal de sauvegarde
 */
function geq_showSaveModal() {
    if (!GeqApp.currentTeams) {
        geq_showToast('Aucune équipe à sauvegarder. Générez d\'abord des équipes.', 'error');
        return;
    }
    
    const modal = document.getElementById('saveModal');
    if (modal) {
        modal.classList.add('active');
        
        // Focus sur le bouton de fermeture pour l'accessibilité
        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) {
            setTimeout(() => closeBtn.focus(), 100);
        }
    }
}

/**
 * Ferme la modal de sauvegarde
 */
function geq_closeSaveModal() {
    const modal = document.getElementById('saveModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Affiche la modal de récupération
 */
function geq_showLoadModal() {
    const modal = document.getElementById('loadModal');
    if (modal) {
        modal.classList.add('active');
        
        // Focus sur le champ de saisie
        const codeInput = document.getElementById('loadCode');
        if (codeInput) {
            setTimeout(() => {
                codeInput.focus();
                codeInput.value = '';
            }, 100);
        }
        
        // Réinitialiser les erreurs
        geq_clearLoadError();
    }
}

/**
 * Ferme la modal de récupération
 */
function geq_closeLoadModal() {
    const modal = document.getElementById('loadModal');
    if (modal) {
        modal.classList.remove('active');
    }
    
    geq_clearLoadError();
}

/**
 * Sauvegarde les équipes actuelles
 */
async function geq_saveTeams() {
    if (!GeqApp.currentTeams) {
        geq_showToast('Aucune équipe à sauvegarder', 'error');
        return;
    }
    
    try {
        // Préparation des données
        const saveData = {
            title: geq_getSaveTitle(),
            teams: GeqApp.currentTeams,
            participants: GeqApp.currentParticipants,
            settings: GeqApp.currentSettings,
            leaderTerm: document.getElementById('leaderTerm')?.value || 'Responsable',
            timestamp: new Date().toISOString()
        };
        
        // Si on a des statistiques, les inclure
        const statsElements = {
            participants: document.getElementById('statParticipants')?.textContent,
            teams: document.getElementById('statTeams')?.textContent,
            average: document.getElementById('statAverage')?.textContent,
            leaders: document.getElementById('statLeaders')?.textContent
        };
        
        if (statsElements.participants) {
            saveData.stats = {
                total_participants: parseInt(statsElements.participants) || 0,
                team_count: parseInt(statsElements.teams) || 0,
                average_per_team: parseFloat(statsElements.average) || 0,
                leaders_count: parseInt(statsElements.leaders) || 0
            };
        }
        
        // Appel API
        const response = await fetch(GeqConfig.api.save, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(saveData)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || 'Erreur de sauvegarde');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erreur de sauvegarde');
        }
        
        // Affichage du résultat
        geq_displaySaveResult(result);
        
        // Analytics
        if (GeqConfig.features.enable_analytics && typeof gtag !== 'undefined') {
            gtag('event', 'save_teams', {
                'teams_count': saveData.teams.length,
                'participants_count': saveData.participants?.length || 0
            });
        }
        
    } catch (error) {
        console.error('Erreur sauvegarde:', error);
        geq_showToast(error.message, 'error');
    }
}

/**
 * Récupère le titre de sauvegarde
 */
function geq_getSaveTitle() {
    // Essayer de récupérer un titre personnalisé (si l'input existe)
    const titleInput = document.getElementById('saveTitle');
    if (titleInput && titleInput.value.trim()) {
        return titleInput.value.trim();
    }
    
    // Générer un titre automatique basé sur la date et le nombre d'équipes
    const now = new Date();
    const dateStr = now.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    
    const teamCount = GeqApp.currentTeams?.length || 0;
    return `Équipes du ${dateStr} (${teamCount} équipes)`;
}

/**
 * Affiche le résultat de la sauvegarde
 */
function geq_displaySaveResult(result) {
    const codeElement = document.getElementById('saveCode');
    const expiresElement = document.getElementById('expiresAt');
    
    if (codeElement) {
        codeElement.textContent = result.code;
        
        // Animation d'apparition du code
        if (GeqConfig.features.enable_animations) {
            codeElement.style.transform = 'scale(0.8)';
            codeElement.style.opacity = '0';
            
            setTimeout(() => {
                codeElement.style.transform = 'scale(1)';
                codeElement.style.opacity = '1';
                codeElement.style.transition = 'all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
            }, 100);
        }
    }
    
    if (expiresElement && result.expires_at) {
        const expiresDate = new Date(result.expires_at);
        expiresElement.textContent = expiresDate.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
    
    // Générer le QR code si activé
    if (GeqConfig.features.enable_qr_sharing && result.sharing_url) {
        geq_generateQRCode(result.sharing_url);
    }
    
    geq_showToast(GeqConfig.messages.success_save, 'success');
}

/**
 * Copie le code de sauvegarde
 */
async function geq_copyCode() {
    const codeElement = document.getElementById('saveCode');
    if (!codeElement) return;
    
    const code = codeElement.textContent;
    
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(code);
        } else {
            // Fallback pour les navigateurs plus anciens
            const textArea = document.createElement('textarea');
            textArea.value = code;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
        
        geq_showToast(GeqConfig.messages.success_copy, 'success', 2000);
        
        // Animation du bouton
        const copyBtn = event?.target?.closest('.copy-btn');
        if (copyBtn && GeqConfig.features.enable_animations) {
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '✓ Copié!';
            copyBtn.style.background = '#10b981';
            copyBtn.style.color = 'white';
            copyBtn.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
                copyBtn.style.background = '';
                copyBtn.style.color = '';
                copyBtn.style.transform = '';
            }, 1500);
        }
        
    } catch (error) {
        console.error('Erreur copie:', error);
        geq_showToast('Impossible de copier le code', 'error');
    }
}

/**
 * Charge des équipes depuis un code
 */
async function geq_loadTeams() {
    const codeInput = document.getElementById('loadCode');
    if (!codeInput) return;
    
    const code = codeInput.value.trim().toUpperCase();
    
    // Validation du format
    if (!geq_validateSaveCode(code)) {
        geq_showLoadError(GeqConfig.messages.error_invalid_format);
        return;
    }
    
    geq_clearLoadError();
    
    try {
        // Désactiver le bouton pendant le chargement
        const loadBtn = document.querySelector('#loadModal .btn-primary');
        if (loadBtn) {
            loadBtn.disabled = true;
            loadBtn.innerHTML = '<div class="spinner"></div> Chargement...';
        }
        
        // Appel API
        const response = await fetch(`${GeqConfig.api.load}?code=${encodeURIComponent(code)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || GeqConfig.messages.error_invalid_code);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || GeqConfig.messages.error_invalid_code);
        }
        
        // Appliquer les données chargées
        geq_applyLoadedData(result);
        
        // Fermer la modal
        geq_closeLoadModal();
        
        // Notification de succès
        const accessInfo = result.access_count > 1 ? ` (${result.access_count}e accès)` : '';
        geq_showToast(`${GeqConfig.messages.success_load}${accessInfo}`, 'success');
        
        // Analytics
        if (GeqConfig.features.enable_analytics && typeof gtag !== 'undefined') {
            gtag('event', 'load_teams', {
                'code': code,
                'teams_count': result.data?.teams?.length || 0
            });
        }
        
    } catch (error) {
        console.error('Erreur chargement:', error);
        geq_showLoadError(error.message);
    } finally {
        // Réactiver le bouton
        const loadBtn = document.querySelector('#loadModal .btn-primary');
        if (loadBtn) {
            loadBtn.disabled = false;
            loadBtn.innerHTML = '🔍 ' + GeqConfig.messages.button_load;
        }
    }
}

/**
 * Affiche une erreur de chargement
 */
function geq_showLoadError(message) {
    const errorElement = document.getElementById('loadError');
    const codeInput = document.getElementById('loadCode');
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }
    
    if (codeInput) {
        codeInput.classList.add('error');
        
        // Animation de secousse
        if (GeqConfig.features.enable_animations) {
            codeInput.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(() => {
                codeInput.style.animation = '';
            }, 500);
        }
    }
}

/**
 * Efface les erreurs de chargement
 */
function geq_clearLoadError() {
    const errorElement = document.getElementById('loadError');
    const codeInput = document.getElementById('loadCode');
    
    if (errorElement) {
        errorElement.classList.remove('show');
    }
    
    if (codeInput) {
        codeInput.classList.remove('error');
    }
}

/**
 * Génère un QR code pour le partage
 */
function geq_generateQRCode(url) {
    const qrContainer = document.getElementById('qrCode');
    if (!qrContainer) return;
    
    // Dans une vraie implémentation, utiliser une librairie comme qrcode.js
    // Pour l'instant, on simule avec un placeholder
    qrContainer.innerHTML = `
        <div style="width: 120px; height: 120px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #6b7280; text-align: center;">
            QR Code<br>
            <small>${url.substring(0, 20)}...</small>
        </div>
    `;
}

/**
 * Exporte les équipes dans le format spécifié
 */
async function geq_exportTeams(format) {
    if (!GeqApp.currentTeams) {
        geq_showToast('Aucune équipe à exporter', 'error');
        return;
    }
    
    if (!GeqConfig.export.formats.includes(format)) {
        geq_showToast(`Format ${format} non supporté`, 'error');
        return;
    }
    
    try {
        const exportData = {
            format: format,
            teams: GeqApp.currentTeams,
            title: geq_getSaveTitle(),
            participants: GeqApp.currentParticipants,
            settings: GeqApp.currentSettings,
            timestamp: new Date().toISOString()
        };
        
        const response = await fetch(GeqConfig.api.export, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(exportData)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || 'Erreur d\'export');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erreur d\'export');
        }
        
        // Télécharger le fichier
        if (result.download_url) {
            geq_downloadFile(result.download_url, result.filename);
        }
        
        geq_showToast(`Export ${format.toUpperCase()} créé avec succès!`, 'success');
        
        // Analytics
        if (GeqConfig.features.enable_analytics && typeof gtag !== 'undefined') {
            gtag('event', 'export_teams', {
                'format': format,
                'teams_count': GeqApp.currentTeams.length
            });
        }
        
    } catch (error) {
        console.error('Erreur export:', error);
        geq_showToast(error.message, 'error');
    }
}

/**
 * Télécharge un fichier
 */
function geq_downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.style.display = 'none';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Validation côté client du système de sauvegarde
 */
function geq_validateSaveData(data) {
    if (!data.teams || !Array.isArray(data.teams) || data.teams.length === 0) {
        return { valid: false, error: 'Aucune équipe à sauvegarder' };
    }
    
    if (data.title && data.title.length > GeqConfig.limits.max_title_length) {
        return { valid: false, error: 'Titre trop long' };
    }
    
    // Vérifier la taille des données JSON
    const jsonSize = JSON.stringify(data).length;
    if (jsonSize > GeqConfig.advanced.max_json_size) {
        return { valid: false, error: 'Données trop volumineuses' };
    }
    
    return { valid: true };
}

/**
 * Partage les équipes via l'API Web Share (si supportée)
 */
async function geq_shareTeams() {
    if (!GeqApp.currentTeams) {
        geq_showToast('Aucune équipe à partager', 'error');
        return;
    }
    
    // Créer un texte simple des équipes
    const shareText = geq_generateShareText();
    
    try {
        if (navigator.share) {
            await navigator.share({
                title: geq_getSaveTitle(),
                text: shareText,
                url: window.location.href
            });
        } else {
            // Fallback: copier le texte
            await navigator.clipboard.writeText(shareText);
            geq_showToast('Équipes copiées dans le presse-papier', 'success');
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error('Erreur partage:', error);
            geq_showToast('Impossible de partager', 'error');
        }
    }
}

/**
 * Génère un texte pour le partage
 */
function geq_generateShareText() {
    if (!GeqApp.currentTeams) return '';
    
    let text = `${geq_getSaveTitle()}\n\n`;
    
    GeqApp.currentTeams.forEach((team, index) => {
        const teamName = team.name || `Équipe ${index + 1}`;
        text += `${teamName}:\n`;
        
        team.members?.forEach(member => {
            const isLeader = member === team.leader;
            text += `  ${isLeader ? '👑 ' : ''}${member}\n`;
        });
        
        text += '\n';
    });
    
    text += `Généré avec le Générateur d'équipes aléatoires - ${window.location.hostname}`;
    
    return text;
}

/**
 * Animation CSS pour la secousse (shake)
 */
document.addEventListener('DOMContentLoaded', function() {
    if (typeof GeqConfig !== 'undefined' && GeqConfig.features && GeqConfig.features.enable_animations) {
        const shakeCSS = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }
        `;
        
        // Injecter le CSS si pas déjà présent
        if (!document.getElementById('geq-shake-animation')) {
            const style = document.createElement('style');
            style.id = 'geq-shake-animation';
            style.textContent = shakeCSS;
            document.head.appendChild(style);
        }
    }
});

console.log('Système de sauvegarde chargé');