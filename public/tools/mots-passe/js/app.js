/**
 * Générateur de Mots de Passe Pro
 * Application principale - Modulaire
 */

// État global
const state = {
    length: 16,
    isAdjustingLength: false,
    autoAdjustLength: true,
    charTypes: {
        uppercase: { enabled: true, quantity: 4, active: false },
        lowercase: { enabled: true, quantity: 4, active: false },
        numbers: { enabled: true, quantity: 4, active: false },
        symbols: { enabled: true, quantity: 4, active: false }
    }
};

const charSets = {
    uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    lowercase: 'abcdefghijklmnopqrstuvwxyz',
    numbers: '0123456789',
    symbols: '!@#$%^&*()_+-=[]{}|;:,.<>?'
};

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    const savedAutoAdjust = localStorage.getItem('autoAdjustLength');
    if (savedAutoAdjust !== null) {
        state.autoAdjustLength = savedAutoAdjust === 'true';
        document.getElementById('autoAdjustLength').checked = state.autoAdjustLength;
    }

    initializeInterface();
    updateValidation();
    generatePassword();
});

function initializeInterface() {
    Object.keys(state.charTypes).forEach(type => {
        const checkbox = document.querySelector(`#char-${type} .character-checkbox`);
        const element = document.getElementById(`char-${type}`);

        if (state.charTypes[type].enabled) {
            checkbox.classList.add('checked');
            element.classList.add('enabled');
        }

        const input = document.getElementById(`qty-input-${type}`);
        const slider = document.getElementById(`qty-slider-${type}`);
        input.value = state.charTypes[type].quantity;
        slider.value = state.charTypes[type].quantity;
    });
}

function toggleCharType(type) {
    state.charTypes[type].enabled = !state.charTypes[type].enabled;

    const checkbox = document.querySelector(`#char-${type} .character-checkbox`);
    const element = document.getElementById(`char-${type}`);

    if (state.charTypes[type].enabled) {
        checkbox.classList.add('checked');
        element.classList.add('enabled');
    } else {
        checkbox.classList.remove('checked');
        element.classList.remove('enabled');
        state.charTypes[type].quantity = 0;
        document.getElementById(`qty-input-${type}`).value = 0;
        document.getElementById(`qty-slider-${type}`).value = 0;
    }

    updateQuantityRanges();
    updateValidation();
    generatePassword();
}

function toggleQuantityControl(type) {
    const controls = document.getElementById(`qty-${type}`);
    const gearBtn = document.querySelector(`#char-${type} .gear-btn`);

    state.charTypes[type].active = !state.charTypes[type].active;

    if (state.charTypes[type].active) {
        controls.classList.add('active');
        gearBtn.classList.add('active');
    } else {
        controls.classList.remove('active');
        gearBtn.classList.remove('active');
    }
}

function updateQuantity(type) {
    const input = document.getElementById(`qty-input-${type}`);
    const slider = document.getElementById(`qty-slider-${type}`);

    let value = parseInt(input.value) || 0;
    value = Math.max(0, Math.min(value, state.length));

    state.charTypes[type].quantity = value;
    input.value = value;
    slider.value = value;

    adjustLengthToQuantities();
    updateQuantityRanges();
    updateValidation();
    generatePassword();
}

function updateQuantityFromSlider(type) {
    const input = document.getElementById(`qty-input-${type}`);
    const slider = document.getElementById(`qty-slider-${type}`);

    const value = parseInt(slider.value);
    state.charTypes[type].quantity = value;
    input.value = value;

    adjustLengthToQuantities();
    updateQuantityRanges();
    updateValidation();
    generatePassword();
}

function updateLength() {
    if (state.isAdjustingLength) {
        return;
    }

    const lengthSlider = document.getElementById('length');
    const lengthValue = document.getElementById('lengthValue');

    state.length = parseInt(lengthSlider.value);
    lengthValue.textContent = state.length;

    updateQuantityRanges();

    Object.keys(state.charTypes).forEach(type => {
        if (state.charTypes[type].quantity > state.length) {
            state.charTypes[type].quantity = Math.floor(state.length / 4);
            const input = document.getElementById(`qty-input-${type}`);
            const slider = document.getElementById(`qty-slider-${type}`);
            input.value = state.charTypes[type].quantity;
            slider.value = state.charTypes[type].quantity;
        }
    });

    updateValidation();
    generatePassword();
}

function updateQuantityRanges() {
    Object.keys(state.charTypes).forEach(type => {
        const input = document.getElementById(`qty-input-${type}`);
        const slider = document.getElementById(`qty-slider-${type}`);

        if (input && slider) {
            input.max = state.length;
            slider.max = state.length;

            if (parseInt(input.value) > state.length) {
                input.value = state.length;
                slider.value = state.length;
                state.charTypes[type].quantity = state.length;
            }
        }
    });
}

