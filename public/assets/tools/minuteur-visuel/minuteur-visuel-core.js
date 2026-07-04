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
        'p45': { minutes: 45 },
        'pomodoro-focus': { minutes: 25, phase: 'focus' },
        'pomodoro-break': { minutes: 5, phase: 'break' }
    };

    // Styles supportant la palette de couleur curatée (disque + anneau — voir écart documenté :
    // le feu de circulation garde son code sémantique vert/jaune/rouge, non personnalisable).
    var COLORABLE_STYLES = ['disk', 'ring'];

    var RING_RADIUS = 90;
    var RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS; // ≈ 565.4867

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function pad2(n) {
        return (n < 10 ? '0' : '') + n;
    }

    document.addEventListener('alpine:init', function () {
        window.Alpine.data('minuteurVisuel', function () {
            return {
                // --- État persisté (localStorage) ---
                style: localStorage.getItem('mv_style') || 'disk',
                accentColor: localStorage.getItem('mv_color') || 'red',
                soundEnabled: localStorage.getItem('mv_sound') !== 'false',
                reducedMotion: localStorage.getItem('mv_reduced_motion') !== null
                    ? localStorage.getItem('mv_reduced_motion') === 'true'
                    : (window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)').matches : false),
                warningThresholdSec: parseInt(localStorage.getItem('mv_warning_threshold') || '60', 10),

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
                    var entry = this.palette[this.accentColor] || this.palette.red;
                    return entry.hex;
                },
                get dialColorHex() {
                    return this.isWarning ? '#DC2626' : this.accentHex;
                },
                get supportsColorPalette() {
                    return COLORABLE_STYLES.indexOf(this.style) !== -1;
                },

                // Anneau : cercle stroke-dasharray/offset.
                get ringCircumference() {
                    return RING_CIRCUMFERENCE.toFixed(2);
                },
                get ringOffset() {
                    return (RING_CIRCUMFERENCE * (1 - this.fraction)).toFixed(2);
                },

                // Disque TimeTimer : secteur (pie slice) qui rétrécit depuis midi, sens horaire.
                get diskPathD() {
                    var cx = 100, cy = 100, r = 90;
                    // Clamp à 359.9° (pas 359.999) : au-delà, l'arrondi .toFixed(2) fait
                    // coïncider le point de départ et d'arrivée de l'arc SVG (tous deux ~100,10),
                    // ce qui dégénère en tracé quasi invisible au lieu du disque plein attendu.
                    var angleDeg = clamp(this.fraction * 360, 0, 359.9);
                    var rad = angleDeg * Math.PI / 180;
                    var x2 = cx + r * Math.sin(rad);
                    var y2 = cy - r * Math.cos(rad);
                    var largeArc = angleDeg > 180 ? 1 : 0;
                    if (angleDeg <= 0.01) {
                        return '';
                    }
                    return 'M ' + cx + ' ' + cy + ' L ' + cx + ' ' + (cy - r) +
                        ' A ' + r + ' ' + r + ' 0 ' + largeArc + ' 1 ' + x2.toFixed(2) + ' ' + y2.toFixed(2) + ' Z';
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

                // --- Contrôle du décompte ---
                applyPreset: function (key) {
                    if (this.state === 'running') return;
                    var preset = this.presets[key];
                    if (!preset) return;
                    this.totalSeconds = preset.minutes * 60;
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
