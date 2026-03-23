/**
 * App.js - Application principale
 * Orchestration de tous les composants
 * Version 100% client-side avec IndexedDB
 */

// ========== NOTATION INGENIEUR ==========
const EngineeringNotation = {
    prefixes: {
        'T': 1e12, 'G': 1e9, 'M': 1e6, 'k': 1e3, '': 1,
        'm': 1e-3, 'μ': 1e-6, 'u': 1e-6, 'n': 1e-9, 'p': 1e-12
    },

    parse(value) {
        if (typeof value === 'number') return value;
        if (!value || value === '') return 0;

        const str = String(value).trim().replace(',', '.');
        const match = str.match(/^(-?\d*\.?\d+)\s*([TGMkmuμnp]?)$/i);
        if (!match) {
            const num = parseFloat(str);
            return isNaN(num) ? 0 : num;
        }

        const num = parseFloat(match[1]);
        let prefix = match[2];
        if (prefix === 'K') prefix = 'k';
        if (prefix === 'U') prefix = 'μ';

        return num * (this.prefixes[prefix] || 1);
    },

    format(value, unit = '', decimals = 3) {
        if (value === 0 || !isFinite(value)) return `0 ${unit}`.trim();

        const absValue = Math.abs(value);
        const prefixList = [
            { exp: 12, prefix: 'T' }, { exp: 9, prefix: 'G' }, { exp: 6, prefix: 'M' },
            { exp: 3, prefix: 'k' }, { exp: 0, prefix: '' }, { exp: -3, prefix: 'm' },
            { exp: -6, prefix: 'μ' }, { exp: -9, prefix: 'n' }, { exp: -12, prefix: 'p' }
        ];

        for (const { exp, prefix } of prefixList) {
            const threshold = Math.pow(10, exp);
            if (absValue >= threshold || exp === -12) {
                const scaled = value / threshold;
                let dec = decimals;
                if (Math.abs(scaled) >= 100) dec = Math.max(0, decimals - 2);
                else if (Math.abs(scaled) >= 10) dec = Math.max(0, decimals - 1);
                return `${scaled.toFixed(dec)} ${prefix}${unit}`.trim();
            }
        }
        return `${value.toExponential(decimals)} ${unit}`.trim();
    }
};

window.EngineeringNotation = EngineeringNotation;

// ========== TOAST NOTIFICATIONS ==========
const Toast = {
    container: null,

    init() {
        this.container = document.getElementById('toast-container');
    },

    show(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const iconSvg = type === 'success'
            ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
            : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';

        toast.innerHTML = `
            <span class="toast-icon">${iconSvg}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" aria-label="Fermer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        `;

        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => this.hide(toast));

        this.container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => this.hide(toast), duration);
        }

        return toast;
    },

    hide(toast) {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 200);
    },

    success(message) { return this.show(message, 'success'); },
    error(message) { return this.show(message, 'error'); }
};

