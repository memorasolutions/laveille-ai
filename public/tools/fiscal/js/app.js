// Variables globales
let mainChart = null;
let repartitionChart = null;
let tauxChart = null;

// Fonction de calcul d'impôt
function calculerImpot(revenu, config) {
    const revenuImposable = Math.max(0, revenu - config.creditBase);
    let impot = 0;
    let details = [];

    for (let palier of config.paliers) {
        if (revenuImposable > palier.min) {
            const montantDansPalier = Math.min(revenuImposable, palier.max) - palier.min;
            const impotPalier = montantDansPalier * palier.taux;
            if (montantDansPalier > 0) {
                impot += impotPalier;
                details.push({
                    min: palier.min,
                    max: palier.max,
                    taux: palier.taux,
                    montant: montantDansPalier,
                    impot: impotPalier
                });
            }
        }
    }

    return { impot, details, revenuImposable };
}

// Fonction de calcul des cotisations
function calculerCotisations(revenu) {
    const cotisations = {};
    const typeEmploi = document.getElementById('typeEmploi').value;
    const multiplier = typeEmploi === 'autonome' ? 2 : 1;

    // RRQ
    if (document.getElementById('includeRRQ').checked) {
        const revenuCotisable = Math.max(0, Math.min(revenu, CONFIG.cotisations.rrq.mgaBase) - CONFIG.cotisations.rrq.exemption);
        const cotisationBase = revenuCotisable * CONFIG.cotisations.rrq.tauxBase;
        
        let cotisationSupp = 0;
        if (revenu > CONFIG.cotisations.rrq.mgaBase) {
            const revenuSupp = Math.min(revenu, CONFIG.cotisations.rrq.mgaSupp) - CONFIG.cotisations.rrq.mgaBase;
            cotisationSupp = revenuSupp * CONFIG.cotisations.rrq.tauxSupp;
        }
        
        cotisations.rrq = (cotisationBase + cotisationSupp) * multiplier;
    } else {
        cotisations.rrq = 0;
    }

    // Assurance-emploi (pas pour les travailleurs autonomes)
    if (document.getElementById('includeAE').checked && typeEmploi === 'salarie') {
        const revenuAssurable = Math.min(revenu, CONFIG.cotisations.ae.maximum);
        cotisations.ae = revenuAssurable * CONFIG.cotisations.ae.taux;
    } else {
        cotisations.ae = 0;
    }

    // RQAP
    if (document.getElementById('includeRQAP').checked) {
        const revenuAssurable = Math.min(revenu, CONFIG.cotisations.rqap.maximum);
        cotisations.rqap = revenuAssurable * CONFIG.cotisations.rqap.taux * multiplier;
    } else {
        cotisations.rqap = 0;
    }

    return cotisations;
}

// Fonction principale de calcul
function calculerTout() {
    const revenuBase = parseFloat(document.getElementById('revenuInput').value) || 0;
    const tempsSupp = parseFloat(document.getElementById('tempsSuppInput').value) || 0;
    const revenuBrut = revenuBase + tempsSupp;
    const cotisationReer = parseFloat(document.getElementById('reerInput').value) || 0;
    const revenuAjuste = revenuBrut - cotisationReer;
    const revenuAjusteBase = revenuBase - cotisationReer;

    // Calcul des impôts sur le salaire total
    const impotQuebecTotal = calculerImpot(revenuAjuste, CONFIG.quebec);
    const impotFederalTotal = calculerImpot(revenuAjuste, CONFIG.federal);
    const impotFederalNetTotal = impotFederalTotal.impot * (1 - CONFIG.federal.abattement);

    // Calcul des impôts sur le salaire de base seulement
    const impotQuebecBase = calculerImpot(revenuAjusteBase, CONFIG.quebec);
    const impotFederalBase = calculerImpot(revenuAjusteBase, CONFIG.federal);
    const impotFederalNetBase = impotFederalBase.impot * (1 - CONFIG.federal.abattement);

    // Calcul de l'impôt sur le temps supplémentaire
    const impotQuebecTempsSupp = impotQuebecTotal.impot - impotQuebecBase.impot;
    const impotFederalTempsSupp = impotFederalNetTotal - impotFederalNetBase;
    const impotTotalTempsSupp = impotQuebecTempsSupp + impotFederalTempsSupp;

    // Calcul des cotisations
    const cotisations = calculerCotisations(revenuBrut);
    const totalCotisations = Object.values(cotisations).reduce((a, b) => a + b, 0);

    // Calcul du taux marginal
    let tauxMarginal = 0;
    for (let palier of CONFIG.quebec.paliers) {
        if (revenuAjuste > palier.min && revenuAjuste <= palier.max) {
            tauxMarginal += palier.taux;
            break;
        }
    }
    for (let palier of CONFIG.federal.paliers) {
        if (revenuAjuste > palier.min && revenuAjuste <= palier.max) {
            tauxMarginal += palier.taux * (1 - CONFIG.federal.abattement);
            break;
        }
    }

    // Totaux
    const impotTotal = impotQuebecTotal.impot + impotFederalNetTotal;
    const totalDeductions = impotTotal + totalCotisations;
    const revenuNet = revenuBrut - totalDeductions;
    const tauxEffectif = revenuBrut > 0 ? (totalDeductions / revenuBrut) * 100 : 0;
    const economieReer = cotisationReer * tauxMarginal;

    // Mise à jour de l'affichage
    document.getElementById('impotTempsSupp').textContent = formatMontant(impotTotalTempsSupp);

    // Mise à jour de l'interface
    updateUI({
        revenuBrut,
        revenuBase,
        tempsSupp,
        revenuAjuste,
        impotQuebec: impotQuebecTotal.impot,
        impotFederal: impotFederalNetTotal,
        impotFederalBrut: impotFederalTotal.impot,
        impotTempsSupp: impotTotalTempsSupp,
        cotisations,
        totalCotisations,
        totalDeductions,
        revenuNet,
        tauxEffectif,
        tauxMarginal: tauxMarginal * 100,
        economieReer,
        detailsQuebec: impotQuebecTotal.details,
        detailsFederal: impotFederalTotal.details
    });
}

