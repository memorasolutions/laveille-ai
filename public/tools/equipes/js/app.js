/**
 * JavaScript Principal - Générateur d'équipes aléatoires
 * Fonctions préfixées "geq_" pour compatibilité iframe
 * 
 * @author MEMORA Solutions
 * @version 1.0.0
 * @date 1er août 2025
 */

// Vérification de la configuration
if (typeof window.GeqConfig === 'undefined') {
    console.error('GeqConfig n\'est pas défini. Vérifiez que la configuration est chargée avant ce script.');
    // Créer une configuration par défaut pour éviter les erreurs
    window.GeqConfig = {
        features: {},
        limits: { min_participants: 2, max_participants: 100, min_teams: 2, max_teams: 50 },
        messages: {},
        api: {},
        ui: {},
        export: { formats: ['pdf', 'csv', 'png'] },
        advanced: {}
    };
}

// Créer l'alias local pour compatibilité
var GeqConfig = window.GeqConfig;

// État global de l'application (préfixé)
window.GeqApp = {
    currentTeams: null,
    currentParticipants: null,
    currentSettings: null,
    isGenerating: false
};

/**
 * Initialisation de l'application
 */
document.addEventListener('DOMContentLoaded', function() {
    geq_initializeApp();
});

/**
 * Initialise l'application
 */
function geq_initializeApp() {
    // Mise à jour du compteur de participants
    geq_updateParticipantCount();
    
    // Gestion du mode de génération
    geq_updateModeUI();
    
    // Chargement automatique si code dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const loadCode = urlParams.get('load');
    if (loadCode && GeqConfig && GeqConfig.features && GeqConfig.features.enable_save_system) {
        setTimeout(() => geq_autoLoadFromURL(loadCode), 500);
    }
    
    // Événements clavier
    document.addEventListener('keydown', geq_handleGlobalKeydown);
    
    // Nettoyage des fichiers d'export temporaires
    setTimeout(geq_cleanupTempFiles, 2000);
    
    // Chargement des statistiques si activé
    if (GeqConfig && GeqConfig.features && GeqConfig.features.enable_stats) {
        geq_loadFooterStats();
    }
    
    console.log('Générateur d\'équipes aléatoires initialisé');
}

/**
 * Met à jour le compteur de participants
 */
function geq_updateParticipantCount() {
    const textarea = document.getElementById('participants');
    const counter = document.getElementById('participantCount');
    const leadersInfo = document.getElementById('leadersCount');
    
    if (!textarea || !counter) return;
    
    const text = textarea.value.trim();
    const participants = text ? text.split('\n').filter(line => line.trim()) : [];
    const leaders = participants.filter(p => p.trim().endsWith('*'));
    
    // Mise à jour du compteur principal
    counter.textContent = participants.length;
    
    // Mise à jour des leaders
    if (leadersInfo && GeqConfig && GeqConfig.features && GeqConfig.features.enable_leaders) {
        const leadersCount = leaders.length;
        leadersInfo.querySelector('.count').textContent = leadersCount;
        
        if (leadersCount > 0) {
            leadersInfo.style.display = 'inline-flex';
        } else {
            leadersInfo.style.display = 'none';
        }
    }
    
    // Validation en temps réel
    geq_validateParticipantsCount(participants.length);
}

/**
 * Valide le nombre de participants
 */
function geq_validateParticipantsCount(count) {
    const textarea = document.getElementById('participants');
    if (!textarea) return;
    
    textarea.classList.remove('border-red-500', 'border-green-500');
    
    if (count < GeqConfig.limits.min_participants) {
        textarea.style.borderColor = '#ef4444';
    } else if (count > GeqConfig.limits.max_participants) {
        textarea.style.borderColor = '#ef4444';
    } else if (count > 0) {
        textarea.style.borderColor = '#10b981';
    } else {
        textarea.style.borderColor = '';
    }
}

/**
 * Met à jour l'interface selon le mode sélectionné
 */
function geq_updateModeUI() {
    const modeRadios = document.querySelectorAll('input[name="mode"]');
    const valueLabel = document.getElementById('valueLabel');
    const valueInput = document.getElementById('teamValue');
    
    if (!valueLabel || !valueInput) return;
    
    const selectedMode = document.querySelector('input[name="mode"]:checked');
    if (!selectedMode) return;
    
    const mode = selectedMode.value;
    
    if (mode === 'teams') {
        valueLabel.textContent = GeqConfig.messages.label_team_count;
        valueInput.placeholder = '2';
        valueInput.min = GeqConfig.limits.min_teams;
        valueInput.max = GeqConfig.limits.max_teams;
        
        if (parseInt(valueInput.value) > GeqConfig.limits.max_teams) {
            valueInput.value = GeqConfig.limits.max_teams;
        }
    } else {
        valueLabel.textContent = GeqConfig.messages.label_members_per_team;
        valueInput.placeholder = '3';
        valueInput.min = 1;
        valueInput.max = Math.floor(GeqConfig.limits.max_participants / 2);
        
        if (parseInt(valueInput.value) > Math.floor(GeqConfig.limits.max_participants / 2)) {
            valueInput.value = Math.floor(GeqConfig.limits.max_participants / 2);
        }
    }
}

