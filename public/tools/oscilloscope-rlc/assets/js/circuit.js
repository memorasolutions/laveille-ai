/**
 * Circuit.js - Calculs des circuits RLC
 * Classe Complex et fonctions de calcul
 */

// ========== CLASSE COMPLEX ==========
class Complex {
    constructor(real = 0, imag = 0) {
        this.real = real;
        this.imag = imag;
    }

    // Magnitude (module)
    get magnitude() {
        return Math.sqrt(this.real * this.real + this.imag * this.imag);
    }

    // Phase en radians
    get phase() {
        return Math.atan2(this.imag, this.real);
    }

    // Phase en degrés
    get phaseDegrees() {
        return this.phase * 180 / Math.PI;
    }

    // Addition
    add(other) {
        if (typeof other === 'number') {
            return new Complex(this.real + other, this.imag);
        }
        return new Complex(this.real + other.real, this.imag + other.imag);
    }

    // Soustraction
    sub(other) {
        if (typeof other === 'number') {
            return new Complex(this.real - other, this.imag);
        }
        return new Complex(this.real - other.real, this.imag - other.imag);
    }

    // Multiplication
    mul(other) {
        if (typeof other === 'number') {
            return new Complex(this.real * other, this.imag * other);
        }
        return new Complex(
            this.real * other.real - this.imag * other.imag,
            this.real * other.imag + this.imag * other.real
        );
    }

    // Division
    div(other) {
        if (typeof other === 'number') {
            return new Complex(this.real / other, this.imag / other);
        }
        const denom = other.real * other.real + other.imag * other.imag;
        if (denom === 0) {
            return new Complex(Infinity, Infinity);
        }
        return new Complex(
            (this.real * other.real + this.imag * other.imag) / denom,
            (this.imag * other.real - this.real * other.imag) / denom
        );
    }

    // Inverse (1/z)
    reciprocal() {
        const denom = this.real * this.real + this.imag * this.imag;
        if (denom === 0) {
            return new Complex(Infinity, 0);
        }
        return new Complex(this.real / denom, -this.imag / denom);
    }

    // Copie
    clone() {
        return new Complex(this.real, this.imag);
    }

    // Représentation string
    toString() {
        const sign = this.imag >= 0 ? '+' : '-';
        return `${this.real.toFixed(4)} ${sign} j${Math.abs(this.imag).toFixed(4)}`;
    }
}

// ========== FORMATAGE NOTATION INGÉNIEUR ==========
const ENGINEERING_PREFIXES = {
    12: 'T',   // Tera
    9: 'G',    // Giga
    6: 'M',    // Mega
    3: 'k',    // Kilo
    0: '',     // Unité
    '-3': 'm', // Milli
    '-6': 'μ', // Micro
    '-9': 'n', // Nano
    '-12': 'p' // Pico
};

function formatEngineering(value, unit, decimals = 3) {
    if (value === 0 || !isFinite(value)) {
        return `0.000 ${unit}`;
    }

    const absValue = Math.abs(value);
    let exponent = Math.floor(Math.log10(absValue));

    // Arrondir à l'exposant multiple de 3 le plus proche
    let engExponent = Math.floor(exponent / 3) * 3;

    // Limiter aux préfixes disponibles
    engExponent = Math.max(-12, Math.min(12, engExponent));

    const mantissa = value / Math.pow(10, engExponent);
    const prefix = ENGINEERING_PREFIXES[engExponent] || '';

    return `${mantissa.toFixed(decimals)} ${prefix}${unit}`;
}