function adjustLengthToQuantities() {
    if (state.isAdjustingLength) {
        return;
    }

    if (!state.autoAdjustLength) {
        return;
    }

    const enabledTypes = Object.keys(state.charTypes).filter(type => state.charTypes[type].enabled);
    const totalRequired = enabledTypes.reduce((sum, type) => sum + state.charTypes[type].quantity, 0);

    if (totalRequired === 0) {
        return;
    }

    let newLength = state.length;

    if (totalRequired !== state.length) {
        newLength = Math.max(totalRequired, 6);
    }

    newLength = Math.max(6, Math.min(50, newLength));

    if (newLength !== state.length) {
        state.isAdjustingLength = true;

        state.length = newLength;
        const lengthSlider = document.getElementById('length');
        const lengthValue = document.getElementById('lengthValue');

        if (lengthSlider && lengthValue) {
            lengthSlider.value = newLength;
            lengthValue.textContent = newLength;
        }

        updateQuantityRanges();

        if (newLength === 50 && totalRequired > 50) {
            autoAdjustQuantities();
        }

        state.isAdjustingLength = false;
    }
}

function autoAdjustQuantities() {
    const enabledTypes = Object.keys(state.charTypes).filter(type => state.charTypes[type].enabled);
    const totalSpecified = enabledTypes.reduce((sum, type) => sum + state.charTypes[type].quantity, 0);

    if (totalSpecified <= state.length) {
        return;
    }

    const overage = totalSpecified - state.length;

    const weights = {};
    let totalWeight = 0;
    enabledTypes.forEach(type => {
        weights[type] = state.charTypes[type].quantity;
        totalWeight += weights[type];
    });

    let remainingReduction = overage;
    enabledTypes.forEach(type => {
        if (remainingReduction > 0) {
            const proportionalReduction = Math.floor((weights[type] / totalWeight) * overage);
            const actualReduction = Math.min(proportionalReduction, state.charTypes[type].quantity - 1);
            state.charTypes[type].quantity -= actualReduction;
            remainingReduction -= actualReduction;
        }
    });

    while (remainingReduction > 0) {
        const sortedTypes = enabledTypes.sort((a, b) => state.charTypes[b].quantity - state.charTypes[a].quantity);

        for (const type of sortedTypes) {
            if (remainingReduction > 0 && state.charTypes[type].quantity > 1) {
                state.charTypes[type].quantity--;
                remainingReduction--;
                break;
            }
        }

        if (remainingReduction > 0 && enabledTypes.every(type => state.charTypes[type].quantity === 1)) {
            break;
        }
    }

    enabledTypes.forEach(type => {
        const input = document.getElementById(`qty-input-${type}`);
        const slider = document.getElementById(`qty-slider-${type}`);
        if (input && slider) {
            input.value = state.charTypes[type].quantity;
            slider.value = state.charTypes[type].quantity;
        }
    });
}

function updateValidation() {
    const totalSpecified = Object.values(state.charTypes)
        .filter(type => type.enabled)
        .reduce((sum, type) => sum + type.quantity, 0);

    const remaining = state.length - totalSpecified;
    const remainingInfo = document.getElementById('remainingInfo');

    Object.keys(state.charTypes).forEach(type => {
        document.getElementById(`char-${type}`).classList.remove('invalid');
    });

    if (remaining < 0) {
        autoAdjustQuantities();
        return;
    }

    if (remaining === 0) {
        remainingInfo.textContent = '\u2705 Configuration parfaite !';
        remainingInfo.className = 'remaining-info';
    } else if (remaining > 0) {
        remainingInfo.textContent = `\uD83D\uDCCD ${remaining} caractères restants (distribués aléatoirement)`;
        remainingInfo.className = 'remaining-info';
    }
}

function applyPreset(preset) {
    const presets = {
        balanced: {
            uppercase: Math.ceil(state.length * 0.25),
            lowercase: Math.ceil(state.length * 0.35),
            numbers: Math.ceil(state.length * 0.25),
            symbols: Math.ceil(state.length * 0.15)
        },
        secure: {
            uppercase: Math.ceil(state.length * 0.2),
            lowercase: Math.ceil(state.length * 0.3),
            numbers: Math.ceil(state.length * 0.2),
            symbols: Math.ceil(state.length * 0.3)
        },
        complex: {
            uppercase: Math.ceil(state.length * 0.15),
            lowercase: Math.ceil(state.length * 0.25),
            numbers: Math.ceil(state.length * 0.25),
            symbols: Math.ceil(state.length * 0.35)
        }
    };

    const config = presets[preset];
    let total = 0;

    Object.keys(config).forEach(type => {
        state.charTypes[type].quantity = config[type];
        total += config[type];
    });

    if (total !== state.length) {
        const diff = state.length - total;
        state.charTypes.lowercase.quantity += diff;
    }

    Object.keys(state.charTypes).forEach(type => {
        document.getElementById(`qty-input-${type}`).value = state.charTypes[type].quantity;
        document.getElementById(`qty-slider-${type}`).value = state.charTypes[type].quantity;
    });

    updateValidation();
    generatePassword();
}