// Fonction de mise à jour de l'interface
function updateUI(data) {
    // Mise à jour des cartes
    document.getElementById('revenuBrutCard').textContent = formatMontant(data.revenuBrut);
    document.getElementById('totalDeductions').textContent = formatMontant(data.totalDeductions);
    document.getElementById('revenuNetCard').textContent = formatMontant(data.revenuNet);
    document.getElementById('tauxEffectifCard').textContent = data.tauxEffectif.toFixed(2) + ' %';
    document.getElementById('tauxMarginalCard').textContent = data.tauxMarginal.toFixed(2) + ' %';
    document.getElementById('economieReer').textContent = formatMontant(data.economieReer);
    
    // Mise à jour de la carte temps supplémentaire
    document.getElementById('tempsSuppBrut').textContent = formatMontant(data.tempsSupp || 0);
    document.getElementById('impotTempsSuppCard').textContent = formatMontant(data.impotTempsSupp || 0);
    const tauxTempsSupp = data.tempsSupp > 0 ? (data.impotTempsSupp / data.tempsSupp * 100) : 0;
    document.getElementById('tauxTempsSuppCard').textContent = tauxTempsSupp.toFixed(2) + ' %';

    // Mise à jour de la barre de progression - CORRECTION du calcul pédagogique
    const pourcentageNet = data.revenuBrut > 0 ? (data.revenuNet / data.revenuBrut * 100) : 0;
    const progressBar = document.getElementById('progressBar');
    
    // DEBUG: Log des valeurs pour identifier le problème 110.4%
    console.log('DEBUG Barre de progression:', {
        revenuBrut: data.revenuBrut,
        revenuNet: data.revenuNet,
        pourcentageCalcule: pourcentageNet,
        largeurCSS: pourcentageNet + '%'
    });
    
    // CORRECTION ROBUSTE: S'assurer que la barre ne dépasse jamais 100%
    const largeurBarre = Math.min(Math.max(pourcentageNet, 0), 100);
    
    // La barre représente visuellement le pourcentage du revenu qui reste net
    progressBar.style.width = largeurBarre + '%';
    
    // DEBUG: Vérifier la cohérence
    if (Math.abs(largeurBarre - pourcentageNet) > 0.1) {
        console.warn('Correction appliquée à la barre:', {
            pourcentageCalcule: pourcentageNet,
            largeurAppliquee: largeurBarre,
            difference: Math.abs(largeurBarre - pourcentageNet)
        });
    }
    
    setTimeout(() => {
        const largeurAppliquee = progressBar.style.width;
        console.log('DEBUG Barre finale:', {
            styleDirect: largeurAppliquee,
            texteAffiche: progressBar.textContent,
            coherence: largeurAppliquee === (largeurBarre + '%') ? 'OK' : 'PROBLEME'
        });
    }, 100);
    
    // Affichage clair et pédagogique : pourcentage du revenu qui reste après impôts
    if (pourcentageNet >= 100) {
        // Gestion d'erreur si jamais le calcul était inversé
        progressBar.textContent = `Erreur de calcul détectée - Vérification requise`;
        console.warn('Erreur: pourcentage net supérieur à 100%', {revenuNet: data.revenuNet, revenuBrut: data.revenuBrut, pourcentage: pourcentageNet});
    } else {
        progressBar.textContent = `Il vous reste ${pourcentageNet.toFixed(1)}% de votre revenu brut après impôts et cotisations`;
    }

    // Mise à jour des graphiques avec requestAnimationFrame pour la fluidité
    requestAnimationFrame(() => {
        updateMainChart(data);
        updateRepartitionChart(data);
        updateTauxChart(data.revenuBrut);
    });

    // Mise à jour des détails
    updateDetails(data);
}

/**
 * Crée les segments visuels pour représenter le salaire et son imposition
 * @param {number} totalIncome - Revenu total imposable
 * @param {Array} taxDetails - Détails des calculs d'impôt
 * @param {Object} config - Configuration fiscale (quebec ou federal)
 * @param {Array} colors - Couleurs pour les segments
 * @returns {Array} Segments pour le graphique
 */