/**
 * Génère les équipes
 */
async function geq_generateTeams(event) {
    if (event) {
        event.preventDefault();
    }
    
    if (GeqApp.isGenerating) return;
    
    try {
        GeqApp.isGenerating = true;
        geq_setLoadingState(true);
        
        // Collecte des données
        const formData = geq_collectFormData();
        if (!formData) {
            throw new Error('Données du formulaire invalides');
        }
        
        // Validation
        const validation = geq_validateFormData(formData);
        if (!validation.valid) {
            throw new Error(validation.error);
        }
        
        // Appel API
        const response = await fetch(GeqConfig.api.generate, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || 'Erreur de génération');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erreur de génération');
        }
        
        // Sauvegarde de l'état
        GeqApp.currentTeams = result.teams;
        GeqApp.currentParticipants = result.participants;
        GeqApp.currentSettings = result.settings;
        
        // Affichage des résultats
        geq_displayResults(result);
        
        // Analytics
        if (GeqConfig.features.enable_analytics && typeof gtag !== 'undefined') {
            gtag('event', 'generate_teams', {
                'participants_count': result.stats.total_participants,
                'teams_count': result.stats.team_count,
                'has_leaders': result.stats.leaders_count > 0
            });
        }
        
        geq_showToast('Équipes générées avec succès!', 'success');
        
    } catch (error) {
        console.error('Erreur génération:', error);
        geq_showToast(error.message, 'error');
    } finally {
        GeqApp.isGenerating = false;
        geq_setLoadingState(false);
    }
}

/**
 * Collecte les données du formulaire
 */
function geq_collectFormData() {
    const participants = document.getElementById('participants')?.value?.trim();
    const mode = document.querySelector('input[name="mode"]:checked')?.value;
    const value = parseInt(document.getElementById('teamValue')?.value);
    
    if (!participants || !mode || !value) {
        return null;
    }
    
    return {
        participants: participants,
        mode: mode,
        value: value,
        timestamp: new Date().toISOString()
    };
}

/**
 * Valide les données du formulaire
 */
function geq_validateFormData(data) {
    const lines = data.participants.split('\n').filter(line => line.trim());
    
    if (lines.length < GeqConfig.limits.min_participants) {
        return {
            valid: false,
            error: `Minimum ${GeqConfig.limits.min_participants} participants requis`
        };
    }
    
    if (lines.length > GeqConfig.limits.max_participants) {
        return {
            valid: false,
            error: `Maximum ${GeqConfig.limits.max_participants} participants autorisés`
        };
    }
    
    if (data.mode === 'teams' && data.value > lines.length) {
        return {
            valid: false,
            error: 'Impossible de créer plus d\'équipes que de participants'
        };
    }
    
    // Validation des leaders
    if (GeqConfig.features.enable_leaders) {
        const leaders = lines.filter(line => line.trim().endsWith('*'));
        if (leaders.length > 0 && data.mode === 'teams' && leaders.length !== data.value) {
            return {
                valid: false,
                error: `Nombre de leaders (${leaders.length}) doit égaler le nombre d'équipes (${data.value})`
            };
        }
    }
    
    return { valid: true };
}

/**
 * Affiche les résultats
 */
function geq_displayResults(result) {
    const resultsSection = document.getElementById('resultsSection');
    const teamsGrid = document.getElementById('teamsGrid');
    
    if (!resultsSection || !teamsGrid) return;
    
    // Mise à jour des statistiques
    geq_updateStats(result.stats);
    
    // Génération des cartes d'équipes
    teamsGrid.innerHTML = '';
    
    result.teams.forEach((team, index) => {
        const teamCard = geq_createTeamCard(team, index);
        teamsGrid.appendChild(teamCard);
    });
    
    // Affichage avec animation
    resultsSection.style.display = 'block';
    
    if (GeqConfig.features.enable_animations) {
        // Animation d'apparition
        resultsSection.classList.add('animate-fade-in');
        
        // Animation échelonnée des équipes
        const teamCards = teamsGrid.querySelectorAll('.team-card');
        teamCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate-slide-up');
        });
    }
    
    // Scroll vers les résultats
    setTimeout(() => {
        resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 300);
}

/**
 * Crée une carte d'équipe
 */
