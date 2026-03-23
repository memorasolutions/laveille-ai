/**
 * Oscilloscope.js - Classe de dessin de l'oscilloscope
 * Canvas HTML5 avec grille professionnelle et waveforms
 */

class Oscilloscope {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.legendElement = document.getElementById('legend');

        // Configuration
        this.divisions = 10;
        this.subDivisions = 5;

        // Configuration de la grille (par thème) - WCAG 2.2 optimisé
        this.gridConfig = {
            dark: {
                divisionColor: 'blue',
                divisionOpacity: 25,
                subDivisionColor: 'blue',
                subDivisionOpacity: 15,
                axisColor: 'blue',
                axisOpacity: 80
            },
            light: {
                divisionColor: 'black',
                divisionOpacity: 25,
                subDivisionColor: 'black',
                subDivisionOpacity: 15,
                axisColor: 'black',
                axisOpacity: 80
            }
        };

        // Palette de couleurs de grille - Charte formulaire_prompt_v2
        this.gridColors = {
            blue:  { r: 52, g: 152, b: 219 },  // #3498db - Primary
            cyan:  { r: 52, g: 152, b: 219 },  // Alias vers blue pour rétrocompatibilité
            green: { r: 39, g: 174, b: 96 },   // #27ae60 - Success
            amber: { r: 243, g: 156, b: 18 },  // #f39c12 - Warning
            white: { r: 255, g: 255, b: 255 },
            black: { r: 0, g: 0, b: 0 },
            red:   { r: 231, g: 76, b: 60 }    // #e74c3c - Danger
        };

        // Couleurs des canaux - Palette Okabe-Ito (accessible daltoniens)
        this.channelColors = {
            1: '#E69F00', // Orange
            2: '#56B4E9', // Bleu ciel
            3: '#009E73', // Vert teal
            4: '#CC79A7'  // Rose
        };

        // État des canaux (offset en divisions, -5 à +5)
        this.channels = {
            1: { signal: null, scale: 50, data: null, offset: 0 },
            2: { signal: null, scale: 50, data: null, offset: 0 },
            3: { signal: null, scale: 50, data: null, offset: 0 },
            4: { signal: null, scale: 50, data: null, offset: 0 }
        };

        // Paramètres de temps
        this.timePerDiv = 0.002; // 2ms par défaut
        this.phaseOffset = 0;
        this.frequency = 60;

        // Signaux disponibles
        this.signals = {};

        // Option de normalisation des amplitudes (pour clarifier le déphasage)
        // Activé par défaut pour une visualisation correcte du déphasage
        this.normalizeAmplitudes = true;

        // Marqueurs de déphasage (lignes verticales aux pics)
        this.showPhaseMarkers = true;
        this.phaseMarkerChannels = [1, 2]; // Canaux à comparer (2 max)