function createTaxSegments(totalIncome, taxDetails, config, colors) {
    const segments = [];
    let currentPosition = 0;
    
    // D'abord, ajouter la portion non imposable (crédit de base)
    if (config.creditBase && config.creditBase > 0) {
        const nonTaxableAmount = Math.min(config.creditBase, totalIncome);
        if (nonTaxableAmount > 0) {
            segments.push({
                height: nonTaxableAmount,
                color: colors[0], // Couleur grise pour non imposé
                label: 'Non imposé',
                taxAmount: 0,
                rate: 0,
                rateDisplay: '0 %',
                isNonTaxable: true,
                bracketInfo: {
                    min: 0,
                    max: config.creditBase,
                    taux: 0,
                    nom: 'Crédit de base'
                }
            });
            currentPosition = nonTaxableAmount;
        }
    }
    
    // Ensuite, ajouter les segments imposables
    if (taxDetails && taxDetails.length > 0) {
        taxDetails.forEach((detail, index) => {
            if (detail.montant > 0) {
                const effectiveRate = config.abattement ? 
                    detail.taux * (1 - config.abattement) : 
                    detail.taux;
                
                segments.push({
                    height: detail.montant,
                    color: colors[index + 1] || colors[colors.length - 1],
                    label: `${(detail.taux * 100).toFixed(1)} %`,
                    taxAmount: detail.impot * (config.abattement ? (1 - config.abattement) : 1),
                    rate: detail.taux,
                    effectiveRate: effectiveRate,
                    rateDisplay: config.abattement ? 
                        `${(effectiveRate * 100).toFixed(1)} % (après abattement)` :
                        `${(detail.taux * 100).toFixed(1)} %`,
                    isNonTaxable: false,
                    bracketInfo: {
                        min: detail.min || currentPosition,
                        max: detail.max || (currentPosition + detail.montant),
                        taux: detail.taux,
                        nom: `${(detail.taux * 100).toFixed(1)} %`
                    }
                });
                currentPosition += detail.montant;
            }
        });
    }
    
    // Si le total des segments est inférieur au revenu total, ajouter la différence
    const totalSegmentHeight = segments.reduce((sum, seg) => sum + seg.height, 0);
    if (totalSegmentHeight < totalIncome) {
        const difference = totalIncome - totalSegmentHeight;
        segments.push({
            height: difference,
            color: colors[0],
            label: 'Reste non catégorisé',
            taxAmount: 0,
            rate: 0,
            rateDisplay: '0 %',
            isNonTaxable: true,
            bracketInfo: {
                min: totalSegmentHeight,
                max: totalIncome,
                taux: 0,
                nom: 'Non catégorisé'
            }
        });
    }
    
    return segments;
}

