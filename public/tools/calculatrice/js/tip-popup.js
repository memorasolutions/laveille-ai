/**
 * 💡 TIP POPUP MANAGER
 * Gestionnaire simple pour popup de sélection de pourboire
 * Compatible avec calculator-simple.js
 */
(function() {
    'use strict';

    class TipPopupManager {
        constructor() {
            this.overlay = null;
            this.selectedTip = 0;
            this.callback = null;
            this.isOpen = false;
            
            this._createModal();
            this._setupEventListeners();
        }
        
        /**
         * Créer la structure HTML du modal
         */
        _createModal() {
            const overlay = document.createElement('div');
            overlay.className = 'tip-modal-overlay';
            overlay.innerHTML = `
                <div class="tip-modal" role="dialog" aria-modal="true" aria-labelledby="tip-modal-title">
                    <div class="tip-modal-header">
                        <h2 class="tip-modal-title" id="tip-modal-title">Choisir un pourboire</h2>
                        <button type="button" class="tip-modal-close" aria-label="Fermer">&times;</button>
                    </div>
                    
                    <div class="tip-modal-presets">
                        <button type="button" class="tip-modal-button" data-tip="10">10%</button>
                        <button type="button" class="tip-modal-button" data-tip="15">15%</button>
                        <button type="button" class="tip-modal-button" data-tip="18">18%</button>
                        <button type="button" class="tip-modal-button" data-tip="20">20%</button>
                    </div>
                    
                    <div class="tip-modal-custom">
                        <label for="tip-modal-custom-input">Montant personnalisé (%)</label>
                        <input 
                            type="number" 
                            id="tip-modal-custom-input" 
                            placeholder="Ex: 12" 
                            min="0" 
                            max="100"
                            step="0.1"
                        >
                    </div>
                    
                    <div class="tip-modal-actions">
                        <button type="button" class="tip-modal-btn tip-modal-btn-secondary" data-action="cancel">
                            Annuler
                        </button>
                        <button type="button" class="tip-modal-btn tip-modal-btn-primary" data-action="confirm">
                            Appliquer le pourboire
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
            this.overlay = overlay;
        }
        
        /**
         * Configuration des événements
         */
        _setupEventListeners() {
            // Fermer avec overlay
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this._close();
                }
            });
            
            // Fermer avec bouton X
            const closeBtn = this.overlay.querySelector('.tip-modal-close');
            closeBtn.addEventListener('click', () => this._close());
            
            // Sélection des pourcentages prédéfinis
            const presetButtons = this.overlay.querySelectorAll('.tip-modal-button');
            presetButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tip = parseInt(button.dataset.tip);
                    this._selectTip(tip);
                    this._updatePresetStates(tip);
                    this._clearCustomInput();
                });
            });
            
            // Input personnalisé
            const customInput = this.overlay.querySelector('#tip-modal-custom-input');
            customInput.addEventListener('input', (e) => {
                const tip = parseFloat(e.target.value) || 0;
                this._selectTip(tip);
                this._clearPresetStates();
            });
            
            // Actions
            const cancelBtn = this.overlay.querySelector('[data-action="cancel"]');
            const confirmBtn = this.overlay.querySelector('[data-action="confirm"]');
            
            cancelBtn.addEventListener('click', () => this._close());
            confirmBtn.addEventListener('click', () => this._confirm());
            
            // Fermer avec Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this._close();
                }
            });
            
            // Focus trap basique
            const modal = this.overlay.querySelector('.tip-modal');
            modal.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    this._handleTabKey(e);
                }
            });
        }
        
        /**
         * Ouvrir le popup
         */
        open(callback, currentTip = 0) {
            this.callback = callback;
            this.selectedTip = currentTip;
            this.isOpen = true;
            
            // Réinitialiser l'état
            this._updatePresetStates(currentTip);
            this._updateCustomInput(currentTip);
            
            // Afficher le modal
            this.overlay.classList.add('show');
            
            // Focus sur le premier élément
            const firstButton = this.overlay.querySelector('.tip-modal-button');
            if (firstButton) {
                firstButton.focus();
            }
            
            console.log('💡 Tip popup opened');
        }
        
        /**
         * Fermer le popup
         */
        _close() {
            this.isOpen = false;
            this.overlay.classList.remove('show');
            this.callback = null;
            console.log('💡 Tip popup closed');
        }
        
        /**
         * Confirmer la sélection
         */
        _confirm() {
            if (this.callback && typeof this.callback === 'function') {
                this.callback(this.selectedTip);
            }
            this._close();
        }
        
        /**
         * Sélectionner un pourboire
         */
        _selectTip(tip) {
            this.selectedTip = Math.max(0, Math.min(100, tip));
        }
        
        /**
         * Mettre à jour l'état des boutons prédéfinis
         */
        _updatePresetStates(activeTip) {
            const buttons = this.overlay.querySelectorAll('.tip-modal-button');
            buttons.forEach(button => {
                const tip = parseInt(button.dataset.tip);
                button.classList.toggle('selected', tip === activeTip);
            });
        }
        
        /**
         * Vider la sélection des boutons prédéfinis
         */
        _clearPresetStates() {
            const buttons = this.overlay.querySelectorAll('.tip-modal-button');
            buttons.forEach(button => {
                button.classList.remove('selected');
            });
        }
        
        /**
         * Mettre à jour l'input personnalisé
         */
        _updateCustomInput(tip) {
            const input = this.overlay.querySelector('#tip-modal-custom-input');
            const presetTips = [10, 15, 18, 20];
            
            if (tip > 0 && !presetTips.includes(tip)) {
                input.value = tip;
            } else {
                input.value = '';
            }
        }
        
        /**
         * Vider l'input personnalisé
         */
        _clearCustomInput() {
            const input = this.overlay.querySelector('#tip-modal-custom-input');
            input.value = '';
        }
        
        /**
         * Gestion simple du focus trap
         */
        _handleTabKey(e) {
            const modal = this.overlay.querySelector('.tip-modal');
            const focusableElements = modal.querySelectorAll(
                'button, input, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    }

    // Exposer globalement pour intégration
    window.TipPopupManager = TipPopupManager;
    
    console.log('💡 TipPopupManager loaded');
})();