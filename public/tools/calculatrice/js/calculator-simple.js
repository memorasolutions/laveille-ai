/**
 * 🧮 CALCULATRICE TAXES CANADA - VERSION SIMPLIFIÉE 2025
 * 
 * ARCHITECTURE: Un seul fichier, patterns modernes, performance optimale
 * OBJECTIF: Corriger DÉFINITIVEMENT les 5 bugs persistants
 * 
 * PATTERNS UTILISÉS:
 * - Proxy pour réactivité automatique
 * - Module Revealing avec IIFE
 * - Observer pattern pour UI updates
 * - State machine simple
 * - Functional programming pour calculs
 */
(function() {
    'use strict';

    // ===============================================
    // 🏗️ CONFIGURATION ET CONSTANTES
    // ===============================================
    
    const TAX_RATES = {
        'AB': { name: 'Alberta', gst: 5.0, pst: 0, total: 5.0 },
        'BC': { name: 'Colombie-Britannique', gst: 5.0, pst: 7.0, total: 12.0 },
        'MB': { name: 'Manitoba', gst: 5.0, pst: 7.0, total: 12.0 },
        'NB': { name: 'Nouveau-Brunswick', hst: 15.0, total: 15.0 },
        'NL': { name: 'Terre-Neuve-et-Labrador', hst: 15.0, total: 15.0 },
        'NS': { name: 'Nouvelle-Écosse', hst: 14.0, total: 14.0 },
        'NT': { name: 'Territoires du Nord-Ouest', gst: 5.0, pst: 0, total: 5.0 },
        'NU': { name: 'Nunavut', gst: 5.0, pst: 0, total: 5.0 },
        'ON': { name: 'Ontario', hst: 13.0, total: 13.0 },
        'PE': { name: 'Île-du-Prince-Édouard', hst: 15.0, total: 15.0 },
        'QC': { name: 'Québec', gst: 5.0, qst: 9.975, total: 14.975 },
        'SK': { name: 'Saskatchewan', gst: 5.0, pst: 6.0, total: 11.0 },
        'YT': { name: 'Yukon', gst: 5.0, pst: 0, total: 5.0 }
    };

    const TIP_PRESETS = [10, 15, 18, 20];
    const MAX_PEOPLE = 20;

    // ===============================================
    // 🧠 GESTIONNAIRE D'ÉTAT RÉACTIF
    // ===============================================
    
    class ReactiveState {
        constructor(initialState = {}) {
            this._state = initialState;
            this._observers = new Map();
            this._history = [];
            this._maxHistory = 10;
            
            // Proxy pour détecter automatiquement les changements
            return new Proxy(this, {
                set(target, property, value) {
                    if (property.startsWith('_')) {
                        // Propriétés privées
                        target[property] = value;
                        return true;
                    }
                    
                    const oldValue = target._state[property];
                    if (oldValue !== value) {
                        // Sauvegarder l'historique
                        target._saveToHistory();
                        
                        // Mettre à jour l'état
                        target._state[property] = value;
                        
                        // Notifier les observateurs
                        target._notify(property, value, oldValue);
                    }
                    return true;
                },
                
                get(target, property) {
                    if (property.startsWith('_') || typeof target[property] === 'function') {
                        return target[property];
                    }
                    return target._state[property];
                }
            });
        }
        
        observe(property, callback) {
            if (!this._observers.has(property)) {
                this._observers.set(property, []);
            }
            this._observers.get(property).push(callback);
        }
        
        _notify(property, newValue, oldValue) {
            const observers = this._observers.get(property);
            if (observers) {
                observers.forEach(callback => callback(newValue, oldValue));
            }
            
            // Observer global pour tous les changements
            const globalObservers = this._observers.get('*');
            if (globalObservers) {
                globalObservers.forEach(callback => callback(property, newValue, oldValue));
            }
        }
        
        _saveToHistory() {
            this._history.push(JSON.parse(JSON.stringify(this._state)));
            if (this._history.length > this._maxHistory) {
                this._history.shift();
            }
        }
        
        undo() {
            if (this._history.length > 0) {
                const previousState = this._history.pop();
                Object.assign(this._state, previousState);
                this._notify('*', this._state);
            }
        }
        
        getState() {
            return { ...this._state };
        }
    }

    // ===============================================
    // 🔢 CALCULATEUR DE TAXES (PURE FUNCTIONS)
    // ===============================================
    
    const TaxCalculator = {
        /**
         * Calcul avant → après taxes (CORRIGÉ pour Québec)
         */
        calculateForward(amount, provinceCode) {
            const province = TAX_RATES[provinceCode];
            if (!province || amount <= 0) return null;
            
            const breakdown = [];
            let totalTax = 0;
            
            if (province.hst) {
                // HST simple
                const hstAmount = this._round(amount * province.hst / 100);
                totalTax += hstAmount;
                breakdown.push({
                    name: 'TVH/HST',
                    rate: province.hst,
                    amount: hstAmount
                });
            } else {
                // GST
                if (province.gst) {
                    const gstAmount = this._round(amount * province.gst / 100);
                    totalTax += gstAmount;
                    breakdown.push({
                        name: 'TPS/GST',
                        rate: province.gst,
                        amount: gstAmount
                    });
                }
                
                // QST (Québec) - MÉTHODE PARALLÈLE (corrigée)
                if (province.qst) {
                    const qstAmount = this._round(amount * province.qst / 100);
                    totalTax += qstAmount;
                    breakdown.push({
                        name: 'TVQ/QST',
                        rate: province.qst,
                        amount: qstAmount
                    });
                }
                // PST (autres provinces)
                else if (province.pst) {
                    const pstAmount = this._round(amount * province.pst / 100);
                    totalTax += pstAmount;
                    breakdown.push({
                        name: 'TVP/PST',
                        rate: province.pst,
                        amount: pstAmount
                    });
                }
            }
            
            return {
                subtotal: this._round(amount),
                total: this._round(amount + totalTax),
                totalTax: this._round(totalTax),
                breakdown,
                province: province.name
            };
        },
        
        /**
         * Calcul après → avant taxes (CORRIGÉ)
         */
        calculateBackward(totalAmount, provinceCode) {
            const province = TAX_RATES[provinceCode];
            if (!province || totalAmount <= 0) return null;
            
            const subtotal = this._round(totalAmount / (1 + province.total / 100));
            const totalTax = this._round(totalAmount - subtotal);
            
            // Recalculer la répartition sur le subtotal
            const forward = this.calculateForward(subtotal, provinceCode);
            
            return {
                subtotal,
                total: this._round(totalAmount),
                totalTax,
                breakdown: forward ? forward.breakdown : [],
                province: province.name
            };
        },
        
        /**
         * Calcul de pourboire
         */
        calculateTip(baseAmount, tipPercentage) {
            if (baseAmount <= 0 || tipPercentage < 0) return null;
            
            const tipAmount = this._round(baseAmount * tipPercentage / 100);
            const totalWithTip = this._round(baseAmount + tipAmount);
            
            return {
                baseAmount: this._round(baseAmount),
                tipPercentage,
                tipAmount,
                totalWithTip
            };
        },
        
        /**
         * Division par personnes
         */
        calculateSplit(totalAmount, numberOfPeople) {
            if (totalAmount <= 0 || numberOfPeople <= 0) return null;
            
            const amountPerPerson = this._round(totalAmount / numberOfPeople);
            
            return {
                totalAmount: this._round(totalAmount),
                numberOfPeople,
                amountPerPerson
            };
        },
        
        _round(number) {
            return Math.round(number * 100) / 100;
        }
    };

    // ===============================================
    // 🖼️ GESTIONNAIRE D'INTERFACE UTILISATEUR
    // ===============================================
    
    class UIManager {
        constructor(state) {
            this.state = state;
            this.elements = {};
            this.isUpdating = false; // Flag pour éviter les boucles
            
            this._initializeElements();
            this._setupEventListeners();
            this._setupStateObservers();
        }
        
        _initializeElements() {
            this.elements = {
                // Champs principaux
                province: document.getElementById('province'),
                amountBefore: document.getElementById('amount-before-tax'),
                amountAfter: document.getElementById('amount-after-tax'),
                
                // Affichage taxes
                taxPlaceholder: document.getElementById('tax-placeholder'),
                tax1Group: document.getElementById('tax1-group'),
                tax1Label: document.getElementById('tax1-label'),
                tax1Amount: document.getElementById('tax1-amount'),
                tax2Group: document.getElementById('tax2-group'),
                tax2Label: document.getElementById('tax2-label'),
                tax2Amount: document.getElementById('tax2-amount'),
                
                // Pourboire (nouveau système popup)
                tipSection: document.getElementById('tip-section'),
                tipPopupBtn: document.getElementById('tip-popup-btn'),
                tipDisplay: document.getElementById('tip-display'),
                tipPercentageSpan: document.getElementById('tip-percentage'),
                tipAmount: document.querySelector('.tip-amount'),
                totalWithTip: document.querySelector('.total-with-tip'),
                tipModifyBtn: document.getElementById('tip-modify-btn'),
                tipRemoveBtn: document.getElementById('tip-remove-btn'),
                
                // Division
                splitSection: document.getElementById('split-section'),
                peopleSlider: document.getElementById('people'),
                rangeValue: document.querySelector('.range-value'),
                perPersonAmount: document.querySelector('.per-person-amount'),
                splitResult: document.querySelector('.split-result'),
                
                
                // Actions
                resetBtn: document.getElementById('reset-btn')
            };
        }
        
        _setupEventListeners() {
            // Province
            if (this.elements.province) {
                this.elements.province.addEventListener('change', (e) => {
                    this.state.selectedProvince = e.target.value;
                });
            }
            
            // Montants (avec gestion bidirectionnelle)
            if (this.elements.amountBefore) {
                this.elements.amountBefore.addEventListener('input', (e) => {
                    if (this.isUpdating) return;
                    this.state.activeField = 'before';
                    this.state.amountBefore = this._parseNumber(e.target.value);
                });
                
                this.elements.amountBefore.addEventListener('focus', () => {
                    this.state.activeField = 'before';
                });
            }
            
            if (this.elements.amountAfter) {
                this.elements.amountAfter.addEventListener('input', (e) => {
                    if (this.isUpdating) return;
                    this.state.activeField = 'after';
                    this.state.amountAfter = this._parseNumber(e.target.value);
                });
                
                this.elements.amountAfter.addEventListener('focus', () => {
                    this.state.activeField = 'after';
                });
            }
            
            // Bouton popup pourboire
            if (this.elements.tipPopupBtn) {
                this.elements.tipPopupBtn.addEventListener('click', () => {
                    this._openTipPopup();
                });
            }
            
            // Bouton modifier pourboire
            if (this.elements.tipModifyBtn) {
                this.elements.tipModifyBtn.addEventListener('click', () => {
                    this._openTipPopup();
                });
            }
            
            // Bouton supprimer pourboire
            if (this.elements.tipRemoveBtn) {
                this.elements.tipRemoveBtn.addEventListener('click', () => {
                    this._removeTip();
                });
            }
            
            // Slider personnes
            if (this.elements.peopleSlider) {
                this.elements.peopleSlider.addEventListener('input', (e) => {
                    this.state.numberOfPeople = parseInt(e.target.value);
                });
            }
            
            
            // Reset
            if (this.elements.resetBtn) {
                this.elements.resetBtn.addEventListener('click', () => {
                    this._resetCalculator();
                });
            }
            
            // Raccourci clavier Ctrl+P / Cmd+P
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                }
            });
        }
        
        _setupStateObservers() {
            // Observer les changements d'état et mettre à jour l'UI
            this.state.observe('selectedProvince', (province) => {
                this._recalculate();
                this._updateVisibility();
            });
            
            this.state.observe('amountBefore', (amount) => {
                if (this.state.activeField === 'before') {
                    this._recalculate();
                }
                this._updateVisibility();
            });
            
            this.state.observe('amountAfter', (amount) => {
                if (this.state.activeField === 'after') {
                    this._recalculateReverse();
                }
                this._updateVisibility();
            });
            
            this.state.observe('calculation', (calc) => {
                this._updateTaxDisplay(calc);
                this._updateAmountFields(calc);
                this._updateVisibility(); // CORRECTION: Mettre à jour visibilité quand calcul change
            });
            
            this.state.observe('tipPercentage', (percentage) => {
                this._updateTipCalculation();
            });
            
            this.state.observe('numberOfPeople', (people) => {
                this._updateSplitCalculation();
                if (this.elements.rangeValue) {
                    this.elements.rangeValue.textContent = people;
                }
            });
        }
        
        _recalculate() {
            if (!this.state.selectedProvince || !this.state.amountBefore) return;
            
            const calculation = TaxCalculator.calculateForward(
                this.state.amountBefore,
                this.state.selectedProvince
            );
            
            this.state.calculation = calculation;
        }
        
        _recalculateReverse() {
            if (!this.state.selectedProvince || !this.state.amountAfter) return;
            
            const calculation = TaxCalculator.calculateBackward(
                this.state.amountAfter,
                this.state.selectedProvince
            );
            
            this.state.calculation = calculation;
        }
        
        _updateAmountFields(calculation) {
            if (!calculation) return;
            
            this.isUpdating = true;
            
            if (this.state.activeField === 'before' && this.elements.amountAfter) {
                this.elements.amountAfter.value = this._formatNumber(calculation.total);
            } else if (this.state.activeField === 'after' && this.elements.amountBefore) {
                this.elements.amountBefore.value = this._formatNumber(calculation.subtotal);
            }
            
            this.isUpdating = false;
        }
        
        _updateTaxDisplay(calculation) {
            if (!calculation || !calculation.breakdown) {
                this._hideTaxGroups();
                return;
            }
            
            const breakdown = calculation.breakdown;
            
            // Premier taxe
            if (breakdown[0]) {
                this._showTaxGroup(1, breakdown[0].name, breakdown[0].amount);
            } else {
                this._hideTaxGroup(1);
            }
            
            // Deuxième taxe
            if (breakdown[1]) {
                this._showTaxGroup(2, breakdown[1].name, breakdown[1].amount);
            } else {
                this._hideTaxGroup(2);
            }
        }
        
        _showTaxGroup(number, label, amount) {
            const group = this.elements[`tax${number}Group`];
            const labelEl = this.elements[`tax${number}Label`];
            const amountEl = this.elements[`tax${number}Amount`];
            
            if (group && labelEl && amountEl) {
                group.style.display = 'block';
                labelEl.textContent = label;
                amountEl.value = this._formatCurrency(amount);
            }
        }
        
        _hideTaxGroup(number) {
            const group = this.elements[`tax${number}Group`];
            if (group) {
                group.style.display = 'none';
            }
        }
        
        _hideTaxGroups() {
            this._hideTaxGroup(1);
            this._hideTaxGroup(2);
        }
        
        _updateTipCalculation() {
            const baseAmount = this.state.calculation?.total || 0;
            const tipPercentage = this.state.tipPercentage || 0;
            
            if (baseAmount <= 0 || tipPercentage <= 0) {
                this._hideTipResult();
                return;
            }
            
            const tipCalc = TaxCalculator.calculateTip(baseAmount, tipPercentage);
            if (tipCalc) {
                this.state.tipCalculation = tipCalc;
                this._showTipResult(tipCalc);
            }
        }
        
        _showTipResult(tipCalc) {
            // Mettre à jour les montants
            if (this.elements.tipAmount) {
                this.elements.tipAmount.textContent = this._formatCurrency(tipCalc.tipAmount);
            }
            if (this.elements.totalWithTip) {
                this.elements.totalWithTip.textContent = this._formatCurrency(tipCalc.totalWithTip);
            }
            if (this.elements.tipPercentageSpan) {
                this.elements.tipPercentageSpan.textContent = tipCalc.tipPercentage;
            }
            
            // Afficher la section résultat et cacher le bouton
            if (this.elements.tipDisplay) {
                this.elements.tipDisplay.style.display = 'block';
            }
            if (this.elements.tipPopupBtn) {
                this.elements.tipPopupBtn.style.display = 'none';
            }
        }
        
        _hideTipResult() {
            // Cacher la section résultat et afficher le bouton
            if (this.elements.tipDisplay) {
                this.elements.tipDisplay.style.display = 'none';
            }
            if (this.elements.tipPopupBtn) {
                this.elements.tipPopupBtn.style.display = 'block';
            }
        }
        
        _updateSplitCalculation() {
            const baseAmount = this.state.tipCalculation?.totalWithTip || this.state.calculation?.total || 0;
            const numberOfPeople = this.state.numberOfPeople || 1;
            
            if (baseAmount <= 0 || numberOfPeople <= 1) {
                this._hideSplitResult();
                return;
            }
            
            const splitCalc = TaxCalculator.calculateSplit(baseAmount, numberOfPeople);
            if (splitCalc) {
                this.state.splitCalculation = splitCalc;
                this._showSplitResult(splitCalc);
            }
        }
        
        _showSplitResult(splitCalc) {
            if (this.elements.perPersonAmount) {
                this.elements.perPersonAmount.textContent = this._formatCurrency(splitCalc.amountPerPerson);
            }
            if (this.elements.splitResult) {
                this.elements.splitResult.style.display = 'block';
                // Mettre à jour l'attribut data-people pour l'impression
                const perPersonDiv = this.elements.splitResult.querySelector('.per-person');
                if (perPersonDiv) {
                    perPersonDiv.setAttribute('data-people', this.state.numberOfPeople);
                }
            }
        }
        
        _hideSplitResult() {
            if (this.elements.splitResult) {
                this.elements.splitResult.style.display = 'none';
            }
        }
        
        _updateVisibility() {
            const hasProvince = !!this.state.selectedProvince;
            const hasCalculation = !!this.state.calculation;
            const hasAmount = this.state.amountBefore > 0 || this.state.amountAfter > 0;
            
            // Gestion du placeholder des taxes
            if (this.elements.taxPlaceholder) {
                // Afficher le placeholder si :
                // - Aucune province sélectionnée : "Choisir votre province"
                // - Province sélectionnée mais pas de montant : "Veuillez entrer un montant"
                if (!hasProvince) {
                    this.elements.taxPlaceholder.innerHTML = '<p>Choisir votre province</p>';
                    this.elements.taxPlaceholder.style.display = 'block';
                } else if (hasProvince && !hasAmount) {
                    this.elements.taxPlaceholder.innerHTML = '<p>Veuillez entrer un montant pour calculer les taxes</p>';
                    this.elements.taxPlaceholder.style.display = 'block';
                } else {
                    this.elements.taxPlaceholder.style.display = 'none';
                }
            }
            
            // Sections conditionnelles dans la carte principale
            if (this.elements.tipSection) {
                this.elements.tipSection.style.display = hasCalculation ? 'block' : 'none';
            }
            if (this.elements.splitSection) {
                this.elements.splitSection.style.display = hasCalculation ? 'block' : 'none';
            }
            
            // Actions section et ses boutons
            const actionsSection = document.getElementById('actions-section');
            if (actionsSection) {
                actionsSection.style.display = hasCalculation ? 'block' : 'none';
            }
            if (this.elements.resetBtn) {
                this.elements.resetBtn.style.display = hasCalculation ? 'inline-flex' : 'none';
            }
            
            // Dividers visuels - afficher seulement si la section suivante est visible
            const tipDivider = document.getElementById('tip-divider');
            const splitDivider = document.getElementById('split-divider');
            const actionsDivider = document.getElementById('actions-divider');
            
            if (tipDivider) {
                tipDivider.style.display = hasCalculation ? 'block' : 'none';
            }
            if (splitDivider) {
                splitDivider.style.display = hasCalculation ? 'block' : 'none';
            }
            if (actionsDivider) {
                actionsDivider.style.display = hasCalculation ? 'block' : 'none';
            }
        }
        
        /**
         * Ouvrir le popup de sélection de pourboire
         */
        _openTipPopup() {
            if (!window.TipPopupManager) {
                console.error('TipPopupManager not available');
                return;
            }
            
            // Créer l'instance si nécessaire
            if (!this.tipPopup) {
                this.tipPopup = new window.TipPopupManager();
            }
            
            // Ouvrir avec callback
            this.tipPopup.open((selectedTip) => {
                this.state.tipPercentage = selectedTip;
                console.log(`💡 Tip selected: ${selectedTip}%`);
            }, this.state.tipPercentage || 0);
        }
        
        /**
         * Supprimer le pourboire
         */
        _removeTip() {
            this.state.tipPercentage = 0;
            this.state.tipCalculation = null;
            this._hideTipResult();
        }
        
        
        _resetCalculator() {
            // Reset de l'état
            Object.assign(this.state, {
                selectedProvince: '',
                amountBefore: 0,
                amountAfter: 0,
                activeField: null,
                calculation: null,
                tipPercentage: 0,
                tipCalculation: null,
                numberOfPeople: 1,
                splitCalculation: null
            });
            
            // Reset de l'UI
            if (this.elements.province) this.elements.province.value = '';
            if (this.elements.amountBefore) this.elements.amountBefore.value = '';
            if (this.elements.amountAfter) this.elements.amountAfter.value = '';
            if (this.elements.peopleSlider) this.elements.peopleSlider.value = '1';
            
            this._hideTaxGroups();
            this._hideTipResult();
            this._hideSplitResult();
            this._updateVisibility();
        }
        
        // Utilitaires
        _parseNumber(value) {
            if (!value || value === '') return 0;
            // Support virgule française
            const cleaned = value.toString().replace(',', '.');
            const parsed = parseFloat(cleaned);
            return isNaN(parsed) ? 0 : parsed;
        }
        
        _formatNumber(number) {
            return number.toFixed(2);
        }
        
        _formatCurrency(number) {
            return `${number.toFixed(2)}$`;
        }
    }

    // ===============================================
    // 🚀 INITIALISATION DE L'APPLICATION
    // ===============================================
    
    class SimpleCalculator {
        constructor() {
            // État réactif initial
            this.state = new ReactiveState({
                selectedProvince: '',
                amountBefore: 0,
                amountAfter: 0,
                activeField: null,
                calculation: null,
                tipPercentage: 0,
                tipCalculation: null,
                numberOfPeople: 1,
                splitCalculation: null,
            });
            
            // Interface utilisateur
            this.ui = null;
            
            console.log('🧮 SimpleCalculator initialized');
        }
        
        async initialize() {
            try {
                // Attendre que le DOM soit prêt
                if (document.readyState === 'loading') {
                    await new Promise(resolve => {
                        document.addEventListener('DOMContentLoaded', resolve);
                    });
                }
                
                // Initialiser l'interface
                this.ui = new UIManager(this.state);
                
                // Code de partage supprimé
                
                // Exposer pour debug
                window.simpleCalculator = this;
                
                console.log('✅ SimpleCalculator ready');
                
            } catch (error) {
                console.error('❌ SimpleCalculator initialization failed:', error);
                throw error;
            }
        }
        
    }

    // ===============================================
    // 🎬 AUTO-INITIALISATION
    // ===============================================
    
    // Initialiser automatiquement la calculatrice
    const calculator = new SimpleCalculator();
    calculator.initialize().catch(error => {
        console.error('Failed to initialize calculator:', error);
        
        // Affichage d'erreur utilisateur
        document.body.innerHTML += `
            <div style="background: #fee; border: 2px solid #f56565; padding: 20px; margin: 20px; border-radius: 8px;">
                <h3 style="color: #e53e3e;">Erreur d'initialisation</h3>
                <p>La calculatrice simplifiée n'a pas pu se charger.</p>
                <details>
                    <summary>Détails techniques</summary>
                    <pre>${error.stack}</pre>
                </details>
            </div>
        `;
    });

})(); // Fin IIFE