// Mise à jour du graphique principal - NOUVELLE VERSION MODULAIRE
function updateMainChart(data) {
    const ctx = document.getElementById('mainChart').getContext('2d');
    
    // NOUVELLE APPROCHE : Les deux barres représentent le SALAIRE TOTAL
    // avec des segments colorés pour montrer comment ce salaire est imposé
    
    const salaireTotalImposable = data.revenuAjuste || 0;
    
    // Couleurs pour les segments
    const couleursQuebec = ['#f0f0f0', '#cfe2ff', '#6ea8fe', '#0a58ca', '#052c65'];
    const couleursFederal = ['#f0f0f0', '#fff3cd', '#ffecb5', '#ffc107', '#ff9800', '#ff5722'];
    
    // Créer les segments pour chaque système fiscal
    const segmentsQuebec = createTaxSegments(salaireTotalImposable, data.detailsQuebec, CONFIG.quebec, couleursQuebec);
    const segmentsFederal = createTaxSegments(salaireTotalImposable, data.detailsFederal, CONFIG.federal, couleursFederal);
    
    // Préparer les datasets pour Chart.js
    const datasets = [];
    const labels = ['Impôt provincial (Québec)', 'Impôt fédéral (avec abattement)'];
    
    // Données pour la légende dynamique
    const impotParPalierQuebec = [];
    const impotParPalierFederal = [];
    
    // Créer les datasets pour Québec (colonne 1)
    segmentsQuebec.forEach((segment, index) => {
        datasets.push({
            label: segment.label,
            data: [segment.height, 0], // Hauteur sur la première colonne seulement
            backgroundColor: segment.color,
            borderColor: 'rgba(255, 255, 255, 0.8)',
            borderWidth: segment.height > 0 ? 1 : 0,
            stack: 'stack1',
            categoryPercentage: 0.95, // Élargir les colonnes de 50% de plus (0.8 → 0.95)
            barPercentage: 0.98,     // Élargir les barres individuelles au maximum
            segmentInfo: segment
        });
        
        if (segment.taxAmount > 0) {
            impotParPalierQuebec.push({
                palier: segment.bracketInfo,
                montant: segment.height,
                impot: segment.taxAmount
            });
        }
    });
    
    // Créer les datasets pour Fédéral (colonne 2)
    segmentsFederal.forEach((segment, index) => {
        datasets.push({
            label: segment.label,
            data: [0, segment.height], // Hauteur sur la deuxième colonne seulement
            backgroundColor: segment.color,
            borderColor: 'rgba(255, 255, 255, 0.8)',
            borderWidth: segment.height > 0 ? 1 : 0,
            stack: 'stack2',
            categoryPercentage: 0.95, // Élargir les colonnes de 50% de plus (0.8 → 0.95)
            barPercentage: 0.98,     // Élargir les barres individuelles au maximum
            segmentInfo: segment
        });
        
        if (segment.taxAmount > 0) {
            impotParPalierFederal.push({
                palier: segment.bracketInfo,
                montant: segment.height,
                impot: segment.taxAmount,
                tauxEffectif: segment.effectiveRate
            });
        }
    });

    const chartData = {
        labels: labels,
        datasets: datasets,
        // Données personnalisées pour les labels
        customData: {
            totalIncome: salaireTotalImposable,
            quebecTax: data.impotQuebec,
            federalTax: data.impotFederal
        }
    };

    if (mainChart) {
        mainChart.data = chartData;
        // Mettre à jour l'axe Y dynamiquement
        mainChart.options.scales.y.max = salaireTotalImposable * 1.15; // 15% d'espace en haut
        mainChart.update('none'); // Pas d'animation pour la fluidité
    } else {
        mainChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false, // Désactiver l'animation pour la fluidité
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 13,
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        // Pas de max fixe - sera défini dynamiquement lors de la mise à jour
                        title: {
                            display: true,
                            text: 'Salaire ($)',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return formatMontant(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.9)',
                        titleColor: '#ffffff',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyColor: '#e5e7eb',
                        bodyFont: {
                            size: 12
                        },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                if (context.parsed.y === 0) return null;
                                
                                const dataset = context.dataset;
                                const segment = dataset.segmentInfo;
                                
                                if (!segment || segment.isNonTaxable) {
                                    return [`Portion non imposée : ${formatMontant(context.parsed.y)}`];
                                }
                                
                                return [
                                    `Palier : ${segment.label}`,
                                    `Tranche de revenu : ${formatMontant(segment.bracketInfo.min)} à ${segment.bracketInfo.max === Infinity ? '∞' : formatMontant(segment.bracketInfo.max)}`,
                                    `Montant dans ce palier : ${formatMontant(segment.height)}`,
                                    `Taux d'imposition : ${segment.rateDisplay}`,
                                    `➜ Impôt généré : ${formatMontant(segment.taxAmount)}`
                                ];
                            }
                        }
                    },
                }
            },
            plugins: [{
                id: 'segmentTaxLabels',
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    
                    // Dessiner l'impôt sur chaque segment individuel
                    chart.data.datasets.forEach((dataset, datasetIndex) => {
                        const meta = chart.getDatasetMeta(datasetIndex);
                        const segmentInfo = dataset.segmentInfo;
                        
                        if (meta.data && meta.data.length > 0 && segmentInfo && segmentInfo.taxAmount > 0) {
                            // Trouver la barre appropriée (colonne Québec ou Fédéral)
                            let bar = null;
                            let dataValue = 0;
                            
                            // Pour stack1 (Québec) - première colonne
                            if (dataset.stack === 'stack1' && dataset.data[0] > 0) {
                                bar = meta.data[0];
                                dataValue = dataset.data[0];
                            }
                            // Pour stack2 (Fédéral) - deuxième colonne  
                            else if (dataset.stack === 'stack2' && dataset.data[1] > 0) {
                                bar = meta.data[1];
                                dataValue = dataset.data[1];
                            }
                            
                            if (bar && dataValue > 0 && bar.height > 15) { // Seulement si le segment est assez grand
                                const x = bar.x;
                                const y = bar.y + (bar.base - bar.y) / 2; // Centre du segment
                                
                                ctx.save();
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.font = 'bold 11px sans-serif';
                                
                                // Texte de l'impôt
                                const taxText = formatMontant(segmentInfo.taxAmount);
                                
                                // Couleur du texte selon le fond (pas d'ombre)
                                const isDarkBackground = segmentInfo.color === '#052c65' || 
                                                       segmentInfo.color === '#0a58ca' || 
                                                       segmentInfo.color === '#ff5722' || 
                                                       segmentInfo.color === '#ff9800' ||
                                                       segmentInfo.color === '#ffc107';
                                
                                ctx.fillStyle = isDarkBackground ? 'white' : '#333';
                                ctx.fillText(taxText, x, y);
                                
                                ctx.restore();
                            }
                        }
                    });
                }
            }]
        });
    }
    
    // Mettre à jour la légende dynamique avec les montants
    updateDynamicLegend(impotParPalierQuebec, impotParPalierFederal);
}