        // Initialiser le canvas responsive
        this.setupResponsive();
    }

    /**
     * Configurer le canvas responsive
     */
    setupResponsive() {
        const resize = () => {
            const container = this.canvas.parentElement;
            const dpr = window.devicePixelRatio || 1;

            // Réinitialiser la taille du canvas pour permettre au conteneur de se redimensionner
            this.canvas.style.width = '100%';
            this.canvas.style.height = 'auto';

            // Maintenant lire la taille réelle du conteneur
            const rect = container.getBoundingClientRect();

            // Calculer la taille optimale
            const maxWidth = rect.width - 32; // Padding
            const aspectRatio = 4 / 3;
            const width = Math.max(300, maxWidth); // Minimum 300px
            const height = width / aspectRatio;

            // Appliquer la résolution haute définition
            this.canvas.width = width * dpr;
            this.canvas.height = height * dpr;
            this.canvas.style.width = width + 'px';
            this.canvas.style.height = height + 'px';

            this.ctx.scale(dpr, dpr);
            this.width = width;
            this.height = height;

            this.draw();
        };

        window.addEventListener('resize', resize);
        resize();
    }

    /**
     * Définir les signaux disponibles
     */
    setSignals(signals) {
        this.signals = signals;
    }

    /**
     * Définir la fréquence
     */
    setFrequency(freq) {
        this.frequency = freq;
    }

    /**
     * Configurer un canal
     */
    setChannel(channelNum, signalName, scale) {
        if (this.channels[channelNum]) {
            this.channels[channelNum].signal = signalName;
            this.channels[channelNum].scale = scale;
        }
    }

    /**
     * Définir le décalage vertical d'un canal (en divisions)
     */
    setChannelOffset(channelNum, offset) {
        if (this.channels[channelNum]) {
            this.channels[channelNum].offset = offset;
        }
    }

    /**
     * Définir la couleur d'un canal
     */
    setChannelColor(channelNum, color) {
        const ch = parseInt(channelNum, 10);
        if (this.channelColors[ch]) {
            this.channelColors[ch] = color;
            // Mettre à jour la variable CSS correspondante
            document.documentElement.style.setProperty(`--channel-${ch}`, color);
            // Redessiner l'oscilloscope
            this.draw();
        }
    }

    /**
     * Définir le temps par division
     */
    setTimePerDiv(time) {
        this.timePerDiv = time;
    }

    /**
     * Définir le décalage de phase
     */
    setPhaseOffset(offset) {
        this.phaseOffset = offset;
    }

    /**
     * Activer/désactiver les marqueurs de déphasage
     */
    setShowPhaseMarkers(show) {
        this.showPhaseMarkers = show;
        this.draw();
    }

    /**
     * Définir les canaux pour les marqueurs de déphasage (2 max)
     */
    setPhaseMarkerChannels(channels) {
        if (Array.isArray(channels) && channels.length <= 2) {
            this.phaseMarkerChannels = channels.map(c => parseInt(c, 10));
            this.draw();
        }
    }

    /**
     * Récupérer le thème de grille (pour les couleurs des lignes)
     */
    getCurrentTheme() {
        // Lire le thème de grille spécifique à l'oscilloscope (indépendant du thème UI)
        const oscTheme = getComputedStyle(document.documentElement).getPropertyValue('--oscilloscope-theme').trim();
        return oscTheme || 'dark';
    }

    /**
     * Configurer la grille (pour le thème actuel)
     */
    setGridConfig(config, theme = null) {
        const t = theme || this.getCurrentTheme();
        if (!this.gridConfig[t]) {
            this.gridConfig[t] = {
                divisionColor: 'blue', divisionOpacity: 25,
                subDivisionColor: 'blue', subDivisionOpacity: 15,
                axisColor: 'blue', axisOpacity: 80
            };
        }
        if (config.divisionColor !== undefined) this.gridConfig[t].divisionColor = config.divisionColor;
        if (config.divisionOpacity !== undefined) this.gridConfig[t].divisionOpacity = config.divisionOpacity;
        if (config.subDivisionColor !== undefined) this.gridConfig[t].subDivisionColor = config.subDivisionColor;
        if (config.subDivisionOpacity !== undefined) this.gridConfig[t].subDivisionOpacity = config.subDivisionOpacity;
        if (config.axisColor !== undefined) this.gridConfig[t].axisColor = config.axisColor;
        if (config.axisOpacity !== undefined) this.gridConfig[t].axisOpacity = config.axisOpacity;
    }

    /**
     * Récupérer la config de grille (pour le thème actuel)
     */
    getGridConfig(theme = null) {
        const t = theme || this.getCurrentTheme();
        return { ...this.gridConfig[t] };
    }

    /**
     * Récupérer toute la config (tous les thèmes)
     */
    getFullGridConfig() {
        return JSON.parse(JSON.stringify(this.gridConfig));
    }

    /**
     * Charger toute la config (tous les thèmes)
     */
    setFullGridConfig(config) {
        if (config.dark) this.gridConfig.dark = { ...this.gridConfig.dark, ...config.dark };
        if (config.light) this.gridConfig.light = { ...this.gridConfig.light, ...config.light };
    }

    /**
     * Dessiner l'oscilloscope complet
     */
    draw() {
        // Effacer le canvas avec la couleur du thème
        const bgColor = getComputedStyle(document.documentElement).getPropertyValue('--oscilloscope-bg').trim() || '#0d1117';
        this.ctx.fillStyle = bgColor;
        this.ctx.fillRect(0, 0, this.width, this.height);

        // Dessiner la grille
        this.drawGrid();

        // Générer et dessiner les waveforms
        this.generateWaveforms();
        this.drawWaveforms();

        // Mettre à jour la légende
        this.updateLegend();
    }

    /**
     * Récupérer les couleurs de grille selon la configuration
     */
    getThemeColors() {
        const config = this.getGridConfig();
        const divisionBase = this.gridColors[config.divisionColor] || this.gridColors.cyan;
        const subDivisionBase = this.gridColors[config.subDivisionColor] || this.gridColors.cyan;
        const axisBase = this.gridColors[config.axisColor] || this.gridColors.cyan;
        const divisionOpacity = config.divisionOpacity / 100;
        const subDivisionOpacity = config.subDivisionOpacity / 100;
        const axisOpacity = config.axisOpacity / 100;

        return {
            subDivision: `rgba(${subDivisionBase.r}, ${subDivisionBase.g}, ${subDivisionBase.b}, ${subDivisionOpacity})`,
            division: `rgba(${divisionBase.r}, ${divisionBase.g}, ${divisionBase.b}, ${divisionOpacity})`,
            axis: `rgba(${axisBase.r}, ${axisBase.g}, ${axisBase.b}, ${axisOpacity})`,
            marker: `rgba(${axisBase.r}, ${axisBase.g}, ${axisBase.b}, ${Math.min(1, axisOpacity * 1.3)})`,
            border: `rgba(${divisionBase.r}, ${divisionBase.g}, ${divisionBase.b}, ${Math.min(1, divisionOpacity * 1.5)})`
        };
    }

    /**
     * Dessiner la grille de l'oscilloscope
     */
    drawGrid() {
        const ctx = this.ctx;
        const w = this.width;
        const h = this.height;

        // Récupérer les couleurs selon le thème actuel
        const colors = this.getThemeColors();

        // Calculer les positions exactes en pixels pour éviter l'antialiasing incohérent
        const totalSubDivs = this.divisions * this.subDivisions;

        // Précalculer toutes les positions X et Y comme entiers
        const xPositions = [];
        const yPositions = [];
        for (let i = 0; i <= totalSubDivs; i++) {
            xPositions.push(Math.floor(i * w / totalSubDivs));
            yPositions.push(Math.floor(i * h / totalSubDivs));
        }

        // Sous-divisions (lignes pointillées) - dessinées à TOUTES les positions
        // Les divisions seront dessinées par-dessus si visibles
        ctx.strokeStyle = colors.subDivision;
        ctx.lineWidth = 1;
        ctx.setLineDash([1, 4]); // Motif pointillé : 1px trait, 4px espace
        ctx.beginPath();

        for (let i = 1; i < totalSubDivs; i++) {
            const x = xPositions[i] + 0.5;
            const y = yPositions[i] + 0.5;

            // Ligne verticale
            ctx.moveTo(x, 0);
            ctx.lineTo(x, h);

            // Ligne horizontale
            ctx.moveTo(0, y);
            ctx.lineTo(w, y);
        }
        ctx.stroke();
        ctx.setLineDash([]); // Réinitialiser pour les lignes pleines

        // Divisions principales (tous les 5 sous-divisions)
        ctx.strokeStyle = colors.division;
        ctx.lineWidth = 1;
        ctx.beginPath();

        for (let i = 1; i < this.divisions; i++) {
            const idx = i * this.subDivisions;
            const x = xPositions[idx] + 0.5;
            const y = yPositions[idx] + 0.5;

            // Ligne verticale
            ctx.moveTo(x, 0);
            ctx.lineTo(x, h);

            // Ligne horizontale
            ctx.moveTo(0, y);
            ctx.lineTo(w, y);
        }
        ctx.stroke();

        // Axes centraux (plus visibles)
        // Arrondir à 0.5 pixel pour un rendu net et identique
        const centerY = Math.round(h / 2) + 0.5;
        const centerX = Math.round(w / 2) + 0.5;

        ctx.strokeStyle = colors.axis;
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        // Axe horizontal central
        ctx.moveTo(0, centerY);
        ctx.lineTo(w, centerY);
        // Axe vertical central
        ctx.moveTo(centerX, 0);
        ctx.lineTo(centerX, h);
        ctx.stroke();

        // Marqueurs sur les axes centraux (comme sur un vrai oscilloscope)
        ctx.strokeStyle = colors.marker;
        ctx.lineWidth = 2;
        const markerSize = 6;

        // Marqueurs horizontaux (sur l'axe horizontal central)
        for (let i = 0; i <= this.divisions; i++) {
            const x = xPositions[i * this.subDivisions] + 0.5;
            ctx.beginPath();
            ctx.moveTo(x, centerY - markerSize);
            ctx.lineTo(x, centerY + markerSize);
            ctx.stroke();
        }

        // Marqueurs verticaux (sur l'axe vertical central)
        for (let i = 0; i <= this.divisions; i++) {
            const y = yPositions[i * this.subDivisions] + 0.5;
            ctx.beginPath();
            ctx.moveTo(centerX - markerSize, y);
            ctx.lineTo(centerX + markerSize, y);
            ctx.stroke();
        }

        // Bordure
        ctx.strokeStyle = colors.border;
        ctx.lineWidth = 2;
        ctx.strokeRect(1, 1, w - 2, h - 2);
    }

    /**
     * Générer les données de waveform pour chaque canal actif
     */
    generateWaveforms() {
        for (let ch = 1; ch <= 4; ch++) {
            const channel = this.channels[ch];
            if (channel.signal && this.signals[channel.signal]) {
                const signal = this.signals[channel.signal];
                channel.data = generateWaveformData(
                    signal,
                    this.frequency,
                    this.timePerDiv,
                    this.divisions,
                    this.phaseOffset
                );
            } else {
                channel.data = null;
            }
        }
    }

    /**
     * Dessiner les waveforms
     */
    drawWaveforms() {
        const ctx = this.ctx;
        const totalTime = this.timePerDiv * this.divisions;

        // Collecter les positions des pics pour les marqueurs de phase
        const peakMarkers = [];

        // Si normalisation activée, trouver l'amplitude max de chaque canal
        const channelMaxAmplitudes = {};
        if (this.normalizeAmplitudes) {
            for (let ch = 1; ch <= 4; ch++) {
                const channel = this.channels[ch];
                if (!channel.data || channel.data.length === 0) continue;
                let maxAmp = 0;
                channel.data.forEach(p => {
                    if (Math.abs(p.y) > maxAmp) maxAmp = Math.abs(p.y);
                });
                channelMaxAmplitudes[ch] = maxAmp;
            }
        }

        for (let ch = 1; ch <= 4; ch++) {
            const channel = this.channels[ch];
            if (!channel.data || channel.data.length === 0) continue;

            const color = this.channelColors[ch];
            const scale = channel.scale; // Volts ou Ampères par division

            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            // Clip region pour que les courbes sortent proprement du cadre
            ctx.save();
            ctx.beginPath();
            ctx.rect(0, 0, this.width, this.height);
            ctx.clip();

            ctx.beginPath();

            // Trouver le premier pic local (premier maximum local) pour le marqueur de phase
            let firstPeakIdx = -1;
            let prevY = -Infinity;
            let wasRising = false;

            // Facteur de normalisation (si activé)
            const normalizedHeight = this.height * 0.35; // 70% de la demi-hauteur
            const normFactor = this.normalizeAmplitudes && channelMaxAmplitudes[ch] > 0
                ? normalizedHeight / channelMaxAmplitudes[ch]
                : null;

            // Calculer le décalage vertical en pixels (offset en divisions)
            const offsetPixels = (channel.offset || 0) * (this.height / this.divisions);

            channel.data.forEach((point, i) => {
                // Convertir le temps en position X
                const x = (point.t / totalTime) * this.width;

                // Convertir la valeur en position Y
                let y;
                if (normFactor) {
                    // Mode normalisé: toutes les courbes ont la même amplitude visuelle
                    y = (this.height / 2) - (point.y * normFactor);
                } else {
                    // Mode normal: utiliser l'échelle du canal
                    const unitsPerPixel = (scale * 5) / (this.height / 2);
                    y = (this.height / 2) - (point.y / unitsPerPixel);
                }

                // Appliquer le décalage vertical
                y += offsetPixels;

                // Pas de clamping - la courbe sort naturellement du cadre
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }

                // Détecter le premier pic local (maximum local)
                // Un pic est quand on montait et qu'on commence à descendre
                if (firstPeakIdx === -1 && i > 0) {
                    const isRising = point.y > prevY;
                    // Si on montait et maintenant on descend = on a trouvé un pic
                    if (wasRising && !isRising && prevY > 0) {
                        firstPeakIdx = i - 1; // Le pic était au point précédent
                    }
                    wasRising = isRising;
                }
                prevY = point.y;
            });

            ctx.stroke();
            ctx.restore();

            // Sauvegarder la position du premier pic local pour le marqueur
            // Si aucun pic local trouvé, utiliser le premier point positif
            const peakIdx = firstPeakIdx >= 0 ? firstPeakIdx : 0;
            if (channel.data[peakIdx]) {
                const peakX = (channel.data[peakIdx].t / totalTime) * this.width;
                const signalName = channel.signal || '';
                peakMarkers.push({ x: peakX, color: color, ch: ch, signal: signalName });
            }
        }

        // Dessiner les marqueurs de phase (lignes verticales aux pics)
        // Filtrer pour ne garder que les canaux sélectionnés
        const selectedMarkers = peakMarkers.filter(m =>
            this.phaseMarkerChannels.includes(m.ch)
        );

        if (this.showPhaseMarkers && selectedMarkers.length >= 2) {
            ctx.setLineDash([8, 4]);
            ctx.lineWidth = 2;

            // Ne dessiner que les 2 premiers marqueurs sélectionnés
            selectedMarkers.slice(0, 2).forEach((marker) => {
                ctx.strokeStyle = marker.color;
                ctx.globalAlpha = 0.85;
                ctx.beginPath();
                ctx.moveTo(marker.x, 0);
                ctx.lineTo(marker.x, this.height);
                ctx.stroke();

                // Dessiner un triangle au sommet pour indiquer le pic
                ctx.fillStyle = marker.color;
                ctx.globalAlpha = 1;
                ctx.beginPath();
                ctx.moveTo(marker.x, 8);
                ctx.lineTo(marker.x - 6, 0);
                ctx.lineTo(marker.x + 6, 0);
                ctx.closePath();
                ctx.fill();
            });

            ctx.globalAlpha = 1;
            ctx.setLineDash([]);
        }
    }

    /**
     * Mettre à jour la légende
     */
    updateLegend() {
        if (!this.legendElement) return;

        const items = [];

        for (let ch = 1; ch <= 4; ch++) {
            const channel = this.channels[ch];
            if (channel.signal && this.signals[channel.signal]) {
                const color = this.channelColors[ch];
                const signal = this.signals[channel.signal];
                const type = channel.signal.startsWith('V_') ? 'V' : 'A';
                const scaleText = channel.scale >= 1
                    ? `${channel.scale} ${type}/div`
                    : `${channel.scale * 1000} m${type}/div`;

                items.push(`
                    <div class="legend-item">
                        <span class="legend-color" style="background: ${color}"></span>
                        <span>CH${ch}: ${channel.signal} (${scaleText})</span>
                    </div>
                `);
            }
        }

        this.legendElement.innerHTML = items.join('');
    }

    /**
     * Exporter le canvas en image PNG
     */
    exportPNG(filename = 'oscilloscope.png') {
        // Créer un canvas temporaire avec fond
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;

        tempCanvas.width = this.canvas.width;
        tempCanvas.height = this.canvas.height;

        // Fond avec la couleur du thème
        const bgColor = getComputedStyle(document.documentElement).getPropertyValue('--oscilloscope-bg').trim() || '#0d1117';
        tempCtx.fillStyle = bgColor;
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

        // Copier le contenu
        tempCtx.drawImage(this.canvas, 0, 0);

        // Ajouter un timestamp avec couleur du thème
        const colors = this.getThemeColors();
        tempCtx.scale(dpr, dpr);
        tempCtx.fillStyle = colors.border;
        tempCtx.font = '10px monospace';
        tempCtx.fillText(new Date().toLocaleString('fr-CA'), 10, this.height - 10);

        // Télécharger
        const link = document.createElement('a');
        link.download = filename;
        link.href = tempCanvas.toDataURL('image/png');
        link.click();
    }
}

// Export global
window.Oscilloscope = Oscilloscope;
