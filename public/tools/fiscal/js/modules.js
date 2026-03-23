/**
 * Modules réutilisables pour le simulateur fiscal
 * Approche modulaire avec responsabilités uniques
 */

// ============================================
// MODULE 1: Formatter - Formatage des données
// ============================================
const Formatter = {
    /**
     * Formate un montant en devise canadienne
     * @param {number} amount - Le montant à formater
     * @returns {string} Montant formaté
     */
    money: function(amount) {
        return new Intl.NumberFormat('fr-CA', {
            style: 'currency',
            currency: 'CAD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount).replace('CA', '').trim();
    },
    
    /**
     * Formate un pourcentage
     * @param {number} value - Valeur à formater (ex: 0.15 pour 15%)
     * @param {number} decimals - Nombre de décimales
     * @returns {string} Pourcentage formaté
     */
    percentage: function(value, decimals = 1) {
        return (value * 100).toFixed(decimals) + ' %';
    },
    
    /**
     * Formate un nombre avec séparateurs de milliers
     * @param {number} value - Valeur à formater
     * @returns {string} Nombre formaté
     */
    number: function(value) {
        return new Intl.NumberFormat('fr-CA').format(value);
    }
};

// ============================================
// MODULE 2: TaxCalculator - Calculs fiscaux
// ============================================
const TaxCalculator = {
    /**
     * Calcule l'impôt selon les paliers
     * @param {number} income - Revenu imposable
     * @param {Array} brackets - Paliers d'imposition
     * @param {number} basicCredit - Crédit de base
     * @returns {Object} Détails du calcul
     */
    calculateTax: function(income, brackets, basicCredit = 0) {
        const taxableIncome = Math.max(0, income - basicCredit);
        let totalTax = 0;
        const details = [];
        
        for (const bracket of brackets) {
            if (taxableIncome > bracket.min) {
                const amountInBracket = Math.min(taxableIncome, bracket.max) - bracket.min;
                if (amountInBracket > 0) {
                    const taxInBracket = amountInBracket * bracket.taux;
                    totalTax += taxInBracket;
                    
                    details.push({
                        bracket: bracket,
                        amountInBracket: amountInBracket,
                        taxAmount: taxInBracket,
                        rate: bracket.taux
                    });
                }
            }
        }
        
        return {
            totalTax: totalTax,
            taxableIncome: taxableIncome,
            details: details
        };
    },
    
    /**
     * Calcule le taux marginal
     * @param {number} income - Revenu
     * @param {Array} brackets - Paliers
     * @returns {number} Taux marginal
     */
    getMarginalRate: function(income, brackets) {
        for (const bracket of brackets) {
            if (income > bracket.min && income <= bracket.max) {
                return bracket.taux;
            }
        }
        return brackets[brackets.length - 1].taux;
    },
    
    /**
     * Calcule le taux effectif
     * @param {number} totalTax - Impôt total
     * @param {number} income - Revenu brut
     * @returns {number} Taux effectif
     */
    getEffectiveRate: function(totalTax, income) {
        return income > 0 ? totalTax / income : 0;
    }
};

// ============================================
// MODULE 3: TaxBracketVisualizer - Visualisation
// ============================================
const TaxBracketVisualizer = {
    /**
     * Prépare les données pour le graphique des paliers
     * @param {number} income - Revenu total
     * @param {Object} quebecDetails - Détails Québec
     * @param {Object} federalDetails - Détails fédéral
     * @returns {Object} Données pour Chart.js
     */
    prepareChartData: function(income, quebecDetails, federalDetails) {
        const datasets = [];
        
        // Configuration des couleurs
        const colors = {
            quebec: ['#cfe2ff', '#6ea8fe', '#0a58ca', '#052c65'],
            federal: ['#fff3cd', '#ffecb5', '#ffc107', '#ff9800', '#ff5722']
        };
        
        // Créer les segments pour Québec
        const quebecSegments = this.createSegments(income, quebecDetails, colors.quebec, 'Québec');
        
        // Créer les segments pour Fédéral
        const federalSegments = this.createSegments(income, federalDetails, colors.federal, 'Fédéral');
        
        return {
            labels: ['Impôt provincial (Québec)', 'Impôt fédéral (avec abattement)'],
            quebecSegments: quebecSegments,
            federalSegments: federalSegments,
            totalIncome: income
        };
    },
    
    /**
     * Crée les segments visuels pour un système fiscal
     * @param {number} totalIncome - Revenu total
     * @param {Object} taxDetails - Détails des calculs
     * @param {Array} colors - Couleurs des paliers
     * @param {string} system - Nom du système (Québec/Fédéral)
     * @returns {Array} Segments pour visualisation
     */
    createSegments: function(totalIncome, taxDetails, colors, system) {
        const segments = [];
        let cumulativeAmount = 0;
        
        if (taxDetails && taxDetails.details) {
            taxDetails.details.forEach((detail, index) => {
                segments.push({
                    startY: cumulativeAmount,
                    height: detail.amountInBracket,
                    color: colors[index] || colors[colors.length - 1],
                    rate: detail.rate,
                    taxAmount: detail.taxAmount,
                    label: `${Formatter.percentage(detail.rate, 1)}`,
                    bracketInfo: detail.bracket
                });
                cumulativeAmount += detail.amountInBracket;
            });
        }
        
        // Ajouter la portion non imposée si nécessaire
        if (cumulativeAmount < totalIncome) {
            segments.unshift({
                startY: 0,
                height: totalIncome - cumulativeAmount,
                color: '#f0f0f0',
                rate: 0,
                taxAmount: 0,
                label: 'Non imposé',
                bracketInfo: { min: 0, max: 0, taux: 0 }
            });
        }
        
        return segments;
    }
};

// ============================================
// MODULE 4: TaxValidator - Tests et validation
// ============================================
const TaxValidator = {
    /**
     * Valide les calculs d'impôt
     * @param {Object} config - Configuration fiscale
     * @returns {Object} Résultats des tests
     */
    runTests: function(config) {
        const testCases = [
            { income: 30000, name: "Bas revenu" },
            { income: 50000, name: "Revenu moyen" },
            { income: 75000, name: "Revenu moyen-élevé" },
            { income: 100000, name: "Revenu élevé" },
            { income: 150000, name: "Revenu très élevé" }
        ];
        
        const results = [];
        
        for (const testCase of testCases) {
            const quebecTax = TaxCalculator.calculateTax(
                testCase.income, 
                config.quebec.paliers, 
                config.quebec.creditBase
            );
            
            const federalTax = TaxCalculator.calculateTax(
                testCase.income, 
                config.federal.paliers, 
                config.federal.creditBase
            );
            
            const federalNetTax = federalTax.totalTax * (1 - config.federal.abattement);
            const totalTax = quebecTax.totalTax + federalNetTax;
            const netIncome = testCase.income - totalTax;
            const effectiveRate = TaxCalculator.getEffectiveRate(totalTax, testCase.income);
            
            results.push({
                testName: testCase.name,
                income: testCase.income,
                quebecTax: quebecTax.totalTax,
                federalTax: federalNetTax,
                totalTax: totalTax,
                netIncome: netIncome,
                effectiveRate: effectiveRate,
                passed: netIncome > 0 && effectiveRate < 1
            });
        }
        
        return {
            tests: results,
            allPassed: results.every(r => r.passed),
            summary: this.generateSummary(results)
        };
    },
    
    /**
     * Génère un résumé des tests
     * @param {Array} results - Résultats des tests
     * @returns {string} Résumé formaté
     */
    generateSummary: function(results) {
        let summary = "=== RAPPORT DE VALIDATION ===\n\n";
        
        for (const result of results) {
            summary += `${result.passed ? '✅' : '❌'} ${result.testName}\n`;
            summary += `   Revenu: ${Formatter.money(result.income)}\n`;
            summary += `   Impôt QC: ${Formatter.money(result.quebecTax)}\n`;
            summary += `   Impôt Féd: ${Formatter.money(result.federalTax)}\n`;
            summary += `   Total: ${Formatter.money(result.totalTax)}\n`;
            summary += `   Net: ${Formatter.money(result.netIncome)}\n`;
            summary += `   Taux effectif: ${Formatter.percentage(result.effectiveRate)}\n\n`;
        }
        
        return summary;
    }
};

// ============================================
// MODULE 5: ChartDataLabels - Labels sur graphiques
// ============================================
const ChartDataLabels = {
    /**
     * Plugin personnalisé pour afficher les montants d'impôt sur les barres
     */
    plugin: {
        id: 'taxAmountLabels',
        afterDatasetsDraw: function(chart) {
            const ctx = chart.ctx;
            const meta0 = chart.getDatasetMeta(0); // Québec
            const meta1 = chart.getDatasetMeta(1); // Fédéral
            
            // Fonction helper pour dessiner le texte
            const drawTaxAmount = function(bar, amount, bgColor) {
                if (amount > 0) {
                    const x = bar.x;
                    const y = bar.y + (bar.base - bar.y) / 2;
                    
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = 'bold 12px sans-serif';
                    
                    // Déterminer la couleur du texte
                    const isDark = bgColor === '#052c65' || bgColor === '#0a58ca' || 
                                  bgColor === '#ff5722' || bgColor === '#ff9800';
                    ctx.fillStyle = isDark ? 'white' : '#333';
                    
                    // Texte avec ombre pour meilleure lisibilité
                    ctx.shadowColor = isDark ? 'rgba(0,0,0,0.5)' : 'rgba(255,255,255,0.8)';
                    ctx.shadowBlur = 4;
                    
                    ctx.fillText(Formatter.money(amount), x, y);
                    ctx.restore();
                }
            };
            
            // Dessiner pour chaque barre si elle a des données
            if (chart.data.customData) {
                const { quebecTaxAmounts, federalTaxAmounts } = chart.data.customData;
                
                // Québec
                if (meta0.data[0] && quebecTaxAmounts) {
                    drawTaxAmount(meta0.data[0], quebecTaxAmounts.total, '#0066cc');
                }
                
                // Fédéral
                if (meta1.data[0] && federalTaxAmounts) {
                    drawTaxAmount(meta1.data[0], federalTaxAmounts.total, '#ff9800');
                }
            }
        }
    }
};

// ============================================
// MODULE 6: ModalManager - Gestion des modals
// ============================================
const ModalManager = {
    /**
     * Initialise tous les modals et boutons
     */
    init: function() {
        // Ajouter les event listeners sur tous les boutons info
        document.querySelectorAll('[data-modal]').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = button.getAttribute('data-modal');
                this.openModal(modalId);
            });
        });

        // Gérer la fermeture avec ESC et backdrop
        document.querySelectorAll('dialog').forEach(dialog => {
            // Bouton de fermeture
            const closeBtn = dialog.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.closeModal(dialog));
            }

            // Fermeture sur backdrop (click outside)
            dialog.addEventListener('click', (e) => {
                const rect = dialog.getBoundingClientRect();
                const isInDialog = (rect.top <= e.clientY && e.clientY <= rect.top + rect.height &&
                                   rect.left <= e.clientX && e.clientX <= rect.left + rect.width);
                if (!isInDialog) {
                    this.closeModal(dialog);
                }
            });

            // Fermeture avec ESC (géré automatiquement par le navigateur pour <dialog>)
            dialog.addEventListener('cancel', (e) => {
                // Permet de gérer la fermeture ESC si nécessaire
            });
        });
    },

    /**
     * Ouvre une modal par son ID
     * @param {string} modalId - ID de la modal
     */
    openModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal && modal.tagName === 'DIALOG') {
            modal.showModal();
            // Focus sur le bouton de fermeture pour l'accessibilité
            const closeBtn = modal.querySelector('.modal-close');
            if (closeBtn) {
                setTimeout(() => closeBtn.focus(), 100);
            }
        }
    },

    /**
     * Ferme une modal
     * @param {HTMLElement} modal - Élément dialog
     */
    closeModal: function(modal) {
        if (modal && modal.tagName === 'DIALOG') {
            modal.close();
        }
    },

    /**
     * Crée une modal dynamiquement
     * @param {string} id - ID de la modal
     * @param {string} title - Titre de la modal
     * @param {string} content - Contenu HTML de la modal
     * @returns {HTMLElement} L'élément dialog créé
     */
    createModal: function(id, title, content) {
        // Vérifier si la modal existe déjà
        let modal = document.getElementById(id);
        if (modal) {
            return modal;
        }

        // Créer la modal
        modal = document.createElement('dialog');
        modal.id = id;
        modal.innerHTML = `
            <div class="modal-header">
                <h2>${title}</h2>
                <button class="modal-close" aria-label="Fermer">×</button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
        `;

        // Ajouter au body
        document.body.appendChild(modal);

        // Initialiser les event listeners
        const closeBtn = modal.querySelector('.modal-close');
        closeBtn.addEventListener('click', () => this.closeModal(modal));

        modal.addEventListener('click', (e) => {
            const rect = modal.getBoundingClientRect();
            const isInDialog = (rect.top <= e.clientY && e.clientY <= rect.top + rect.height &&
                               rect.left <= e.clientX && e.clientX <= rect.left + rect.width);
            if (!isInDialog) {
                this.closeModal(modal);
            }
        });

        return modal;
    }
};

// Export pour utilisation dans app.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        Formatter,
        TaxCalculator,
        TaxBracketVisualizer,
        TaxValidator,
        ChartDataLabels,
        ModalManager
    };
}