function geq_createTeamCard(team, index) {
    const card = document.createElement('div');
    card.className = 'team-card';
    
    const teamName = team.name || `Équipe ${index + 1}`;
    const members = team.members || [];
    const leader = team.leader;
    
    card.innerHTML = `
        <div class="team-name">
            <span class="team-number">${index + 1}</span>
            ${geq_escapeHtml(teamName)}
        </div>
        <ul class="members-list">
            ${members.map(member => `
                <li class="member-item ${member === leader ? 'leader' : ''}">
                    ${geq_escapeHtml(member)}
                </li>
            `).join('')}
        </ul>
        <div class="member-count">
            ${members.length} membre${members.length > 1 ? 's' : ''}
        </div>
    `;
    
    return card;
}

/**
 * Met à jour les statistiques
 */
function geq_updateStats(stats) {
    const elements = {
        statParticipants: document.getElementById('statParticipants'),
        statTeams: document.getElementById('statTeams'),
        statAverage: document.getElementById('statAverage'),
        statLeaders: document.getElementById('statLeaders'),
        statLeadersDiv: document.getElementById('statLeadersDiv')
    };
    
    if (elements.statParticipants) {
        elements.statParticipants.textContent = stats.total_participants;
    }
    
    if (elements.statTeams) {
        elements.statTeams.textContent = stats.team_count;
    }
    
    if (elements.statAverage) {
        elements.statAverage.textContent = stats.average_per_team;
    }
    
    if (elements.statLeaders && elements.statLeadersDiv) {
        elements.statLeaders.textContent = stats.leaders_count;
        
        if (stats.leaders_count > 0) {
            elements.statLeadersDiv.style.display = 'block';
        } else {
            elements.statLeadersDiv.style.display = 'none';
        }
    }
}

/**
 * Régénère les équipes
 */
function geq_regenerateTeams() {
    if (GeqApp.isGenerating) return;
    
    geq_generateTeams();
}

/**
 * Gère l'état de chargement
 */
function geq_setLoadingState(loading) {
    const generateBtn = document.getElementById('btnGenerate');
    if (!generateBtn) return;
    
    const btnText = generateBtn.querySelector('.btn-text');
    const btnLoading = generateBtn.querySelector('.btn-loading');
    
    if (loading) {
        generateBtn.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoading) btnLoading.style.display = 'flex';
    } else {
        generateBtn.disabled = false;
        if (btnText) btnText.style.display = 'block';
        if (btnLoading) btnLoading.style.display = 'none';
    }
}

/**
 * Affiche une notification toast
 */
function geq_showToast(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    toast.innerHTML = `
        <div class="toast-message">${geq_escapeHtml(message)}</div>
        <button class="toast-close" onclick="geq_closeToast(this)">×</button>
    `;
    
    container.appendChild(toast);
    
    // Animation d'apparition
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Suppression automatique
    setTimeout(() => geq_closeToast(toast.querySelector('.toast-close')), duration);
}

/**
 * Ferme une notification toast
 */
function geq_closeToast(button) {
    const toast = button.closest('.toast');
    if (!toast) return;
    
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
}

/**
 * Gère les raccourcis clavier globaux
 */
function geq_handleGlobalKeydown(event) {
    // Ctrl/Cmd + Enter = Génération
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        geq_generateTeams();
        return;
    }
    
    // Escape = Fermer les modales
    if (event.key === 'Escape') {
        geq_closeAllModals();
        return;
    }
    
    // Ctrl/Cmd + S = Sauvegarde (si résultats disponibles)
    if ((event.ctrlKey || event.metaKey) && event.key === 's' && GeqApp.currentTeams) {
        event.preventDefault();
        if (GeqConfig.features.enable_save_system) {
            geq_showSaveModal();
        }
        return;
    }
    
    // Ctrl/Cmd + L = Chargement
    if ((event.ctrlKey || event.metaKey) && event.key === 'l') {
        event.preventDefault();
        if (GeqConfig.features.enable_save_system) {
            geq_showLoadModal();
        }
        return;
    }
}

/**
 * Ferme toutes les modales ouvertes
 */
function geq_closeAllModals() {
    const modals = document.querySelectorAll('.modal.active');
    modals.forEach(modal => modal.classList.remove('active'));
}

/**
 * Bascule le mode plein écran
 */
function geq_toggleFullscreen() {
    const body = document.body;
    
    if (body.classList.contains('fullscreen-mode')) {
        body.classList.remove('fullscreen-mode');
        document.getElementById('btnFullscreen').innerHTML = '⛶ Plein écran';
    } else {
        body.classList.add('fullscreen-mode');
        document.getElementById('btnFullscreen').innerHTML = '× Quitter';
    }
}

/**
 * Chargement automatique depuis l'URL
 */