function resetQuantities() {
    const defaultQty = Math.floor(state.length / 4);

    Object.keys(state.charTypes).forEach(type => {
        state.charTypes[type].quantity = defaultQty;
        document.getElementById(`qty-input-${type}`).value = defaultQty;
        document.getElementById(`qty-slider-${type}`).value = defaultQty;
    });

    updateValidation();
}

function generatePassword() {
    const enabledTypes = Object.keys(state.charTypes).filter(type => state.charTypes[type].enabled);

    if (enabledTypes.length === 0) {
        document.getElementById('password').value = 'Sélectionnez au moins un type de caractère';
        return;
    }

    let password = '';
    let remainingLength = state.length;

    enabledTypes.forEach(type => {
        const quantity = state.charTypes[type].quantity;
        const charset = charSets[type];

        for (let i = 0; i < quantity; i++) {
            const randomIndex = crypto.getRandomValues(new Uint32Array(1))[0] % charset.length;
            password += charset[randomIndex];
        }

        remainingLength -= quantity;
    });

    if (remainingLength > 0) {
        const allChars = enabledTypes.map(type => charSets[type]).join('');

        for (let i = 0; i < remainingLength; i++) {
            const randomIndex = crypto.getRandomValues(new Uint32Array(1))[0] % allChars.length;
            password += allChars[randomIndex];
        }
    }

    password = shuffleString(password);

    document.getElementById('password').value = password;
    checkStrength(password);
}

function shuffleString(str) {
    const array = str.split('');
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array.join('');
}

function checkStrength(password) {
    let strength = 0;
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const entropyInfo = document.getElementById('entropyInfo');

    if (password.length >= 12) strength += 20;
    if (password.length >= 16) strength += 15;
    if (password.length >= 20) strength += 15;

    if (/[a-z]/.test(password)) strength += 15;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 10;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 20;

    const charSpace = getCharSpace(password);
    const entropy = password.length * Math.log2(charSpace);

    strengthFill.style.width = strength + '%';
    entropyInfo.textContent = Math.round(entropy) + ' bits';

    if (strength < 40) {
        strengthFill.style.background = 'linear-gradient(90deg, #dc3545, #fd7e14)';
        strengthText.textContent = 'Faible';
        strengthText.style.color = '#dc3545';
    } else if (strength < 70) {
        strengthFill.style.background = 'linear-gradient(90deg, #ffc107, #fd7e14)';
        strengthText.textContent = 'Moyen';
        strengthText.style.color = '#ffc107';
    } else if (strength < 90) {
        strengthFill.style.background = 'linear-gradient(90deg, #28a745, #20c997)';
        strengthText.textContent = 'Fort';
        strengthText.style.color = '#28a745';
    } else {
        strengthFill.style.background = 'linear-gradient(90deg, #20c997, #17a2b8)';
        strengthText.textContent = 'Excellent';
        strengthText.style.color = '#20c997';
    }
}

function getCharSpace(password) {
    let space = 0;
    if (/[a-z]/.test(password)) space += 26;
    if (/[A-Z]/.test(password)) space += 26;
    if (/[0-9]/.test(password)) space += 10;
    if (/[^a-zA-Z0-9]/.test(password)) space += 32;
    return space;
}

function toggleAutoAdjust() {
    state.autoAdjustLength = document.getElementById('autoAdjustLength').checked;
    localStorage.setItem('autoAdjustLength', state.autoAdjustLength);

    if (!state.autoAdjustLength) {
        updateValidation();
    }
}

function copyPassword() {
    const passwordInput = document.getElementById('password');
    const password = passwordInput.value;

    if (password === 'Configurez vos options puis générez...') {
        showNotification('Générez d\'abord un mot de passe', 'warning');
        return;
    }

    navigator.clipboard.writeText(password).then(() => {
        showFeedback();
    }).catch(() => {
        passwordInput.select();
        document.execCommand('copy');
        showFeedback();
    });
}

function showFeedback() {
    const feedback = document.getElementById('feedback');
    feedback.classList.add('show');
    setTimeout(() => {
        feedback.classList.remove('show');
    }, 2500);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `feedback ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => notification.classList.add('show'), 100);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