// ========== CALCUL DU CIRCUIT ==========
function calculateCircuit(params) {
    const { circuitType, voltage, frequency, resistance, inductance, capacitance, targetPF = 0.95 } = params;

    // Pulsation angulaire
    const omega = 2 * Math.PI * frequency;

    // Réactances
    const XL = omega * inductance;           // Réactance inductive
    const XC = inductance > 0 || capacitance > 0
        ? (capacitance > 0 ? 1 / (omega * capacitance) : 0)
        : 0; // Réactance capacitive

    // Impédances des composants
    const ZR = new Complex(resistance, 0);
    const ZL = new Complex(0, XL);
    const ZC = new Complex(0, -XC);

    // Tension source (complexe, phase 0)
    const Vs = new Complex(voltage, 0);

    // Variables pour les résultats
    let Z_total, I_source;
    let V_R, I_R, V_L, I_L, V_C, I_C;

    // Déterminer les composants présents
    // Extraire le préfixe avant serie/series/parallel/parallèle
    // Ex: "rl_series" → "rl", "RC parallèle" → "rc"
    const typeLower = circuitType.toLowerCase();
    const prefix = typeLower.split(/[-_ ]?(serie|series|parallel|parallèle|parallele)/i)[0].trim();
    const hasR = prefix.includes('r');
    const hasL = prefix.includes('l');
    const hasC = prefix.includes('c');
    const isSeries = typeLower.includes('serie') || typeLower.includes('series');

    if (isSeries) {
        // Circuit série : Z_total = ZR + ZL + ZC
        Z_total = new Complex(0, 0);
        if (hasR) Z_total = Z_total.add(ZR);
        if (hasL) Z_total = Z_total.add(ZL);
        if (hasC) Z_total = Z_total.add(ZC);

        // Courant commun
        I_source = Vs.div(Z_total);

        // Tensions sur chaque composant
        if (hasR) {
            V_R = I_source.mul(ZR);
            I_R = I_source.clone();
        }
        if (hasL) {
            V_L = I_source.mul(ZL);
            I_L = I_source.clone();
        }
        if (hasC) {
            V_C = I_source.mul(ZC);
            I_C = I_source.clone();
        }
    } else {
        // Circuit parallèle : Y_total = 1/ZR + 1/ZL + 1/ZC
        let Y_total = new Complex(0, 0);

        if (hasR && resistance > 0) {
            Y_total = Y_total.add(ZR.reciprocal());
        }
        if (hasL && inductance > 0) {
            Y_total = Y_total.add(ZL.reciprocal());
        }
        if (hasC && capacitance > 0) {
            Y_total = Y_total.add(ZC.reciprocal());
        }

        Z_total = Y_total.reciprocal();

        // Courant source
        I_source = Vs.div(Z_total);

        // Tension commune = tension source
        // Courants dans chaque branche
        if (hasR && resistance > 0) {
            V_R = Vs.clone();
            I_R = Vs.div(ZR);
        }
        if (hasL && inductance > 0) {
            V_L = Vs.clone();
            I_L = Vs.div(ZL);
        }
        if (hasC && capacitance > 0) {
            V_C = Vs.clone();
            I_C = Vs.div(ZC);
        }
    }

    // Calculs de puissance détaillés
    const apparentPower = voltage * I_source.magnitude;  // S (VA)
    const phaseAngle = Z_total.phaseDegrees;
    const phaseRad = Z_total.phase;
    const powerFactor = Math.cos(phaseRad);
    const realPower = apparentPower * powerFactor;       // P (W)
    const reactivePower = apparentPower * Math.sin(phaseRad); // Q (VAR)

    // Déterminer le type de charge
    // phaseAngle > 0 : courant en retard → charge inductive (lagging)
    // phaseAngle < 0 : courant en avance → charge capacitive (leading)
    // phaseAngle = 0 : charge purement résistive
    let loadType = 'resistive';
    let pfType = '';
    if (Math.abs(phaseAngle) > 0.1) {
        if (phaseAngle > 0) {
            loadType = 'inductive';
            pfType = ' (ind.)';
        } else {
            loadType = 'capacitive';
            pfType = ' (cap.)';
        }
    }

    // Calcul de la capacité de correction PFC
    // Pour corriger vers un FP cible (ex: 0.95), on doit compenser la puissance réactive
    const targetAngle = Math.acos(targetPF);
    const targetQ = realPower * Math.tan(targetAngle);
    const Qc = reactivePower - targetQ; // Puissance réactive du condensateur
    const Cpfc = (loadType === 'inductive' && Qc > 0)
        ? Qc / (omega * voltage * voltage)
        : 0;

    // Calcul du courant après correction
    const newS = realPower / targetPF;
    const currentBefore = I_source.magnitude;
    const currentAfter = newS / voltage;
    const currentSavings = ((currentBefore - currentAfter) / currentBefore) * 100;

    // Construire les résultats
    const results = {
        source: {
            voltage: formatEngineering(voltage, 'V'),
            current: formatEngineering(I_source.magnitude, 'A'),
            impedance: formatEngineering(Z_total.magnitude, 'Ω'),
            power: formatEngineering(apparentPower, 'VA'),
            realPower: formatEngineering(realPower, 'W'),
            reactivePower: formatEngineering(Math.abs(reactivePower), 'VAR')
        },
        // Données brutes pour le triangle des puissances
        powerAnalysis: {
            P: realPower,           // Puissance active (W)
            Q: reactivePower,       // Puissance réactive (VAR) - signée
            S: apparentPower,       // Puissance apparente (VA)
            pf: powerFactor,        // Facteur de puissance (0 à 1)
            phi: phaseAngle,        // Angle de phase (degrés)
            phiRad: phaseRad,       // Angle de phase (radians)
            loadType: loadType,     // Type de charge
            // Composants présents
            hasL: hasL,
            hasC: hasC,
            hasR: hasR,
            // Puissances réactives individuelles (pour circuits RLC)
            QL: hasL ? (isSeries ? XL * Math.pow(I_source.magnitude, 2) : Math.pow(voltage, 2) / XL) : 0,
            QC: hasC ? (isSeries ? XC * Math.pow(I_source.magnitude, 2) : Math.pow(voltage, 2) / XC) : 0,
            XL: XL,
            XC: XC,
            // Correction PFC
            targetPF: targetPF,
            Qc: Qc,                 // Q du condensateur de correction
            Cpfc: Cpfc,             // Capacité de correction (F)
            newQ: targetQ,          // Nouvelle puissance réactive après correction
            newS: newS,             // Nouvelle puissance apparente
            currentBefore: currentBefore,   // Courant avant correction
            currentAfter: currentAfter,     // Courant après correction
            currentSavings: currentSavings  // Économie de courant (%)
        },
        powerFactor: `${(Math.abs(powerFactor) * 100).toFixed(1)} %${pfType}`,
        phaseAngle: `${Math.abs(phaseAngle).toFixed(2)}°${pfType}`,
        signals: {
            'V_Source': { magnitude: voltage, phase: 0 },
            'I_Source': { magnitude: I_source.magnitude, phase: I_source.phaseDegrees }
        }
    };

    // Ajouter les résultats par composant
    if (hasR) {
        const PR = V_R ? V_R.magnitude * I_R.magnitude * Math.cos(V_R.phase - I_R.phase) : 0;
        results.resistor = {
            voltage: V_R ? formatEngineering(V_R.magnitude, 'V') : '-',
            current: I_R ? formatEngineering(I_R.magnitude, 'A') : '-',
            impedance: formatEngineering(resistance, 'Ω'),
            power: formatEngineering(PR, 'W')
        };
        if (V_R) {
            results.signals['V_R'] = { magnitude: V_R.magnitude, phase: V_R.phaseDegrees };
            results.signals['I_R'] = { magnitude: I_R.magnitude, phase: I_R.phaseDegrees };
        }
    }

    if (hasL) {
        results.inductor = {
            voltage: V_L ? formatEngineering(V_L.magnitude, 'V') : '-',
            current: I_L ? formatEngineering(I_L.magnitude, 'A') : '-',
            reactance: formatEngineering(XL, 'Ω'),
            power: '0.000 W' // Inductance idéale ne dissipe pas
        };
        if (V_L) {
            results.signals['V_L'] = { magnitude: V_L.magnitude, phase: V_L.phaseDegrees };
            results.signals['I_L'] = { magnitude: I_L.magnitude, phase: I_L.phaseDegrees };
        }
    }

    if (hasC) {
        results.capacitor = {
            voltage: V_C ? formatEngineering(V_C.magnitude, 'V') : '-',
            current: I_C ? formatEngineering(I_C.magnitude, 'A') : '-',
            reactance: formatEngineering(XC, 'Ω'),
            power: '0.000 W' // Capacitance idéale ne dissipe pas
        };
        if (V_C) {
            results.signals['V_C'] = { magnitude: V_C.magnitude, phase: V_C.phaseDegrees };
            results.signals['I_C'] = { magnitude: I_C.magnitude, phase: I_C.phaseDegrees };
        }
    }

    return results;
}

// ========== GÉNÉRATION DES DONNÉES DE FORME D'ONDE ==========
function generateWaveformData(signal, frequency, timePerDiv, numDivisions, phaseOffset = 0) {
    const totalTime = timePerDiv * numDivisions;
    const numPoints = Math.max(200, Math.min(1000, Math.round(totalTime * frequency * 100)));

    const data = [];
    const omega = 2 * Math.PI * frequency;
    const phaseRad = (signal.phase + phaseOffset) * Math.PI / 180;
    const amplitude = signal.magnitude * Math.SQRT2; // RMS vers peak

    for (let i = 0; i <= numPoints; i++) {
        const t = (i / numPoints) * totalTime;
        let y = amplitude * Math.sin(omega * t + phaseRad);

        // Clipping réaliste si amplitude trop grande
        const maxClip = amplitude * 1.2;
        y = Math.max(-maxClip, Math.min(maxClip, y));

        data.push({ t, y });
    }

    return data;
}

// ========== EXPORTS ==========
window.Complex = Complex;
window.formatEngineering = formatEngineering;
window.calculateCircuit = calculateCircuit;
window.generateWaveformData = generateWaveformData;