async function geq_autoLoadFromURL(code) {
    if (!code || code.length !== 8) return;
    
    try {
        const response = await fetch(`${GeqConfig.api.load}?code=${encodeURIComponent(code)}`);
        
        if (!response.ok) {
            throw new Error('Code invalide ou expiré');
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            geq_applyLoadedData(result);
            geq_showToast(`Équipes "${result.title}" récupérées avec succès!`, 'success');
            
            // Nettoyer l'URL
            const url = new URL(window.location);
            url.searchParams.delete('load');
            window.history.replaceState({}, '', url);
        }
        
    } catch (error) {
        console.warn('Chargement automatique échoué:', error);
    }
}

/**
 * Applique les données chargées
 */
function geq_applyLoadedData(result) {
    const data = result.data;
    
    // Restaurer les participants si disponibles
    if (data.participants && Array.isArray(data.participants)) {
        const textarea = document.getElementById('participants');
        if (textarea) {
            textarea.value = data.participants.join('\n');
            geq_updateParticipantCount();
        }
    }
    
    // Restaurer le terme des responsables si disponible
    if (data.leaderTerm) {
        const leaderTermInput = document.getElementById('leaderTerm');
        const leaderTermModal = document.getElementById('leaderTermModal');
        if (leaderTermInput) {
            leaderTermInput.value = data.leaderTerm;
        }
        if (leaderTermModal) {
            leaderTermModal.value = data.leaderTerm;
        }
        // Mettre à jour l'affichage si la fonction existe
        if (typeof updateLeaderTermDisplay === 'function') {
            updateLeaderTermDisplay();
        }
    }
    
    // Restaurer les paramètres si disponibles
    if (data.settings) {
        const modeRadio = document.querySelector(`input[name="mode"][value="${data.settings.mode}"]`);
        if (modeRadio) {
            modeRadio.checked = true;
            geq_updateModeUI();
        }
        
        const valueInput = document.getElementById('teamValue');
        if (valueInput && data.settings.value) {
            valueInput.value = data.settings.value;
        }
    }
    
    // Afficher les équipes
    if (data.teams) {
        GeqApp.currentTeams = data.teams;
        GeqApp.currentParticipants = data.participants;
        GeqApp.currentSettings = data.settings;
        
        geq_displayResults({
            teams: data.teams,
            stats: data.stats || {
                total_participants: (data.participants || []).length,
                team_count: data.teams.length,
                leaders_count: (data.leaders || []).length,
                average_per_team: Math.round((data.participants || []).length / data.teams.length * 10) / 10
            }
        });
    }
}

/**
 * Charge les statistiques du footer
 */
async function geq_loadFooterStats() {
    try {
        // Simulation - dans une vraie implémentation, appeler une API stats
        const footerStats = document.getElementById('footerStats');
        if (footerStats) {
            footerStats.innerHTML = `
                <span>Équipes générées aujourd'hui: <strong>42</strong></span>
                <span style="margin-left: 1rem;">Codes sauvegardés: <strong>18</strong></span>
            `;
        }
    } catch (error) {
        console.warn('Impossible de charger les statistiques:', error);
    }
}

/**
 * Nettoie les fichiers temporaires d'export
 */
function geq_cleanupTempFiles() {
    // Dans une vraie implémentation, appeler un endpoint de nettoyage
    // Pour l'instant, juste un log
    console.log('Nettoyage des fichiers temporaires...');
}

/**
 * Échappe le HTML pour éviter les injections XSS
 */
function geq_escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Valide un code de sauvegarde
 */
function geq_validateSaveCode(code) {
    const length = GeqConfig?.features?.save_system?.code_length || 8;
    const pattern = new RegExp(`^[A-Z0-9]{${length}}$`);
    return pattern.test(code.toUpperCase());
}

/**
 * Formate l'entrée du code (majuscules)
 */
function geq_formatCodeInput(input) {
    input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
}

/**
 * Gère la touche Enter dans le champ de chargement
 */
function geq_handleLoadKeyup(event) {
    if (event.key === 'Enter') {
        geq_loadTeams();
    }
}

/**
 * Utilitaires de debug (mode développement)
 */
if (GeqConfig?.advanced?.debug) {
    window.GeqDebug = {
        getAppState: () => GeqApp,
        generateTestData: () => {
            const textarea = document.getElementById('participants');
            if (textarea) {
                textarea.value = 'Alice\nBob*\nCharlie\nDiana*\nEmma\nFrank\nGrace\nHenry';
                geq_updateParticipantCount();
            }
        },
        clearResults: () => {
            const resultsSection = document.getElementById('resultsSection');
            if (resultsSection) {
                resultsSection.style.display = 'none';
            }
            GeqApp.currentTeams = null;
        }
    };
    
    console.log('Mode debug activé. Utilisez window.GeqDebug pour les outils de développement.');
}

console.log('Application JavaScript chargée - Version 1.0.0');