// Mise à jour du graphique de répartition (OPTIMISÉ POUR LA FLUIDITÉ)
function updateRepartitionChart(data) {
    const ctx = document.getElementById('repartitionChart').getContext('2d');
    
    const chartData = {
        labels: ['Répartition du revenu'],
        datasets: [
            {
                label: 'Revenu net',
                data: [data.revenuNet],
                backgroundColor: 'rgba(40, 167, 69, 0.9)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 2,
                borderRadius: 5
            },
            {
                label: 'Impôt Québec',
                data: [data.impotQuebec],
                backgroundColor: 'rgba(0, 102, 204, 0.9)',
                borderColor: 'rgba(0, 102, 204, 1)',
                borderWidth: 2,
                borderRadius: 5
            },
            {
                label: 'Impôt fédéral',
                data: [data.impotFederal],
                backgroundColor: 'rgba(255, 152, 0, 0.9)',
                borderColor: 'rgba(255, 152, 0, 1)',
                borderWidth: 2,
                borderRadius: 5
            },
            {
                label: 'RRQ',
                data: [data.cotisations.rrq || 0],
                backgroundColor: 'rgba(103, 58, 183, 0.9)',
                borderColor: 'rgba(103, 58, 183, 1)',
                borderWidth: 2,
                borderRadius: 5
            },
            {
                label: 'Assurance-emploi',
                data: [data.cotisations.ae || 0],
                backgroundColor: 'rgba(233, 30, 99, 0.9)',
                borderColor: 'rgba(233, 30, 99, 1)',
                borderWidth: 2,
                borderRadius: 5
            },
            {
                label: 'RQAP',
                data: [data.cotisations.rqap || 0],
                backgroundColor: 'rgba(0, 150, 136, 0.9)',
                borderColor: 'rgba(0, 150, 136, 1)',
                borderWidth: 2,
                borderRadius: 5
            }
        ]
    };

    if (repartitionChart) {
        repartitionChart.data = chartData;
        repartitionChart.update('none'); // Pas d'animation pour la fluidité
    } else {
        repartitionChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false, // Désactiver l'animation pour la fluidité
                indexAxis: 'y',
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Montant ($)',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return formatMontant(value);
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 15,
                            padding: 10,
                            font: {
                                size: 11
                            },
                            usePointStyle: true,
                            pointStyle: 'rectRounded'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.x;
                                const total = data.revenuBrut;
                                if (value === 0) return '';
                                
                                // Validation du calcul de pourcentage
                                if (total <= 0) {
                                    return `${context.dataset.label} : ${formatMontant(value)}`;
                                }
                                
                                const percentage = ((value / total) * 100).toFixed(1);
                                
                                // Vérification de cohérence uniquement pour des cas vraiment problématiques
                                if (parseFloat(percentage) > 200) {
                                    console.warn('Pourcentage très incohérent détecté:', {
                                        label: context.dataset.label,
                                        value: value,
                                        total: total,
                                        percentage: percentage
                                    });
                                    return `${context.dataset.label} : ${formatMontant(value)} (calcul à vérifier)`;
                                }
                                
                                // Log pour debug si nécessaire
                                if (parseFloat(percentage) > 100) {
                                    console.log('Pourcentage élevé (normal pour certains cas):', {
                                        label: context.dataset.label,
                                        percentage: percentage
                                    });
                                }
                                
                                // Affichage pédagogique clair et précis
                                const label = context.dataset.label;
                                if (label === 'Revenu net') {
                                    return `${label} : ${formatMontant(value)} (${percentage}% de votre revenu reste dans vos poches)`;
                                } else if (label.includes('Impôt')) {
                                    return `${label} : ${formatMontant(value)} (${percentage}% de votre revenu part en impôts)`;
                                } else {
                                    return `${label} : ${formatMontant(value)} (${percentage}% du revenu brut)`;
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}

// Mise à jour du graphique des taux (OPTIMISÉ POUR LA FLUIDITÉ)
function updateTauxChart(revenuActuel) {
    const ctx = document.getElementById('tauxChart').getContext('2d');
    
    // Générer les données pour la courbe
    const points = [];
    for (let revenu = 0; revenu <= 200000; revenu += 5000) {
        const revenuAjuste = revenu;
        const impotQ = calculerImpot(revenuAjuste, CONFIG.quebec);
        const impotF = calculerImpot(revenuAjuste, CONFIG.federal);
        const impotTotal = impotQ.impot + impotF.impot * (1 - CONFIG.federal.abattement);
        const cotisations = calculerCotisations(revenu);
        const totalCotisations = Object.values(cotisations).reduce((a, b) => a + b, 0);
        const totalDeductions = impotTotal + totalCotisations;
        const tauxEffectif = revenu > 0 ? (totalDeductions / revenu) * 100 : 0;
        
        points.push({
            x: revenu,
            y: tauxEffectif
        });
    }

    const chartData = {
        datasets: [{
            label: 'Taux effectif (%)',
            data: points,
            borderColor: '#0066cc',
            backgroundColor: 'rgba(0, 102, 204, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 5
        }, {
            label: 'Votre position',
            data: [{
                x: revenuActuel,
                y: points.find(p => p.x >= revenuActuel)?.y || 0
            }],
            backgroundColor: '#dc3545',
            borderColor: '#dc3545',
            pointRadius: 10,
            pointHoverRadius: 12,
            showLine: false
        }]
    };

    if (tauxChart) {
        tauxChart.data = chartData;
        tauxChart.update('none'); // Pas d'animation pour la fluidité
    } else {
        tauxChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false, // Désactiver l'animation pour la fluidité
                scales: {
                    x: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: 'Revenu ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return formatMontant(value);
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Taux effectif (%)'
                        },
                        min: 0,
                        max: 50
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return `Taux : ${context.parsed.y.toFixed(2)} %`;
                                } else {
                                    return `Votre taux : ${context.parsed.y.toFixed(2)} %`;
                                }
                            },
                            title: function(context) {
                                return `Revenu : ${formatMontant(context[0].parsed.x)}`;
                            }
                        }
                    }
                }
            }
        });
    }
}

