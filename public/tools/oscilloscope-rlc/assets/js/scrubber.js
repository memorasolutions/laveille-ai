/**
 * Scrubber.js - Number Scrubbing sur les labels
 * Permet de modifier les valeurs numériques par glissement horizontal sur le label
 * Inspiré de Photoshop, Figma, After Effects
 */

class NumberScrubber {
    constructor() {
        this.activeInput = null;
        this.startX = 0;
        this.startValue = 0;
        this.sensitivity = 1;
        this.isDragging = false;

        // Configuration par défaut des plages
        this.config = {
            voltage: { min: 0, max: 1000, step: 1, sensitivity: 0.5 },
            frequency: { min: 0, max: 10000, step: 1, sensitivity: 0.5 },
            resistance: { min: 0, max: 10000, step: 1, sensitivity: 0.5 },
            inductance: { min: 0, max: 10000, step: 1, sensitivity: 0.5 },
            capacitance: { min: 0, max: 10000, step: 1, sensitivity: 0.5 }
        };

        this.init();
    }

    init() {
        // Trouver tous les groupes d'input avec unité
        const inputGroups = document.querySelectorAll('.input-group:has(.input-with-unit)');

        inputGroups.forEach(group => {
            const input = group.querySelector('input[type="number"]');
            const label = group.querySelector('label');

            if (!input || !label) return;

            const inputId = input.id;
            const config = this.config[inputId] || { min: 0, max: 1000, step: 1, sensitivity: 0.5 };

            // Ajouter la classe scrubber au label
            label.classList.add('scrubber-label');
            label.dataset.inputId = inputId;
            label.title = 'Glissez horizontalement pour ajuster';

            // Événements de scrubbing sur le label
            label.addEventListener('mousedown', (e) => this.handleMouseDown(e, input, config));
            label.addEventListener('touchstart', (e) => this.handleTouchStart(e, input, config), { passive: false });
        });

        // Événements globaux pour le dragging
        document.addEventListener('mousemove', this.handleMouseMove.bind(this));
        document.addEventListener('mouseup', this.handleMouseUp.bind(this));
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this));
    }

    handleMouseDown(e, input, config) {
        if (e.button !== 0) return; // Seulement clic gauche

        e.preventDefault();
        this.startDrag(e.clientX, input, config);
    }

    handleTouchStart(e, input, config) {
        if (e.touches.length !== 1) return;

        e.preventDefault();
        this.startDrag(e.touches[0].clientX, input, config);
    }

    startDrag(clientX, input, config) {
        this.isDragging = true;
        this.activeInput = input;
        this.startX = clientX;
        this.startValue = parseFloat(input.value) || 0;
        this.sensitivity = config.sensitivity;
        this.config.active = config;

        // Ajouter classe active
        document.body.classList.add('scrubbing');
        input.closest('.input-group')?.classList.add('scrubbing-active');
    }

    handleMouseMove(e) {
        if (!this.isDragging || !this.activeInput) return;

        e.preventDefault();
        this.updateValue(e.clientX);
    }

    handleTouchMove(e) {
        if (!this.isDragging || !this.activeInput || e.touches.length !== 1) return;

        e.preventDefault();
        this.updateValue(e.touches[0].clientX);
    }

    updateValue(clientX) {
        const deltaX = clientX - this.startX;
        const config = this.config.active;

        // Calculer le changement de valeur
        let sensitivity = this.sensitivity;

        // Modificateurs clavier pour précision
        if (window.event?.shiftKey) {
            sensitivity *= 10; // Plus rapide
        } else if (window.event?.altKey) {
            sensitivity *= 0.1; // Plus précis
        }

        let newValue = this.startValue + (deltaX * sensitivity);

        // Appliquer les limites
        newValue = Math.max(config.min, Math.min(config.max, newValue));

        // Arrondir selon le step
        newValue = Math.round(newValue / config.step) * config.step;

        // Mettre à jour l'input
        this.activeInput.value = newValue;
        this.activeInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    handleMouseUp() {
        this.endDrag();
    }

    handleTouchEnd() {
        this.endDrag();
    }

    endDrag() {
        if (!this.isDragging) return;

        this.isDragging = false;
        document.body.classList.remove('scrubbing');
        this.activeInput?.closest('.input-group')?.classList.remove('scrubbing-active');
        this.activeInput = null;
    }
}

// Initialiser au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    window.numberScrubber = new NumberScrubber();
});

// Export pour utilisation externe
window.NumberScrubber = NumberScrubber;
