/**
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Minuteur visuel — logique Alpine.js pure (aucune dépendance externe).
 * Décompte précis via deadline absolue (performance.now()) + requestAnimationFrame,
 * pas un setInterval décrémenté naïvement (dérive dans le temps).
 *
 * Styles de rendu (5) : disque TimeTimer, sablier, anneau, chiffres (flip), feu de circulation.
 * Palette de couleur curatée (5 tons, WCAG AAA ≥ 7:1 sur fond blanc), persistée localStorage.
 */
(function () {
    'use strict';

    // Palette curatée — tons validés WCAG AAA (≥ 7:1) contre fond blanc du cadran.
    var PALETTE = {
        red: { label: 'Rouge classique', hex: '#991B1B' },
        teal: { label: 'Teal', hex: '#064E5A' },
        orange: { label: 'Orange', hex: '#9A2A06' },
        violet: { label: 'Violet', hex: '#6B21A8' },
        blue: { label: 'Bleu', hex: '#1E40AF' }
    };

    // Presets nommés — clé -> minutes (+ phase Pomodoro optionnelle).
    var PRESETS = {
        'p5': { minutes: 5 },
        'p10': { minutes: 10 },
        'p15': { minutes: 15 },
        'p25': { minutes: 25 },
        'pomodoro-focus': { minutes: 25, phase: 'focus' },
        'pomodoro-break': { minutes: 5, phase: 'break' }
    };

    // Styles supportant la palette de couleur curatée (disque + anneau + chiffres, #759-764 —
    // le feu de circulation garde son code sémantique vert/jaune/rouge, non personnalisable).
    var COLORABLE_STYLES = ['disk', 'ring', 'flip'];

    var RING_RADIUS = 90;
    var RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS; // ≈ 565.4867

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function pad2(n) {
        return (n < 10 ? '0' : '') + n;
    }

    // --- Contraste WCAG 2.2 (#740/#741) : la couleur accent peut être une des 5 teintes
    // curées OU une couleur personnalisée choisie par l'utilisateur — dans les deux cas,
    // il faut choisir AUTOMATIQUEMENT la meilleure couleur de texte (jamais blanc à
    // l'aveugle) : si la couleur de base est pâle, blanc échouerait le contraste.
    function hexToRgb(hex) {
        var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex || '');
        if (!m) return { r: 0, g: 0, b: 0 };
        return { r: parseInt(m[1], 16), g: parseInt(m[2], 16), b: parseInt(m[3], 16) };
    }

    function relativeLuminance(hex) {
        var rgb = hexToRgb(hex);
        var chans = [rgb.r, rgb.g, rgb.b].map(function (c) {
            var s = c / 255;
            return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * chans[0] + 0.7152 * chans[1] + 0.0722 * chans[2];
    }

    function contrastRatio(hexA, hexB) {
        var lA = relativeLuminance(hexA) + 0.05;
        var lB = relativeLuminance(hexB) + 0.05;
        return lA > lB ? lA / lB : lB / lA;
    }

    // Choisit, parmi les candidats, celui qui offre le MEILLEUR contraste contre bgHex —
    // garantit le meilleur résultat possible (jamais un simple "blanc par défaut") pour
    // n'importe quelle couleur (curatée ou personnalisée), de façon entièrement automatique.
    function bestTextColorOn(bgHex, candidates) {
        candidates = candidates || ['#FFFFFF', '#1A1D23'];
        var best = candidates[0], bestRatio = 0;
        for (var i = 0; i < candidates.length; i++) {
            var ratio = contrastRatio(bgHex, candidates[i]);
            if (ratio > bestRatio) { bestRatio = ratio; best = candidates[i]; }
        }
        return best;
    }

    function isValidHexColor(hex) {
        return /^#[a-f\d]{6}$/i.test(hex || '');
    }

    // Secteur (pie slice) partagé par diskPathD (coordonnées SVG 0-200) et
    // diskPathDNormalized (coordonnées 0-1, objectBoundingBox — #740) : même géométrie,
    // juste une échelle différente, pour éviter de dupliquer le calcul trigonométrique.
    function buildPieSlicePath(cx, cy, r, angleDeg, decimals) {
        if (angleDeg <= 0.01) return '';
        var rad = angleDeg * Math.PI / 180;
        var x2 = cx + r * Math.sin(rad);
        var y2 = cy - r * Math.cos(rad);
        var largeArc = angleDeg > 180 ? 1 : 0;
        return 'M ' + cx + ' ' + cy + ' L ' + cx + ' ' + (cy - r) +
            ' A ' + r + ' ' + r + ' 0 ' + largeArc + ' 1 ' + x2.toFixed(decimals) + ' ' + y2.toFixed(decimals) + ' Z';
    }

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('minuteurVisuel', function (isAuthenticated) {
            return {
                // --- État persisté (localStorage) ---
                style: localStorage.getItem('mv_style') || 'disk',
                // Couleurs personnalisées récentes (#744-750) : synchronisées côté serveur
                // uniquement si connecté (users.tool_preferences) — incite à la connexion pour
                // les invités plutôt que de dupliquer un historique local non synchronisé.
                isAuthenticated: !!isAuthenticated,
                recentCustomColors: [],
                // #751-758 : jusqu'à 2 durées personnalisées épinglées (connectés) - bascule
                // étoile (pas un historique roulant comme les couleurs) : le rôle d'un "favori"
                // qu'on épingle est de rester tel quel jusqu'à ce qu'on le retire explicitement.
                customDurations: [],
                accentColor: localStorage.getItem('mv_color') || 'red',
                // #742 : couleur personnalisée, en plus des 5 teintes curées — accentColor
                // devient 'custom' quand active, la vraie valeur vit ici (validée hex strict).
                customColorHex: (function () {
                    var v = localStorage.getItem('mv_color_custom');
                    return isValidHexColor(v) ? v : '#4B5563';
                })(),
                soundEnabled: localStorage.getItem('mv_sound') !== 'false',
                reducedMotion: localStorage.getItem('mv_reduced_motion') !== null
                    ? localStorage.getItem('mv_reduced_motion') === 'true'
                    : (window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)').matches : false),
                warningThresholdSec: parseInt(localStorage.getItem('mv_warning_threshold') || '60', 10),
                // Durées Pomodoro configurables (défaut 25/5 classique) — pilotent les presets
                // 'pomodoro-focus'/'pomodoro-break' de PRESETS à la place des minutes fixes.
                pomodoroFocusMin: clamp(parseInt(localStorage.getItem('mv_pomodoro_focus'), 10) || 25, 1, 120),
                pomodoroBreakMin: clamp(parseInt(localStorage.getItem('mv_pomodoro_break'), 10) || 5, 1, 30),

                // --- État du décompte ---
                totalSeconds: 5 * 60,
                remainingMs: 5 * 60 * 1000,
                state: 'idle', // idle | running | paused | finished
                deadline: 0,
                rafId: null,
                pomodoroPhase: null,

                // --- ARIA sobre ---
                ariaMessage: '',
                lastAnnouncedMinute: null,
                warningAnnounced: false,

                // --- Divers UI ---
                flipPulse: false,
                _lastFlipSecond: null,
                shareCopied: false,

                palette: PALETTE,
                presets: PRESETS,
                customMinutes: 20,

                init: function () {
                    var self = this;
                    self.remainingMs = self.totalSeconds * 1000;
                    self._applyUrlParams();
                    if (self.isAuthenticated) self._loadToolPreferences();

                    document.addEventListener('visibilitychange', function () {
                        if (!document.hidden && self.state === 'running') {
                            self._syncFromDeadline();
                        }
                    });

                    this.$watch('style', function (value) {
                        try { localStorage.setItem('mv_style', value); } catch (e) {}
                        self._syncUrl();
                    });
                    this.$watch('totalSeconds', function () {
                        self._syncUrl();
                    });
                },

                _applyUrlParams: function () {
                    try {
                        var params = new URLSearchParams(window.location.search);
                        var minutes = parseInt(params.get('minutes'), 10);
                        var styleParam = params.get('style');
                        if (styleParam && ['disk', 'hourglass', 'ring', 'flip', 'traffic'].indexOf(styleParam) !== -1) {
                            this.style = styleParam;
                        }
                        if (!isNaN(minutes) && minutes > 0 && minutes <= 180) {
                            this.totalSeconds = minutes * 60;
                            this.remainingMs = this.totalSeconds * 1000;
                        }
                    } catch (e) {}
                },

                _syncUrl: function () {
                    try {
                        var minutes = Math.max(1, Math.round(this.totalSeconds / 60));
                        var params = new URLSearchParams(window.location.search);
                        params.set('minutes', String(minutes));
                        params.set('style', this.style);
                        var newUrl = window.location.pathname + '?' + params.toString();
                        window.history.replaceState({}, '', newUrl);
                    } catch (e) {}
                },

                // --- Getters dérivés ---
                get fraction() {
                    return this.totalSeconds > 0 ? clamp(this.remainingMs / (this.totalSeconds * 1000), 0, 1) : 0;
                },
                get percentRemaining() {
                    return this.fraction * 100;
                },
                get display() {
                    var secondsLeft = Math.ceil(this.remainingMs / 1000);
                    var m = Math.floor(secondsLeft / 60);
                    var s = secondsLeft % 60;
                    return pad2(m) + ':' + pad2(s);
                },
                get isWarning() {
                    return this.state === 'running'
                        && this.remainingMs > 0
                        && Math.ceil(this.remainingMs / 1000) <= this.warningThresholdSec;
                },
                get accentHex() {
                    if (this.accentColor === 'custom' && isValidHexColor(this.customColorHex)) {
                        return this.customColorHex;
                    }
                    var entry = this.palette[this.accentColor] || this.palette.red;
                    return entry.hex;
                },
                get dialColorHex() {
                    return this.isWarning ? '#DC2626' : this.accentHex;
                },
                // #740/#741 : couleur du calque de texte superposé (celui qui se révèle sur
                // le secteur coloré du disque) — blanc par défaut, mais choisi AUTOMATIQUEMENT
                // (meilleur contraste réel) pour toute couleur pâle, curatée ou personnalisée.
                get diskAccentTextColor() {
                    return bestTextColorOn(this.dialColorHex);
                },
                // #759-764 : le style Chiffres a un fond plein (pas un secteur partiel comme le
                // disque) — un seul calque suffit, même fonction de contraste automatique.
                get flipTextColor() {
                    return bestTextColorOn(this.dialColorHex);
                },
                // #742 : le "+" du swatch personnalisé doit rester lisible peu importe la
                // dernière couleur personnalisée mémorisée (même logique de contraste auto).
                get customSwatchTextColor() {
                    return bestTextColorOn(this.customColorHex);
                },
                get supportsColorPalette() {
                    return COLORABLE_STYLES.indexOf(this.style) !== -1;
                },
                // #751-758 : l'étoile d'épinglage est désactivée si la durée courante est déjà
                // invalide, OU si les 2 emplacements sont pleins ET que cette durée n'y figure pas
                // déjà (dans ce cas précis, la bascule doit rester possible pour la DÉSépingler).
                get isCurrentDurationPinned() {
                    var minutes = parseInt(this.customMinutes, 10);
                    return this.customDurations.indexOf(minutes) !== -1;
                },
                get isCustomMinutesPinnable() {
                    var minutes = parseInt(this.customMinutes, 10);
                    if (!minutes || minutes < 1 || minutes > 180) return false;
                    return this.isCurrentDurationPinned || this.customDurations.length < 2;
                },

                // Anneau : cercle stroke-dasharray/offset.
                get ringCircumference() {
                    return RING_CIRCUMFERENCE.toFixed(2);
                },
                get ringOffset() {
                    return (RING_CIRCUMFERENCE * (1 - this.fraction)).toFixed(2);
                },

                // Disque TimeTimer : secteur (pie slice) qui rétrécit depuis midi, sens horaire.
                // Clamp à 359.9° (pas 359.999) : au-delà, l'arrondi .toFixed() fait coïncider
                // le point de départ et d'arrivée de l'arc SVG (tous deux ~100,10), ce qui
                // dégénère en tracé quasi invisible au lieu du disque plein attendu.
                get diskPathD() {
                    var angleDeg = clamp(this.fraction * 360, 0, 359.9);
                    return buildPieSlicePath(100, 100, 90, angleDeg, 2);
                },
                // #740 : même secteur, coordonnées normalisées 0-1 (objectBoundingBox) — sert
                // de clip-path pour le calque de texte HTML au-dessus du SVG (pas les mêmes
                // unités que diskPathD, un clip-path CSS classique interprète les coordonnées
                // en pixels réels du div, PAS relatif à un viewBox : objectBoundingBox est le
                // seul mécanisme natif qui recale automatiquement sur la taille réelle du cadran).
                get diskPathDNormalized() {
                    var angleDeg = clamp(this.fraction * 360, 0, 359.9);
                    return buildPieSlicePath(0.5, 0.5, 0.45, angleDeg, 4);
                },

                // Sablier : hauteur de sable (haut qui se vide, bas qui se remplit) — approximation esthétique.
                get topSandY() {
                    return (130 - this.fraction * 110).toFixed(2);
                },
                get topSandHeight() {
                    return (this.fraction * 110 + 12).toFixed(2);
                },
                get bottomSandY() {
                    return (240 - (1 - this.fraction) * 110).toFixed(2);
                },
                get bottomSandHeight() {
                    return ((1 - this.fraction) * 110 + 12).toFixed(2);
                },

                // Feu de circulation : phase par seuils fixes (V1).
                get trafficPhase() {
                    var p = this.percentRemaining;
                    if (p > 50) return 'green';
                    if (p > 20) return 'yellow';
                    return 'red';
                },

                // Seuils de la légende du feu de circulation — dérivés de totalSeconds (mêmes
                // seuils que trafficPhase : 50 % et 20 %), reformatés mm:ss. Réactifs : se
                // recalculent automatiquement si l'utilisateur change de durée (preset, ±1,
                // durée personnalisée), puisque totalSeconds est lu à chaque évaluation.
                get trafficTotalFormatted() {
                    var s = Math.round(this.totalSeconds);
                    return pad2(Math.floor(s / 60)) + ':' + pad2(s % 60);
                },
                get trafficGreenThreshold() {
                    var s = Math.round(this.totalSeconds * 0.5);
                    return pad2(Math.floor(s / 60)) + ':' + pad2(s % 60);
                },
                get trafficYellowThreshold() {
                    var s = Math.round(this.totalSeconds * 0.2);
                    return pad2(Math.floor(s / 60)) + ':' + pad2(s % 60);
                },

                // --- Contrôle du décompte ---
                applyPreset: function (key) {
                    if (this.state === 'running') return;
                    var preset = this.presets[key];
                    if (!preset) return;
                    // Pomodoro : minutes configurables (Réglages) plutôt que la valeur fixe de PRESETS.
                    var minutes = preset.minutes;
                    if (key === 'pomodoro-focus') minutes = this.pomodoroFocusMin;
                    else if (key === 'pomodoro-break') minutes = this.pomodoroBreakMin;
                    this.totalSeconds = minutes * 60;
                    this.remainingMs = this.totalSeconds * 1000;
                    this.pomodoroPhase = preset.phase || null;
                    this.state = 'idle';
                    this._resetAnnounceFlags();
                    this.start();
                },

                applyCustomMinutes: function () {
                    if (this.state === 'running') return;
                    var raw = parseInt(this.customMinutes, 10);
                    if (!raw || raw < 1) raw = 1;
                    if (raw > 180) raw = 180;
                    this.customMinutes = raw;
                    this.totalSeconds = raw * 60;
                    this.remainingMs = this.totalSeconds * 1000;
                    this.pomodoroPhase = null;
                    this.state = 'idle';
                    this._resetAnnounceFlags();
                    this.start();
                },

                toggleStartPause: function () {
                    if (this.state === 'idle') {
                        this.start();
                    } else if (this.state === 'running') {
                        this.pause();
                    } else if (this.state === 'paused') {
                        this.resume();
                    } else if (this.state === 'finished') {
                        this.reset();
                        this.start();
                    }
                },

                handleSpaceKey: function (event) {
                    var target = event.target;
                    var tag = (target && target.tagName ? target.tagName : '').toLowerCase();
                    var isEditable = !!(target && (target.isContentEditable || ['input', 'textarea', 'select'].indexOf(tag) !== -1));
                    if (isEditable) return;
                    event.preventDefault();
                    this.toggleStartPause();
                },

                start: function () {
                    if (this.remainingMs <= 0) {
                        this.remainingMs = this.totalSeconds * 1000;
                    }
                    this.deadline = performance.now() + this.remainingMs;
                    this.state = 'running';
                    this._resetAnnounceFlags();
                    this._loop();
                },

                pause: function () {
                    if (this.rafId) {
                        cancelAnimationFrame(this.rafId);
                        this.rafId = null;
                    }
                    this.state = 'paused';
                },

                resume: function () {
                    this.deadline = performance.now() + this.remainingMs;
                    this.state = 'running';
                    this._loop();
                },

                reset: function () {
                    if (this.rafId) {
                        cancelAnimationFrame(this.rafId);
                        this.rafId = null;
                    }
                    this.state = 'idle';
                    this.remainingMs = this.totalSeconds * 1000;
                    this._resetAnnounceFlags();
                },

                adjustMinutes: function (deltaMinutes) {
                    var deltaMs = deltaMinutes * 60 * 1000;
                    this.remainingMs = Math.max(0, this.remainingMs + deltaMs);
                    if (this.remainingMs > this.totalSeconds * 1000) {
                        this.totalSeconds = Math.round(this.remainingMs / 1000);
                    }
                    if (this.state === 'running') {
                        this.deadline = performance.now() + this.remainingMs;
                    }
                    if (this.state === 'finished' && this.remainingMs > 0) {
                        this.state = 'paused';
                    }
                },

                setStyle: function (key) {
                    this.style = key;
                },

                setColor: function (key) {
                    if (!this.palette[key]) return;
                    this.accentColor = key;
                    try { localStorage.setItem('mv_color', key); } catch (e) {}
                },

                // #742 : couleur personnalisée — valeur venant d'un <input type="color">
                // (toujours un hex valide par construction du navigateur), persistée à part
                // de la clé de palette curatée. Le contraste du texte se recalcule tout seul
                // via diskAccentTextColor, aucune action supplémentaire requise ici.
                setCustomColor: function (hex) {
                    if (!isValidHexColor(hex)) return;
                    this.customColorHex = hex;
                    this.accentColor = 'custom';
                    try {
                        localStorage.setItem('mv_color_custom', hex);
                        localStorage.setItem('mv_color', 'custom');
                    } catch (e) {}
                },

                // Appelé sur @change (une fois le choix finalisé), pas @input (qui déclenche en
                // continu pendant le glissement dans le sélecteur natif) - évite de spammer le
                // serveur pendant qu'on fait glisser le curseur de teinte.
                persistCustomColorHistory: function (hex) {
                    if (!isValidHexColor(hex) || !this.isAuthenticated) return;
                    var idx = this.recentCustomColors.findIndex(function (c) { return c.toLowerCase() === hex.toLowerCase(); });
                    if (idx !== -1) this.recentCustomColors.splice(idx, 1);
                    this.recentCustomColors.unshift(hex);
                    this.recentCustomColors = this.recentCustomColors.slice(0, 5);
                    this._saveRecentColors();
                },

                _headers: function () {
                    return {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
                    };
                },

                // Un seul GET pour toutes les préférences serveur de cet outil (couleurs récentes
                // + durées épinglées) - évite de multiplier les appels réseau au chargement.
                _loadToolPreferences: function () {
                    var self = this;
                    fetch('/api/tool-preferences/minuteur-visuel', { headers: self._headers() })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (data) {
                            if (!data || !data.preferences) return;
                            if (Array.isArray(data.preferences.custom_colors)) {
                                self.recentCustomColors = data.preferences.custom_colors;
                            }
                            if (Array.isArray(data.preferences.custom_durations)) {
                                self.customDurations = data.preferences.custom_durations;
                            }
                        })
                        .catch(function () {});
                },

                _saveRecentColors: function () {
                    if (!this.isAuthenticated) return;
                    fetch('/api/tool-preferences/minuteur-visuel', {
                        method: 'POST',
                        headers: this._headers(),
                        body: JSON.stringify({ key: 'custom_colors', value: this.recentCustomColors })
                    }).catch(function () {});
                },

                _saveCustomDurations: function () {
                    if (!this.isAuthenticated) return;
                    fetch('/api/tool-preferences/minuteur-visuel', {
                        method: 'POST',
                        headers: this._headers(),
                        body: JSON.stringify({ key: 'custom_durations', value: this.customDurations })
                    }).catch(function () {});
                },

                // #751-758 : bascule étoile - épingle la durée COURANTE (customMinutes) si elle
                // n'est pas déjà épinglée et qu'il reste une place (max 2) ; la retire si elle
                // l'est déjà (symétrique, comportement de bascule attendu d'une icône étoile).
                pinCurrentDuration: function () {
                    if (!this.isAuthenticated) return;
                    var minutes = parseInt(this.customMinutes, 10);
                    if (!minutes || minutes < 1 || minutes > 180) return;
                    if (this.customDurations.indexOf(minutes) !== -1) {
                        this.removeCustomDuration(minutes);
                        return;
                    }
                    if (this.customDurations.length >= 2) return;
                    this.customDurations.push(minutes);
                    this._saveCustomDurations();
                },

                removeCustomDuration: function (minutes) {
                    var idx = this.customDurations.indexOf(minutes);
                    if (idx === -1) return;
                    this.customDurations.splice(idx, 1);
                    this._saveCustomDurations();
                },

                applyCustomDuration: function (minutes) {
                    if (this.state === 'running') return;
                    this.totalSeconds = minutes * 60;
                    this.remainingMs = this.totalSeconds * 1000;
                    this.pomodoroPhase = null;
                    this.state = 'idle';
                    this._resetAnnounceFlags();
                    this.start();
                },

                // Persistance localStorage seulement — x-model a DÉJÀ mis à jour la propriété
                // réactive au moment où ce handler @change se déclenche (le double-toggle
                // this.x = !this.x annulait le clic de l'utilisateur : la case se recochait
                // visuellement à l'état précédent, jamais réellement "cochable").
                toggleSound: function () {
                    try { localStorage.setItem('mv_sound', String(this.soundEnabled)); } catch (e) {}
                },

                toggleReducedMotion: function () {
                    try { localStorage.setItem('mv_reduced_motion', String(this.reducedMotion)); } catch (e) {}
                },

                setWarningThreshold: function (seconds) {
                    var value = parseInt(seconds, 10);
                    if (isNaN(value) || value < 0) value = 60;
                    this.warningThresholdSec = value;
                    try { localStorage.setItem('mv_warning_threshold', String(value)); } catch (e) {}
                },

                setPomodoroFocus: function (minutes) {
                    var value = clamp(parseInt(minutes, 10) || 25, 1, 120);
                    this.pomodoroFocusMin = value;
                    try { localStorage.setItem('mv_pomodoro_focus', String(value)); } catch (e) {}
                },

                setPomodoroBreak: function (minutes) {
                    var value = clamp(parseInt(minutes, 10) || 5, 1, 30);
                    this.pomodoroBreakMin = value;
                    try { localStorage.setItem('mv_pomodoro_break', String(value)); } catch (e) {}
                },

                shareCurrentUrl: function () {
                    var self = this;
                    this._syncUrl();
                    try {
                        navigator.clipboard.writeText(window.location.href).then(function () {
                            self.shareCopied = true;
                            setTimeout(function () { self.shareCopied = false; }, 2500);
                        });
                    } catch (e) {}
                },

                // --- Boucle rAF (précision via deadline absolue) ---
                _loop: function () {
                    var self = this;
                    var step = function () {
                        if (self.state !== 'running') return;
                        self._syncFromDeadline();
                        if (self.state === 'running') {
                            self.rafId = requestAnimationFrame(step);
                        }
                    };
                    this.rafId = requestAnimationFrame(step);
                },

                _syncFromDeadline: function () {
                    var now = performance.now();
                    this.remainingMs = Math.max(0, this.deadline - now);
                    this._checkAnnouncements();
                    if (this.remainingMs <= 0) {
                        this.state = 'finished';
                        if (this.rafId) { cancelAnimationFrame(this.rafId); this.rafId = null; }
                        this._onFinished();
                    } else {
                        this._maybeFlipPulse();
                    }
                },

                _resetAnnounceFlags: function () {
                    this.lastAnnouncedMinute = null;
                    this.warningAnnounced = false;
                    this.ariaMessage = '';
                    this._lastFlipSecond = null;
                },

                // Annonces ARIA sobres : uniquement sur changement de minute entière + un avertissement
                // unique + une fin unique — jamais à chaque seconde.
                _checkAnnouncements: function () {
                    var secondsLeft = Math.ceil(this.remainingMs / 1000);
                    var wholeMinutes = Math.ceil(secondsLeft / 60);

                    if (secondsLeft > 0 && wholeMinutes !== this.lastAnnouncedMinute) {
                        this.lastAnnouncedMinute = wholeMinutes;
                        this.ariaMessage = wholeMinutes > 1
                            ? wholeMinutes + ' minutes restantes'
                            : 'Moins d\'une minute restante';
                    }

                    if (!this.warningAnnounced && secondsLeft > 0 && secondsLeft <= this.warningThresholdSec) {
                        this.warningAnnounced = true;
                        this.ariaMessage = 'Bientôt fini : ' + this.display + ' restant';
                        this.playWarningSound();
                    }
                },

                _maybeFlipPulse: function () {
                    if (this.reducedMotion) return;
                    var secondsLeft = Math.ceil(this.remainingMs / 1000);
                    if (secondsLeft !== this._lastFlipSecond) {
                        this._lastFlipSecond = secondsLeft;
                        this.flipPulse = true;
                        var self = this;
                        setTimeout(function () { self.flipPulse = false; }, 180);
                    }
                },

                _onFinished: function () {
                    this.ariaMessage = 'Minuteur terminé !';
                    this.playFinishSound();
                },

                // --- Son (Web Audio API, oscillateur généré — pattern tirage-presentations::playBeep) ---
                playBeep: function (frequency, duration) {
                    if (!this.soundEnabled) return;
                    try {
                        var ctx = new (window.AudioContext || window.webkitAudioContext)();
                        var osc = ctx.createOscillator();
                        var gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.type = 'sine';
                        osc.frequency.value = frequency;
                        gain.gain.value = 0.15;
                        osc.start();
                        osc.stop(ctx.currentTime + (duration / 1000));
                    } catch (e) {}
                },
                playWarningSound: function () {
                    var self = this;
                    self.playBeep(880, 120);
                    setTimeout(function () { self.playBeep(880, 120); }, 220);
                },
                playFinishSound: function () {
                    var self = this;
                    self.playBeep(660, 150);
                    setTimeout(function () { self.playBeep(880, 150); }, 250);
                    setTimeout(function () { self.playBeep(1100, 300); }, 500);
                }
            };
        });
    });
})();