// Mise à jour des détails - AFFICHAGE PÉDAGOGIQUE AMÉLIORÉ
function updateDetails(data) {
    // IMPORTANT : Utiliser les détails déjà calculés dans calculerTout()
    // pour garantir la synchronisation parfaite
    
    // Détails Québec - Affichage pédagogique amélioré
    let htmlQuebec = '';
    
    // Afficher le calcul du revenu imposable
    htmlQuebec += `
        <div class="detail-explanation">
            <div class="detail-line">
                <span>Revenu brut</span>
                <span>${formatMontant(data.revenuBrut)}</span>
            </div>
            <div class="detail-line">
                <span>Crédit de base (non imposé)</span>
                <span>-${formatMontant(CONFIG.quebec.creditBase)}</span>
            </div>
            <div class="detail-line revenu-imposable">
                <span>= Revenu imposable</span>
                <span>${formatMontant(data.revenuAjuste)}</span>
            </div>
        </div>
        <div class="detail-separator"></div>
    `;
    
    // Détails par palier
    if (data.detailsQuebec && data.detailsQuebec.length > 0) {
        for (let detail of data.detailsQuebec) {
            htmlQuebec += `
                <div class="detail-line">
                    <span>${formatMontant(detail.montant)} × ${(detail.taux * 100).toFixed(1)} %</span>
                    <span>${formatMontant(detail.impot)}</span>
                </div>
            `;
        }
    }
    htmlQuebec += `
        <div class="detail-line total-line">
            <span>Total Québec</span>
            <span>${formatMontant(data.impotQuebec)}</span>
        </div>
    `;
    document.getElementById('detailsQuebec').innerHTML = htmlQuebec;

    // Détails Fédéral - Affichage pédagogique amélioré
    let htmlFederal = '';
    
    // Afficher le calcul du revenu imposable fédéral
    htmlFederal += `
        <div class="detail-explanation">
            <div class="detail-line">
                <span>Revenu brut</span>
                <span>${formatMontant(data.revenuBrut)}</span>
            </div>
            <div class="detail-line">
                <span>Crédit de base fédéral (non imposé)</span>
                <span>-${formatMontant(CONFIG.federal.creditBase)}</span>
            </div>
            <div class="detail-line revenu-imposable">
                <span>= Revenu imposable fédéral</span>
                <span>${formatMontant(data.revenuBrut - CONFIG.federal.creditBase)}</span>
            </div>
        </div>
        <div class="detail-separator"></div>
    `;
    
    // Détails par palier avec taux effectifs
    if (data.detailsFederal && data.detailsFederal.length > 0) {
        for (let detail of data.detailsFederal) {
            const tauxEffectif = detail.taux * (1 - CONFIG.federal.abattement);
            const impotNet = detail.impot * (1 - CONFIG.federal.abattement);
            htmlFederal += `
                <div class="detail-line">
                    <span>${formatMontant(detail.montant)} × ${(tauxEffectif * 100).toFixed(1)} % (avec abattement)</span>
                    <span>${formatMontant(impotNet)}</span>
                </div>
            `;
        }
    }
    htmlFederal += `
        <div class="detail-separator"></div>
        <div class="detail-line">
            <span>Impôt brut (avant abattement)</span>
            <span>${formatMontant(data.impotFederalBrut)}</span>
        </div>
        <div class="detail-line">
            <span>Abattement du Québec (-16,5 %)</span>
            <span>-${formatMontant(data.impotFederalBrut * CONFIG.federal.abattement)}</span>
        </div>
        <div class="detail-line total-line">
            <span>Total fédéral</span>
            <span>${formatMontant(data.impotFederal)}</span>
        </div>
    `;
    document.getElementById('detailsFederal').innerHTML = htmlFederal;

    // Détails cotisations
    let htmlCotisations = '';
    if (data.cotisations.rrq > 0) {
        htmlCotisations += `
            <div class="detail-line">
                <span>RRQ</span>
                <span>${formatMontant(data.cotisations.rrq)}</span>
            </div>
        `;
    }
    if (data.cotisations.ae > 0) {
        htmlCotisations += `
            <div class="detail-line">
                <span>Assurance-emploi</span>
                <span>${formatMontant(data.cotisations.ae)}</span>
            </div>
        `;
    }
    if (data.cotisations.rqap > 0) {
        htmlCotisations += `
            <div class="detail-line">
                <span>RQAP</span>
                <span>${formatMontant(data.cotisations.rqap)}</span>
            </div>
        `;
    }
    htmlCotisations += `
        <div class="detail-line">
            <span>Total cotisations</span>
            <span>${formatMontant(data.totalCotisations)}</span>
        </div>
    `;
    document.getElementById('detailsCotisations').innerHTML = htmlCotisations;

    // Résumé
    const htmlResume = `
        <div class="detail-line">
            <span>Revenu brut</span>
            <span>${formatMontant(data.revenuBrut)}</span>
        </div>
        <div class="detail-line">
            <span>Impôts</span>
            <span>-${formatMontant(data.impotQuebec + data.impotFederal)}</span>
        </div>
        <div class="detail-line">
            <span>Cotisations</span>
            <span>-${formatMontant(data.totalCotisations)}</span>
        </div>
        <div class="detail-line">
            <span>Revenu net</span>
            <span>${formatMontant(data.revenuNet)}</span>
        </div>
    `;
    document.getElementById('detailsResume').innerHTML = htmlResume;
}