// ========== APPLICATION PRINCIPALE ==========
document.addEventListener('DOMContentLoaded', async () => {
    // Initialiser Toast
    Toast.init();

    // Initialiser Storage (IndexedDB)
    await Storage.init();
    await Storage.loadCache();

    // Application state
    const app = {
        oscilloscope: null,
        currentResults: null,
        currentCircuitId: null,
        initializing: true
    };

    // Initialiser l'oscilloscope
    app.oscilloscope = new Oscilloscope('oscilloscope');

    // Detecter iframe
    if (window.parent !== window) {
        document.body.classList.add('in-iframe');
    }

    // ========== ELEMENTS DOM ==========
    const elements = {
        // Inputs
        circuitType: document.getElementById('circuit-type'),
        voltage: document.getElementById('voltage'),
        frequency: document.getElementById('frequency'),
        resistance: document.getElementById('resistance'),
        inductance: document.getElementById('inductance'),
        capacitance: document.getElementById('capacitance'),

        // Selecteurs d'unites
        voltageUnit: document.getElementById('voltage-unit'),
        frequencyUnit: document.getElementById('frequency-unit'),
        resistanceUnit: document.getElementById('resistance-unit'),
        inductanceUnit: document.getElementById('inductance-unit'),
        capacitanceUnit: document.getElementById('capacitance-unit'),

        // Groupes de composants
        groupResistance: document.getElementById('group-resistance'),
        groupInductance: document.getElementById('group-inductance'),
        groupCapacitance: document.getElementById('group-capacitance'),

        // Controles oscilloscope
        timeDiv: document.getElementById('time-div'),
        phaseOffset: document.getElementById('phase-offset'),
        phaseValue: document.getElementById('phase-value'),

        // Canaux
        channels: {
            1: { enabled: document.getElementById('channel-1-enabled'), signal: document.getElementById('channel-1-signal'), scale: document.getElementById('channel-1-scale'), offset: document.getElementById('channel-1-offset') },
            2: { enabled: document.getElementById('channel-2-enabled'), signal: document.getElementById('channel-2-signal'), scale: document.getElementById('channel-2-scale'), offset: document.getElementById('channel-2-offset') },
            3: { enabled: document.getElementById('channel-3-enabled'), signal: document.getElementById('channel-3-signal'), scale: document.getElementById('channel-3-scale'), offset: document.getElementById('channel-3-offset') },
            4: { enabled: document.getElementById('channel-4-enabled'), signal: document.getElementById('channel-4-signal'), scale: document.getElementById('channel-4-scale'), offset: document.getElementById('channel-4-offset') }
        },

        // Resultats
        results: {
            voltage: document.getElementById('result-voltage'),
            current: document.getElementById('result-current'),
            impedance: document.getElementById('result-impedance'),
            power: document.getElementById('result-power'),
            pf: document.getElementById('result-pf'),
            phase: document.getElementById('result-phase'),
            vr: document.getElementById('result-vr'),
            ir: document.getElementById('result-ir'),
            pr: document.getElementById('result-pr'),
            vl: document.getElementById('result-vl'),
            il: document.getElementById('result-il'),
            xl: document.getElementById('result-xl'),
            vc: document.getElementById('result-vc'),
            ic: document.getElementById('result-ic'),
            xc: document.getElementById('result-xc')
        },

        // Cards composants
        cardResistor: document.getElementById('card-resistor'),
        cardInductor: document.getElementById('card-inductor'),
        cardCapacitor: document.getElementById('card-capacitor'),

        // Menu popup
        btnMenu: document.getElementById('btn-menu'),
        menuPopup: document.getElementById('menu-popup'),
        menuOverlay: document.getElementById('menu-overlay'),
        menuTheme: document.getElementById('menu-theme'),
        themeLabel: document.getElementById('theme-label'),
        menuCapture: document.getElementById('menu-capture'),
        menuSave: document.getElementById('menu-save'),
        menuCircuits: document.getElementById('menu-circuits'),
        circuitsCount: document.getElementById('circuits-count'),
        menuExport: document.getElementById('menu-export'),
        menuImport: document.getElementById('menu-import'),

        // Plein ecran
        btnFullscreen: document.getElementById('btn-fullscreen'),

        // Normalisation amplitudes
        btnNormalize: document.getElementById('btn-normalize'),

        // Reglages grille
        btnGridSettings: document.getElementById('btn-grid-settings'),
        gridSettingsPopover: document.getElementById('grid-settings-popover'),
        btnGridReset: document.getElementById('btn-grid-reset'),
        divisionOpacity: document.getElementById('division-opacity'),
        divisionOpacityValue: document.getElementById('division-opacity-value'),
        subdivisionOpacity: document.getElementById('subdivision-opacity'),
        subdivisionOpacityValue: document.getElementById('subdivision-opacity-value'),
        axisOpacity: document.getElementById('axis-opacity'),
        axisOpacityValue: document.getElementById('axis-opacity-value'),

        // Modals
        modalSave: document.getElementById('modal-save'),
        circuitName: document.getElementById('circuit-name'),
        btnSaveConfirm: document.getElementById('btn-save-confirm'),
        btnSaveCancel: document.getElementById('btn-save-cancel'),
        modalCircuits: document.getElementById('modal-circuits'),
        circuitsList: document.getElementById('circuits-list'),
        btnCircuitsClose: document.getElementById('btn-circuits-close')
    };

    // ========== FONCTIONS UTILITAIRES ==========

    function getCircuitParams() {
        const getValue = (input, unitSelect) => {
            const value = parseFloat(input.value) || 0;
            const multiplier = parseFloat(unitSelect.value) || 1;
            return value * multiplier;
        };

        return {
            circuitType: elements.circuitType.value,
            voltage: getValue(elements.voltage, elements.voltageUnit),
            frequency: getValue(elements.frequency, elements.frequencyUnit),
            resistance: getValue(elements.resistance, elements.resistanceUnit),
            inductance: getValue(elements.inductance, elements.inductanceUnit),
            capacitance: getValue(elements.capacitance, elements.capacitanceUnit)
        };
    }

    function parseCircuitType(type) {
        const typeLower = type.toLowerCase();
        const prefix = typeLower.split(/[-_ ]?(serie|series|parallel|parallèle|parallele)/i)[0].trim();
        return {
            hasR: prefix.includes('r'),
            hasL: prefix.includes('l'),
            hasC: prefix.includes('c'),
            isSeries: typeLower.includes('serie') || typeLower.includes('series')
        };
    }

    function updateComponentVisibility() {
        const type = elements.circuitType.value;
        const { hasR, hasL, hasC } = parseCircuitType(type);

        elements.groupResistance.style.display = 'block';
        elements.groupInductance.style.display = hasL ? 'block' : 'none';
        elements.groupCapacitance.style.display = hasC ? 'block' : 'none';

        if (elements.cardResistor) elements.cardResistor.classList.toggle('hidden', false);
        if (elements.cardInductor) elements.cardInductor.classList.toggle('hidden', !hasL);
        if (elements.cardCapacitor) elements.cardCapacitor.classList.toggle('hidden', !hasC);
    }

    function updateChannelOptions() {
        const type = elements.circuitType.value;
        const { hasR, hasL, hasC } = parseCircuitType(type);

        const signals = [
            { value: '', label: '---' },
            { value: 'V_Source', label: 'V Source' },
            { value: 'I_Source', label: 'I Source' }
        ];

        if (hasR) {
            signals.push({ value: 'V_R', label: 'V Resistance' });
            signals.push({ value: 'I_R', label: 'I Resistance' });
        }
        if (hasL) {
            signals.push({ value: 'V_L', label: 'V Inductance' });
            signals.push({ value: 'I_L', label: 'I Inductance' });
        }
        if (hasC) {
            signals.push({ value: 'V_C', label: 'V Capacitance' });
            signals.push({ value: 'I_C', label: 'I Capacitance' });
        }

        for (let ch = 1; ch <= 4; ch++) {
            const select = elements.channels[ch].signal;
            const currentValue = select.value;
            select.innerHTML = signals.map(s => `<option value="${s.value}">${s.label}</option>`).join('');
            if (signals.some(s => s.value === currentValue)) {
                select.value = currentValue;
            }
        }
    }

    function calculate() {
        const params = getCircuitParams();
        // Fréquence doit être > 0 (division par zéro sinon), mais tension peut être 0
        if (params.voltage < 0 || params.frequency <= 0) return;

        app.currentResults = calculateCircuit(params);
        displayResults(app.currentResults);
        updateOscilloscope();
        Storage.setLastConfig(params);
    }

    function displayResults(results) {
        elements.results.voltage.textContent = results.source.voltage;
        elements.results.current.textContent = results.source.current;
        elements.results.impedance.textContent = results.source.impedance;
        elements.results.power.textContent = results.source.power;
        elements.results.pf.textContent = results.powerFactor;
        elements.results.phase.textContent = results.phaseAngle;

        if (results.resistor) {
            elements.results.vr.textContent = results.resistor.voltage;
            elements.results.ir.textContent = results.resistor.current;
            elements.results.pr.textContent = results.resistor.power;
        }
        if (results.inductor) {
            elements.results.vl.textContent = results.inductor.voltage;
            elements.results.il.textContent = results.inductor.current;
            elements.results.xl.textContent = results.inductor.reactance;
        }
        if (results.capacitor) {
            elements.results.vc.textContent = results.capacitor.voltage;
            elements.results.ic.textContent = results.capacitor.current;
            elements.results.xc.textContent = results.capacitor.reactance;
        }
    }

    function updateOscilloscope() {
        if (!app.currentResults) return;

        const params = getCircuitParams();
        app.oscilloscope.setSignals({ ...app.currentResults.signals });
        app.oscilloscope.setFrequency(params.frequency);
        app.oscilloscope.setTimePerDiv(parseFloat(elements.timeDiv.value));
        app.oscilloscope.setPhaseOffset(parseFloat(elements.phaseOffset.value));

        for (let ch = 1; ch <= 4; ch++) {
            const enabled = elements.channels[ch].enabled.checked;
            const signalName = elements.channels[ch].signal.value;
            const scale = parseFloat(elements.channels[ch].scale.value);
            const offset = parseFloat(elements.channels[ch].offset.value);
            app.oscilloscope.setChannel(ch, enabled && signalName ? signalName : null, scale);
            app.oscilloscope.setChannelOffset(ch, offset);
        }

        app.oscilloscope.draw();

        if (!app.initializing) {
            Storage.setOscilloscopeConfig({
                timePerDiv: parseFloat(elements.timeDiv.value),
                phaseOffset: parseFloat(elements.phaseOffset.value),
                channels: {
                    1: { enabled: elements.channels[1].enabled.checked, signal: elements.channels[1].signal.value, scale: parseFloat(elements.channels[1].scale.value), offset: parseFloat(elements.channels[1].offset.value) },
                    2: { enabled: elements.channels[2].enabled.checked, signal: elements.channels[2].signal.value, scale: parseFloat(elements.channels[2].scale.value), offset: parseFloat(elements.channels[2].offset.value) },
                    3: { enabled: elements.channels[3].enabled.checked, signal: elements.channels[3].signal.value, scale: parseFloat(elements.channels[3].scale.value), offset: parseFloat(elements.channels[3].offset.value) },
                    4: { enabled: elements.channels[4].enabled.checked, signal: elements.channels[4].signal.value, scale: parseFloat(elements.channels[4].scale.value), offset: parseFloat(elements.channels[4].offset.value) }
                }
            });
        }
    }

    function loadCircuitConfig(config) {
        if (!config) return;

        elements.circuitType.value = config.circuit_type || config.circuitType || 'rlc_parallel';

        const setValueWithUnit = (input, unitSelect, value) => {
            if (!value || value === 0) { input.value = 0; return; }

            const options = Array.from(unitSelect.options);
            let bestOption = options[0];
            let bestValue = value / parseFloat(options[0].value);

            for (const option of options) {
                const multiplier = parseFloat(option.value);
                const displayValue = value / multiplier;
                if (displayValue >= 1 && displayValue < 1000) {
                    bestOption = option;
                    bestValue = displayValue;
                    break;
                }
                if (Math.abs(displayValue) >= 0.1 && Math.abs(displayValue) < 10000) {
                    if (Math.abs(displayValue - 100) < Math.abs(bestValue - 100)) {
                        bestOption = option;
                        bestValue = displayValue;
                    }
                }
            }

            unitSelect.value = bestOption.value;
            input.value = parseFloat(bestValue.toPrecision(6));
        };

        setValueWithUnit(elements.voltage, elements.voltageUnit, config.voltage || 120);
        setValueWithUnit(elements.frequency, elements.frequencyUnit, config.frequency || 60);
        setValueWithUnit(elements.resistance, elements.resistanceUnit, config.resistance || 100);
        setValueWithUnit(elements.inductance, elements.inductanceUnit, config.inductance || 0.1);
        setValueWithUnit(elements.capacitance, elements.capacitanceUnit, config.capacitance || 0.0001);

        updateComponentVisibility();
        updateChannelOptions();
        calculate();
    }

    // ========== MENU POPUP ==========

    function toggleMenu(show) {
        const isVisible = show !== undefined ? show : !elements.menuPopup.classList.contains('visible');
        elements.menuPopup.classList.toggle('visible', isVisible);
        elements.menuOverlay.classList.toggle('visible', isVisible);
        elements.btnMenu.setAttribute('aria-expanded', isVisible);
    }

    function toggleTheme() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        html.setAttribute('data-theme', newTheme);
        Storage.setTheme(newTheme);
        elements.themeLabel.textContent = newTheme === 'dark' ? 'Mode clair' : 'Mode sombre';

        updateGridSettingsUI();
        if (app.oscilloscope) app.oscilloscope.draw();
    }

    function captureOscilloscope() {
        const timestamp = new Date().toISOString().slice(0, 19).replace(/[:-]/g, '');
        app.oscilloscope.exportPNG(`oscilloscope_${timestamp}.png`);
        Toast.success('Capture sauvegardée');
        toggleMenu(false);
    }

    async function updateCircuitsCount() {
        const circuits = await Storage.getCircuits();
        elements.circuitsCount.textContent = circuits.length;
        elements.circuitsCount.dataset.count = circuits.length;
    }

    // ========== MODAL SAUVEGARDE ==========

    function openSaveModal() {
        elements.circuitName.value = '';
        elements.modalSave.showModal();
        toggleMenu(false);
    }

    async function saveCurrentCircuit() {
        const name = elements.circuitName.value.trim();
        if (!name) {
            Toast.error('Veuillez entrer un nom pour le circuit');
            return;
        }

        const params = getCircuitParams();
        const circuitData = {
            name,
            circuit_type: params.circuitType,
            voltage: params.voltage,
            frequency: params.frequency,
            resistance: params.resistance,
            inductance: params.inductance,
            capacitance: params.capacitance,
            oscilloscope_config: Storage.getOscilloscopeConfig()
        };

        try {
            await Storage.saveCircuit(circuitData);
            elements.modalSave.close();
            Toast.success('Circuit sauvegardé');
            updateCircuitsCount();
        } catch (error) {
            Toast.error('Erreur : ' + error.message);
        }
    }

    // ========== MODAL CIRCUITS ==========

    async function openCircuitsModal() {
        const circuits = await Storage.getCircuits();
        toggleMenu(false);

        if (circuits.length === 0) {
            elements.circuitsList.innerHTML = `
                <div class="circuits-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <p>Aucun circuit sauvegardé</p>
                </div>
            `;
        } else {
            elements.circuitsList.innerHTML = circuits.map(circuit => {
                const typeLabel = circuit.circuit_type.replace('_', ' ').replace('series', 'série');
                const date = new Date(circuit.created_at).toLocaleDateString('fr-FR');
                return `
                    <div class="circuit-item" data-id="${circuit.id}">
                        <div class="circuit-item-info">
                            <div class="circuit-item-name">${circuit.name}</div>
                            <div class="circuit-item-meta">${typeLabel} - ${date}</div>
                        </div>
                        <div class="circuit-item-actions">
                            <button class="circuit-item-btn btn-load" title="Charger" data-action="load">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 10 20 15 15 20"></polyline>
                                    <path d="M4 4v7a4 4 0 0 0 4 4h12"></path>
                                </svg>
                            </button>
                            <button class="circuit-item-btn btn-delete" title="Supprimer" data-action="delete">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        elements.modalCircuits.showModal();
    }

    async function handleCircuitAction(e) {
        const btn = e.target.closest('.circuit-item-btn');
        if (!btn) return;

        const item = btn.closest('.circuit-item');
        const id = parseInt(item.dataset.id);
        const action = btn.dataset.action;

        if (action === 'load') {
            const circuit = await Storage.getCircuit(id);
            if (circuit) {
                loadCircuitConfig(circuit);
                elements.modalCircuits.close();
                Toast.success(`Circuit "${circuit.name}" chargé`);
            }
        } else if (action === 'delete') {
            const name = item.querySelector('.circuit-item-name').textContent;
            if (confirm(`Supprimer "${name}" ?`)) {
                await Storage.deleteCircuit(id);
                item.remove();
                updateCircuitsCount();
                Toast.success('Circuit supprimé');

                // Verifier si liste vide
                const remaining = elements.circuitsList.querySelectorAll('.circuit-item');
                if (remaining.length === 0) {
                    elements.circuitsList.innerHTML = `
                        <div class="circuits-empty">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <p>Aucun circuit sauvegardé</p>
                        </div>
                    `;
                }
            }
        }
    }

    // ========== EXPORT / IMPORT ==========

    async function exportCircuits() {
        try {
            await Storage.downloadCircuits();
            Toast.success('Circuits exportés');
            toggleMenu(false);
        } catch (error) {
            Toast.error('Erreur export : ' + error.message);
        }
    }

    async function importCircuits() {
        try {
            const count = await Storage.openImportDialog();
            if (count > 0) {
                Toast.success(`${count} circuit(s) importé(s)`);
                updateCircuitsCount();
            }
            toggleMenu(false);
        } catch (error) {
            Toast.error('Erreur import : ' + error.message);
        }
    }

    // ========== EVENT LISTENERS ==========

    // Type de circuit
    elements.circuitType.addEventListener('change', () => {
        updateComponentVisibility();
        updateChannelOptions();
        calculate();
    });

    // Inputs parametres
    [elements.voltage, elements.frequency, elements.resistance, elements.inductance, elements.capacitance]
        .forEach(input => input.addEventListener('input', calculate));

    // Selecteurs unites
    [elements.voltageUnit, elements.frequencyUnit, elements.resistanceUnit, elements.inductanceUnit, elements.capacitanceUnit]
        .forEach(select => select.addEventListener('change', calculate));

    // Controles oscilloscope
    elements.timeDiv.addEventListener('change', updateOscilloscope);
    elements.phaseOffset.addEventListener('input', (e) => {
        elements.phaseValue.textContent = `${e.target.value}°`;
        updateOscilloscope();
    });

    // Canaux
    for (let ch = 1; ch <= 4; ch++) {
        elements.channels[ch].enabled.addEventListener('change', updateOscilloscope);
        elements.channels[ch].signal.addEventListener('change', updateOscilloscope);
        elements.channels[ch].scale.addEventListener('change', updateOscilloscope);
        elements.channels[ch].offset.addEventListener('input', updateOscilloscope);

        // Indicateur de position centrale pour le slider d'offset
        elements.channels[ch].offset.addEventListener('input', (e) => {
            const slider = e.target;
            const value = parseFloat(slider.value);
            slider.classList.toggle('at-center', value === 0);
        });

        // Double-clic pour recentrer
        elements.channels[ch].offset.addEventListener('dblclick', (e) => {
            const slider = e.target;
            slider.value = 0;
            slider.classList.add('at-center');
            slider.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    // Boutons reset pour recentrer les offsets
    document.querySelectorAll('.offset-reset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const ch = btn.dataset.channel;
            const slider = elements.channels[ch].offset;
            slider.value = 0;
            slider.classList.add('at-center');
            slider.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    // Boutons paramètres de trace (gear)
    document.querySelectorAll('.channel-settings-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const ch = btn.dataset.channel;
            const popover = document.querySelector(`.channel-settings-popover[data-channel="${ch}"]`);

            // Fermer les autres popovers
            document.querySelectorAll('.channel-settings-popover.active').forEach(p => {
                if (p !== popover) p.classList.remove('active');
            });

            popover.classList.toggle('active');
        });
    });

    // Sélection de couleur dans les popovers
    document.querySelectorAll('.channel-settings-popover .color-swatch').forEach(swatch => {
        swatch.addEventListener('click', (e) => {
            e.stopPropagation();
            const popover = swatch.closest('.channel-settings-popover');
            const ch = popover.dataset.channel;
            const color = swatch.dataset.color;

            // Marquer le swatch actif
            popover.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
            swatch.classList.add('active');

            // Mettre à jour l'indicateur de couleur
            const indicator = document.querySelector(`.channel-settings-btn[data-channel="${ch}"] .channel-color-indicator`);
            indicator.style.backgroundColor = color;

            // Mettre à jour la couleur dans l'oscilloscope
            app.oscilloscope.setChannelColor(ch, color);
        });
    });

    // Toggles de marqueurs de phase (système FIFO - max 2 canaux)
    // File d'attente pour garder l'ordre de sélection
    let phaseMarkerQueue = [1, 2]; // Canaux par défaut (CH1 et CH2 cochés)

    document.querySelectorAll('.phase-marker-toggle').forEach(toggle => {
        toggle.addEventListener('change', (e) => {
            const ch = parseInt(e.target.dataset.channel, 10);

            if (e.target.checked) {
                // Ajouter le canal à la file
                if (!phaseMarkerQueue.includes(ch)) {
                    phaseMarkerQueue.push(ch);
                }

                // Si plus de 2 canaux, retirer le plus ancien (FIFO)
                if (phaseMarkerQueue.length > 2) {
                    const oldestChannel = phaseMarkerQueue.shift();
                    // Décocher le canal le plus ancien
                    const oldToggle = document.querySelector(`.phase-marker-toggle[data-channel="${oldestChannel}"]`);
                    if (oldToggle) {
                        oldToggle.checked = false;
                    }
                }
            } else {
                // Retirer le canal de la file
                phaseMarkerQueue = phaseMarkerQueue.filter(c => c !== ch);
            }

            // Mettre à jour l'oscilloscope
            app.oscilloscope.setPhaseMarkerChannels([...phaseMarkerQueue]);
            app.oscilloscope.setShowPhaseMarkers(phaseMarkerQueue.length >= 2);
        });
    });

    // Fermer les popovers en cliquant ailleurs
    document.addEventListener('click', () => {
        document.querySelectorAll('.channel-settings-popover.active').forEach(p => {
            p.classList.remove('active');
        });
    });

    // Menu popup
    elements.btnMenu.addEventListener('click', () => toggleMenu());
    elements.menuOverlay.addEventListener('click', () => toggleMenu(false));
    elements.menuTheme.addEventListener('click', toggleTheme);
    elements.menuCapture.addEventListener('click', captureOscilloscope);
    elements.menuSave.addEventListener('click', openSaveModal);
    elements.menuCircuits.addEventListener('click', openCircuitsModal);
    elements.menuExport.addEventListener('click', exportCircuits);
    elements.menuImport.addEventListener('click', importCircuits);

    // Fermer menu avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (elements.menuPopup.classList.contains('visible')) {
                toggleMenu(false);
            }
        }
    });

    // Plein ecran
    elements.btnFullscreen.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    });

    // Normalisation des amplitudes
    elements.btnNormalize.addEventListener('click', () => {
        app.oscilloscope.normalizeAmplitudes = !app.oscilloscope.normalizeAmplitudes;
        elements.btnNormalize.setAttribute('aria-pressed', app.oscilloscope.normalizeAmplitudes);
        app.oscilloscope.draw();
    });

    // Modal sauvegarde
    elements.btnSaveConfirm.addEventListener('click', saveCurrentCircuit);
    elements.btnSaveCancel.addEventListener('click', () => elements.modalSave.close());
    elements.circuitName.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') saveCurrentCircuit();
    });

    // Modal circuits
    elements.circuitsList.addEventListener('click', handleCircuitAction);
    elements.btnCircuitsClose.addEventListener('click', () => elements.modalCircuits.close());

    // Sections collapsibles
    document.querySelectorAll('.card-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const isCollapsed = toggle.getAttribute('data-collapsed') === 'true';
            toggle.setAttribute('data-collapsed', !isCollapsed);
        });
    });

    // ========== REGLAGES GRILLE ==========

    elements.btnGridSettings.addEventListener('click', (e) => {
        e.stopPropagation();
        const isVisible = elements.gridSettingsPopover.classList.toggle('visible');
        elements.btnGridSettings.setAttribute('aria-expanded', isVisible);
    });

    document.addEventListener('click', (e) => {
        if (!elements.gridSettingsPopover.contains(e.target) && e.target !== elements.btnGridSettings) {
            elements.gridSettingsPopover.classList.remove('visible');
            elements.btnGridSettings.setAttribute('aria-expanded', 'false');
        }
    });

    // Gestion des onglets du popover
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;
            // Mettre à jour les onglets
            document.querySelectorAll('.settings-tab').forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            // Afficher le panel correspondant
            document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
            document.querySelector(`.settings-panel[data-panel="${tabName}"]`)?.classList.add('active');
        });
    });

    const colorConfigMap = { 'division': 'divisionColor', 'subdivision': 'subDivisionColor', 'axis': 'axisColor' };

    // Couleurs de fond disponibles
    const backgroundColors = {
        'dark': '#0d1117',
        'navy': '#1a1a2e',
        'green-dark': '#0a1612',
        'light': '#f5f5f5',
        'sepia': '#f4ecd8',
        'black': '#000000'
    };

    document.querySelectorAll('.color-presets').forEach(container => {
        const target = container.dataset.target;
        container.querySelectorAll('.color-preset').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.color-preset').forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-pressed', 'false');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');

                const configKey = colorConfigMap[target];
                if (configKey) {
                    app.oscilloscope.setGridConfig({ [configKey]: btn.dataset.color });
                    app.oscilloscope.draw();
                    saveGridConfig();
                }
            });
        });
    });

    // Gestionnaire pour les presets bicolores (fond + texte) - WCAG 2.2
    document.querySelectorAll('.color-preset-dual').forEach(btn => {
        btn.addEventListener('click', () => {
            // Mettre à jour l'état actif
            document.querySelectorAll('.color-preset-dual').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-pressed', 'true');

            // Appliquer les couleurs (fond + texte)
            const bgColor = btn.dataset.bg;
            const textColor = btn.dataset.text;
            const theme = btn.dataset.theme || 'dark';

            document.documentElement.style.setProperty('--oscilloscope-bg', bgColor);
            document.documentElement.style.setProperty('--oscilloscope-text', textColor);

            // Changer le thème de grille (dark=lignes bleues, light=lignes noires)
            document.documentElement.style.setProperty('--oscilloscope-theme', theme);

            // Appliquer automatiquement les couleurs de grille appropriées au thème
            const gridDefaults = Storage.getGridConfigDefaults();
            const themeConfig = gridDefaults[theme];
            if (themeConfig) {
                app.oscilloscope.setGridConfig(themeConfig, theme);
                // Sauvegarder la nouvelle config
                const fullConfig = app.oscilloscope.getFullGridConfig();
                Storage.setGridConfig(fullConfig);
                updateGridSettingsUI();
            }

            app.oscilloscope.draw();
            Storage.setOscilloscopeBg(btn.dataset.color);
            Storage.setOscilloscopeText(textColor);
            Storage.setOscilloscopeTheme(theme);
        });
    });

    elements.divisionOpacity.addEventListener('input', (e) => {
        const value = parseInt(e.target.value);
        elements.divisionOpacityValue.textContent = `${value}%`;
        app.oscilloscope.setGridConfig({ divisionOpacity: value });
        app.oscilloscope.draw();
    });
    elements.divisionOpacity.addEventListener('change', saveGridConfig);

    elements.subdivisionOpacity.addEventListener('input', (e) => {
        const value = parseInt(e.target.value);
        elements.subdivisionOpacityValue.textContent = `${value}%`;
        app.oscilloscope.setGridConfig({ subDivisionOpacity: value });
        app.oscilloscope.draw();
    });
    elements.subdivisionOpacity.addEventListener('change', saveGridConfig);

    elements.axisOpacity.addEventListener('input', (e) => {
        const value = parseInt(e.target.value);
        elements.axisOpacityValue.textContent = `${value}%`;
        app.oscilloscope.setGridConfig({ axisOpacity: value });
        app.oscilloscope.draw();
    });
    elements.axisOpacity.addEventListener('change', saveGridConfig);

    elements.btnGridReset.addEventListener('click', () => {
        const defaults = Storage.resetGridConfig();
        app.oscilloscope.setFullGridConfig(defaults);
        updateGridSettingsUI();
        // Réinitialiser les couleurs fond + texte + thème (WCAG 2.2)
        const defaultBtn = document.querySelector('.color-preset-dual[data-color="light"]');
        if (defaultBtn) {
            document.documentElement.style.setProperty('--oscilloscope-bg', defaultBtn.dataset.bg);
            document.documentElement.style.setProperty('--oscilloscope-text', defaultBtn.dataset.text);
            document.documentElement.style.setProperty('--oscilloscope-theme', defaultBtn.dataset.theme || 'light');
            Storage.setOscilloscopeBg('light');
            Storage.setOscilloscopeText(defaultBtn.dataset.text);
            Storage.setOscilloscopeTheme('light');
            // Mettre à jour l'état des boutons
            document.querySelectorAll('.color-preset-dual').forEach(btn => {
                const isActive = btn.dataset.color === 'light';
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }
        app.oscilloscope.draw();
    });

    function saveGridConfig() {
        Storage.setGridConfig(app.oscilloscope.getFullGridConfig());
    }

    function updateGridSettingsUI() {
        const config = app.oscilloscope.getGridConfig();
        elements.axisOpacity.value = config.axisOpacity;
        elements.axisOpacityValue.textContent = `${config.axisOpacity}%`;
        elements.divisionOpacity.value = config.divisionOpacity;
        elements.divisionOpacityValue.textContent = `${config.divisionOpacity}%`;
        elements.subdivisionOpacity.value = config.subDivisionOpacity;
        elements.subdivisionOpacityValue.textContent = `${config.subDivisionOpacity}%`;

        const colorMap = { 'axis': config.axisColor, 'division': config.divisionColor, 'subdivision': config.subDivisionColor };
        Object.entries(colorMap).forEach(([target, color]) => {
            document.querySelectorAll(`.color-presets[data-target="${target}"] .color-preset`).forEach(btn => {
                const isActive = btn.dataset.color === color;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        });
    }

    // ========== INITIALISATION ==========

    // Theme
    const savedTheme = Storage.getTheme();
    document.documentElement.setAttribute('data-theme', savedTheme);
    elements.themeLabel.textContent = savedTheme === 'dark' ? 'Mode clair' : 'Mode sombre';

    // Config grille
    const fullGridConfig = Storage.getGridConfig();
    app.oscilloscope.setFullGridConfig(fullGridConfig);
    updateGridSettingsUI();

    // Couleur de fond et texte de l'oscilloscope (WCAG 2.2)
    const savedBg = Storage.getOscilloscopeBg();
    const savedText = Storage.getOscilloscopeText();
    const savedOscTheme = Storage.getOscilloscopeTheme();

    // Appliquer les couleurs et le thème de grille sauvegardés
    const bgBtn = document.querySelector(`.color-preset-dual[data-color="${savedBg}"]`);
    if (bgBtn) {
        document.documentElement.style.setProperty('--oscilloscope-bg', bgBtn.dataset.bg);
        document.documentElement.style.setProperty('--oscilloscope-text', bgBtn.dataset.text);
        document.documentElement.style.setProperty('--oscilloscope-theme', bgBtn.dataset.theme || 'dark');
        bgBtn.classList.add('active');
        bgBtn.setAttribute('aria-pressed', 'true');
    } else {
        // Fallback si pas de bouton correspondant
        if (savedText) {
            document.documentElement.style.setProperty('--oscilloscope-text', savedText);
        }
        document.documentElement.style.setProperty('--oscilloscope-theme', savedOscTheme);
    }

    // Config oscilloscope
    const oscConfig = Storage.getOscilloscopeConfig();
    elements.timeDiv.value = oscConfig.timePerDiv;
    elements.phaseOffset.value = oscConfig.phaseOffset;
    elements.phaseValue.textContent = `${oscConfig.phaseOffset}°`;

    // Derniere config circuit
    const lastConfig = Storage.getLastConfig();
    if (lastConfig) {
        loadCircuitConfig(lastConfig);
    } else {
        updateComponentVisibility();
        updateChannelOptions();
    }

    // Restaurer canaux
    if (oscConfig.channels) {
        for (let ch = 1; ch <= 4; ch++) {
            if (oscConfig.channels[ch]) {
                elements.channels[ch].enabled.checked = oscConfig.channels[ch].enabled !== false;
                elements.channels[ch].signal.value = oscConfig.channels[ch].signal || '';
                elements.channels[ch].scale.value = oscConfig.channels[ch].scale || 50;
                elements.channels[ch].offset.value = oscConfig.channels[ch].offset || 0;
            }
        }
    } else {
        elements.channels[1].signal.value = 'V_Source';
        elements.channels[2].signal.value = 'I_Source';
    }

    // Premier calcul
    calculate();

    // Compteur circuits
    updateCircuitsCount();

    // Fin initialisation
    app.initializing = false;

    console.log('Oscilloscope RLC initialisé (client-side)');
});