// Fonction pour mettre à jour la légende dynamique avec les montants d'impôt
function updateDynamicLegend(impotQuebec, impotFederal) {
    // Créer ou mettre à jour la légende dynamique
    let legendContainer = document.getElementById('dynamicLegend');
    if (!legendContainer) {
        // Créer le conteneur s'il n'existe pas
        const chartWrapper = document.querySelector('.chart-wrapper');
        legendContainer = document.createElement('div');
        legendContainer.id = 'dynamicLegend';
        legendContainer.className = 'dynamic-legend';
        
        // Insérer après le graphique principal
        const barLegend = document.querySelector('.bar-legend');
        if (barLegend) {
            barLegend.insertBefore(legendContainer, barLegend.firstChild);
        }
    }
    
    // Générer le HTML de la légende avec les montants actuels
    let htmlContent = '<div class="legend-dynamic-title">💡 Montants d\'impôt payés en temps réel :</div>';
    htmlContent += '<div class="legend-dynamic-grid">';
    
    // Section Québec
    htmlContent += '<div class="legend-dynamic-section">';
    htmlContent += '<div class="legend-dynamic-header">Impôt provincial (Québec)</div>';
    
    let totalQuebec = 0;
    impotQuebec.forEach((item, index) => {
        if (item.montant > 0) {
            htmlContent += `
                <div class="legend-dynamic-item ${item.impot > 0 ? 'active' : ''}">
                    <div class="legend-color quebec-${index + 1}"></div>
                    <div class="legend-text">
                        <span class="legend-palier">${item.palier.nom} (${formatMontant(item.palier.min)} - ${item.palier.max === Infinity ? '∞' : formatMontant(item.palier.max)})</span>
                        <span class="legend-montant">${formatMontant(item.impot)}</span>
                    </div>
                </div>
            `;
            totalQuebec += item.impot;
        }
    });
    
    htmlContent += `
        <div class="legend-dynamic-total">
            <span>Total Québec :</span>
            <span class="total-amount">${formatMontant(totalQuebec)}</span>
        </div>
    `;
    htmlContent += '</div>';
    
    // Section Fédéral
    htmlContent += '<div class="legend-dynamic-section">';
    htmlContent += '<div class="legend-dynamic-header">Impôt fédéral (après abattement 16,5 %)</div>';
    
    let totalFederal = 0;
    impotFederal.forEach((item, index) => {
        if (item.montant > 0) {
            const tauxAffiche = item.tauxEffectif ? (item.tauxEffectif * 100).toFixed(1) : (item.palier.taux * 100).toFixed(1);
            htmlContent += `
                <div class="legend-dynamic-item ${item.impot > 0 ? 'active' : ''}">
                    <div class="legend-color federal-${index + 1}"></div>
                    <div class="legend-text">
                        <span class="legend-palier">${tauxAffiche} % (${formatMontant(item.palier.min)} - ${item.palier.max === Infinity ? '∞' : formatMontant(item.palier.max)})</span>
                        <span class="legend-montant">${formatMontant(item.impot)}</span>
                    </div>
                </div>
            `;
            totalFederal += item.impot;
        }
    });
    
    htmlContent += `
        <div class="legend-dynamic-total">
            <span>Total Fédéral :</span>
            <span class="total-amount">${formatMontant(totalFederal)}</span>
        </div>
    `;
    htmlContent += '</div>';
    htmlContent += '</div>';
    
    // Message pédagogique
    htmlContent += `
        <div class="legend-pedagogic-message">
            <strong>📚 Principe important :</strong> Quand votre revenu augmente et entre dans un nouveau palier, 
            <span class="highlight">les montants d'impôt des paliers inférieurs restent identiques</span>. 
            Seul le revenu qui dépasse dans le nouveau palier est imposé au taux plus élevé !
        </div>
    `;
    
    legendContainer.innerHTML = htmlContent;
}

// Fonction de formatage des montants
function formatMontant(montant) {
    return new Intl.NumberFormat('fr-CA', {
        style: 'currency',
        currency: 'CAD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(montant).replace('CA', '').trim();
}

// Fonction pour afficher/masquer les détails
function toggleDetails() {
    const content = document.getElementById('detailsContent');
    const button = document.querySelector('.toggle-button');
    
    if (content.classList.contains('active')) {
        content.classList.remove('active');
        button.textContent = 'Afficher les détails';
    } else {
        content.classList.add('active');
        button.textContent = 'Masquer les détails';
    }
}

// Gestionnaires d'événements (OPTIMISÉS POUR LA FLUIDITÉ)
document.getElementById('revenuSlider').addEventListener('input', function(e) {
    const value = e.target.value;
    document.getElementById('revenuInput').value = value;
    document.getElementById('revenuDisplay').textContent = formatMontant(value);
    // Mise à jour immédiate de l'affichage sans attendre le calcul
    document.getElementById('revenuBrutCard').textContent = formatMontant(
        parseFloat(value) + parseFloat(document.getElementById('tempsSuppInput').value || 0)
    );
    requestAnimationFrame(calculerTout);
});

document.getElementById('revenuInput').addEventListener('change', function(e) {
    const value = Math.min(500000, Math.max(0, e.target.value));
    e.target.value = value;
    document.getElementById('revenuSlider').value = Math.min(250000, value);
    document.getElementById('revenuDisplay').textContent = formatMontant(value);
    calculerTout();
});

document.getElementById('tempsSuppSlider').addEventListener('input', function(e) {
    const value = e.target.value;
    document.getElementById('tempsSuppInput').value = value;
    document.getElementById('tempsSuppDisplay').textContent = formatMontant(value);
    // Mise à jour immédiate de l'affichage
    document.getElementById('tempsSuppBrut').textContent = formatMontant(value);
    requestAnimationFrame(calculerTout);
});

document.getElementById('tempsSuppInput').addEventListener('change', function(e) {
    const value = Math.min(100000, Math.max(0, e.target.value));
    e.target.value = value;
    document.getElementById('tempsSuppSlider').value = Math.min(50000, value);
    document.getElementById('tempsSuppDisplay').textContent = formatMontant(value);
    calculerTout();
});

document.getElementById('reerSlider').addEventListener('input', function(e) {
    const value = e.target.value;
    document.getElementById('reerInput').value = value;
    document.getElementById('reerDisplay').textContent = formatMontant(value);
    requestAnimationFrame(calculerTout);
});

document.getElementById('reerInput').addEventListener('change', function(e) {
    const value = Math.min(50000, Math.max(0, e.target.value));
    e.target.value = value;
    document.getElementById('reerSlider').value = Math.min(32490, value);
    document.getElementById('reerDisplay').textContent = formatMontant(value);
    calculerTout();
});

document.getElementById('typeEmploi').addEventListener('change', calculerTout);
document.getElementById('includeRRQ').addEventListener('change', calculerTout);
document.getElementById('includeAE').addEventListener('change', calculerTout);
document.getElementById('includeRQAP').addEventListener('change', calculerTout);

// Initialisation
window.addEventListener('load', function() {
    calculerTout();
});

// Gestion du mode plein écran
function toggleFullscreen() {
    const container = document.querySelector('.container');
    const fullscreenIcon = document.getElementById('fullscreenIcon');
    const exitFullscreenIcon = document.getElementById('exitFullscreenIcon');

    if (!container.classList.contains('fullscreen-mode')) {
        // Activer le mode plein écran
        container.classList.add('fullscreen-mode', 'entering');
        fullscreenIcon.style.display = 'none';
        exitFullscreenIcon.style.display = 'block';

        // Tenter d'utiliser l'API Fullscreen du navigateur
        if (container.requestFullscreen) {
            container.requestFullscreen().catch(err => {
                console.log('Fullscreen API non disponible:', err);
            });
        } else if (container.webkitRequestFullscreen) {
            container.webkitRequestFullscreen();
        } else if (container.mozRequestFullScreen) {
            container.mozRequestFullScreen();
        } else if (container.msRequestFullscreen) {
            container.msRequestFullscreen();
        }

        // Retirer la classe d'animation après la transition
        setTimeout(() => {
            container.classList.remove('entering');
        }, 300);

        // Forcer la mise à jour des graphiques
        setTimeout(() => {
            if (mainChart) mainChart.resize();
            if (repartitionChart) repartitionChart.resize();
            if (tauxChart) tauxChart.resize();
            calculerTout();
        }, 350);

    } else {
        // Désactiver le mode plein écran
        container.classList.remove('fullscreen-mode');
        fullscreenIcon.style.display = 'block';
        exitFullscreenIcon.style.display = 'none';

        // Quitter le mode plein écran du navigateur
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(err => {
                console.log('Erreur lors de la sortie du plein écran:', err);
            });
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }

        // Forcer la mise à jour des graphiques
        setTimeout(() => {
            if (mainChart) mainChart.resize();
            if (repartitionChart) repartitionChart.resize();
            if (tauxChart) tauxChart.resize();
            calculerTout();
        }, 100);
    }
}

// Détecter la sortie du plein écran avec ESC
document.addEventListener('fullscreenchange', handleFullscreenChange);
document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
document.addEventListener('mozfullscreenchange', handleFullscreenChange);
document.addEventListener('MSFullscreenChange', handleFullscreenChange);

function handleFullscreenChange() {
    const container = document.querySelector('.container');
    const fullscreenIcon = document.getElementById('fullscreenIcon');
    const exitFullscreenIcon = document.getElementById('exitFullscreenIcon');

    // Si on n'est plus en plein écran navigateur mais que la classe est encore là
    if (!document.fullscreenElement &&
        !document.webkitFullscreenElement &&
        !document.mozFullScreenElement &&
        !document.msFullscreenElement) {

        if (container.classList.contains('fullscreen-mode')) {
            container.classList.remove('fullscreen-mode');
            fullscreenIcon.style.display = 'block';
            exitFullscreenIcon.style.display = 'none';

            // Mise à jour des graphiques
            setTimeout(() => {
                if (mainChart) mainChart.resize();
                if (repartitionChart) repartitionChart.resize();
                if (tauxChart) tauxChart.resize();
                calculerTout();
            }, 100);
        }
    }
}

// Raccourci clavier F11 pour basculer le mode plein écran
document.addEventListener('keydown', function(e) {
    if (e.key === 'F11') {
        e.preventDefault();
        toggleFullscreen();
    }
});