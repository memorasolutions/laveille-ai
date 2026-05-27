// ============================================
// APPLICATION STATE
// ============================================
const AppState = {
    rules: [],
    currentStep: 1,
    editingRuleId: null,
    selectedCategory: 'identity',
    selectedGender: 'random',
    detectionBadgeIndex: null,
    isFullpage: false
};

// ============================================
// DONNEES DE REMPLACEMENT
// ============================================
const FakeData = {
    firstNamesMale: ['Pierre', 'Michel', 'Jean', 'Andre', 'Philippe', 'Rene', 'Louis', 'Alain', 'Jacques', 'Bernard', 'Marcel', 'Daniel', 'Roger', 'Robert', 'Paul', 'Claude', 'Francois', 'Henri', 'Yves', 'Maurice'],
    firstNamesFemale: ['Marie', 'Jeanne', 'Francoise', 'Monique', 'Nicole', 'Jacqueline', 'Anne', 'Sylvie', 'Catherine', 'Christine', 'Nathalie', 'Isabelle', 'Sophie', 'Valerie', 'Martine', 'Brigitte', 'Patricia', 'Helene', 'Veronique', 'Danielle'],
    lastNames: ['Tremblay', 'Gagnon', 'Roy', 'Cote', 'Bouchard', 'Gauthier', 'Morin', 'Lavoie', 'Fortin', 'Gagne', 'Ouellet', 'Pelletier', 'Belanger', 'Levesque', 'Bergeron', 'Leblanc', 'Paquette', 'Girard', 'Simard', 'Boucher'],
    cities: ['Montreal', 'Quebec', 'Laval', 'Gatineau', 'Longueuil', 'Sherbrooke', 'Saguenay', 'Levis', 'Trois-Rivieres', 'Terrebonne'],
    streets: ['rue Principale', 'avenue des Erables', 'boulevard Saint-Laurent', 'rue de la Montagne', 'avenue du Parc', 'rue Sainte-Catherine', 'boulevard Rene-Levesque', 'rue Saint-Denis', 'avenue Laurier', 'rue Notre-Dame'],
    domains: ['exemple.com', 'courriel.ca', 'test.org', 'demo.net', 'fictif.qc.ca'],
    companies: ['Entreprise ABC', 'Groupe XYZ', 'Services Omega', 'Industries Alpha', 'Solutions Beta', 'Compagnie Delta', 'Corporation Gamma', 'Firme Sigma', 'Agence Nova', 'Societe Atlas'],
    projects: ['Projet Horizon', 'Initiative Phenix', 'Programme Etoile', 'Mission Aurore', 'Operation Eclair', 'Plan Boreal', 'Strategie Altitude', 'Action Tremplin']
};

// ============================================
// CONFIGURATION DES CHAMPS PAR CATEGORIE
// ============================================
const CategoryConfig = {
    contact: {
        label: 'Email ou telephone',
        placeholder: 'ex: jean@exemple.com ou 514-555-1234',
        replacementLabel: 'Remplacer par',
        replacementPlaceholder: 'ex: pierre@demo.net ou 450-123-4567',
        hint: 'Formats acceptes : email, telephone fixe ou mobile'
    },
    location: {
        label: 'Adresse ou code postal',
        placeholder: 'ex: 123 rue Principale, Montreal ou H2X 1Y4',
        replacementLabel: 'Nouvelle adresse fictive',
        replacementPlaceholder: 'ex: 456 avenue des Erables ou G1A 2B3',
        hint: 'Utilisez une adresse fictive mais realiste'
    },
    id: {
        label: 'Numero d\'identification',
        placeholder: 'ex: NAS 123-456-789 ou dossier #12345',
        replacementLabel: 'Numero fictif',
        replacementPlaceholder: 'ex: NAS 987-654-321 ou dossier #99999',
        hint: 'NAS, numero de dossier, carte de credit, etc.'
    },
    date: {
        label: 'Date a anonymiser',
        placeholder: 'ex: 15/03/1985 ou 12 mai 1950',
        replacementLabel: 'Date fictive',
        replacementPlaceholder: 'ex: 22/08/1972 ou 3 janvier 1968',
        hint: 'Le format sera preserve automatiquement'
    },
    money: {
        label: 'Montant financier',
        placeholder: 'ex: 50 000$ ou 75 000 euros',
        replacementLabel: 'Montant fictif',
        replacementPlaceholder: 'ex: 35 000$ ou 42 000 euros',
        hint: 'Salaires, prix, transactions, etc.'
    },
    other: {
        label: 'Texte a anonymiser',
        placeholder: 'ex: Banque Royale, Projet Alpha...',
        replacementLabel: 'Texte de remplacement',
        replacementPlaceholder: 'ex: Entreprise XYZ, Projet Beta...',
        hint: 'Noms d\'entreprises, projets, lieux, etc.'
    }
};

// ============================================
// PATTERNS DE DETECTION
// ============================================
const DetectionPatterns = {
    email: {
        regex: /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g,
        category: 'contact',
        label: 'Email'
    },
    phoneCA: {
        regex: /(?:\+1[-.\s]?)?(?:\(?\d{3}\)?[-.\s]?)?\d{3}[-.\s]?\d{4}/g,
        category: 'contact',
        label: 'Telephone'
    },
    postalCA: {
        regex: /[A-Za-z]\d[A-Za-z][-\s]?\d[A-Za-z]\d/g,
        category: 'location',
        label: 'Code postal CA'
    },
    postalFR: {
        regex: /\b\d{5}\b/g,
        category: 'location',
        label: 'Code postal FR'
    },
    nas: {
        regex: /\b\d{3}[-\s]?\d{3}[-\s]?\d{3}\b/g,
        category: 'id',
        label: 'NAS'
    },
    creditCard: {
        regex: /\b(?:\d{4}[-\s]?){3}\d{4}\b/g,
        category: 'id',
        label: 'Carte credit'
    },
    date: {
        regex: /\b(?:\d{1,2}[-/]\d{1,2}[-/]\d{2,4}|\d{4}[-/]\d{1,2}[-/]\d{1,2}|\d{1,2}\s+(?:janvier|fevrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre)\s+\d{4})\b/gi,
        category: 'date',
        label: 'Date'
    },
    money: {
        regex: /\d{1,3}(?:[\s\u00A0,]\d{3})*(?:[.,]\d{2})?\s*(?:\$|euros?|CAD|EUR|dollars?)|\$\s*\d{1,3}(?:[\s\u00A0,]\d{3})*(?:[.,]\d{2})?/gi,
        category: 'money',
        label: 'Montant'
    },
    properName: {
        regex: /\b[A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][a-zàâäéèêëïîôùûüç]{2,}(?:[-][A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][a-zàâäéèêëïîôùûüç]+)?\s+[A-ZÀÂÄÉÈÊËÏÎÔÙÛÜÇ][a-zàâäéèêëïîôùûüç]+\b/g,
        category: 'identity',
        label: 'Nom complet'
    }
};

// ============================================
// UTILITAIRES
// ============================================
function generateId() {
    return 'rule_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function escapeRegExpWithFlexibleSpaces(string) {
    return string
        .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
        .replace(/\s+/g, '\\s+');
}

function createBoundedRegex(pattern, flags = 'gi') {
    const escaped = escapeRegExpWithFlexibleSpaces(pattern);
    const firstChar = pattern.charAt(0);
    const lastChar = pattern.charAt(pattern.length - 1);
    const startsWithWord = /\w/.test(firstChar);
    const endsWithWord = /\w/.test(lastChar);

    const startBoundary = startsWithWord ? '\\b' : '(?<![\\w])';
    const endBoundary = endsWithWord ? '\\b' : '(?![\\w])';

    try {
        return new RegExp(`${startBoundary}${escaped}${endBoundary}`, flags);
    } catch (e) {
        return new RegExp(escaped, flags);
    }
}

function randomFrom(array) {
    return array[Math.floor(Math.random() * array.length)];
}

function randomNumber(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function parseDateFromString(dateStr) {
    const monthNames = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin',
                       'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];

    let day, month, year;

    const frenchMatch = dateStr.match(/(\d{1,2})\s+(janvier|fevrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre)\s+(\d{4})/i);
    if (frenchMatch) {
        day = parseInt(frenchMatch[1]);
        month = monthNames.findIndex(m => m.toLowerCase() === frenchMatch[2].toLowerCase()) + 1;
        year = parseInt(frenchMatch[3]);
        return { day, month, year, format: 'french' };
    }

    const isoMatch = dateStr.match(/(\d{4})[-/](\d{1,2})[-/](\d{1,2})/);
    if (isoMatch) {
        year = parseInt(isoMatch[1]);
        month = parseInt(isoMatch[2]);
        day = parseInt(isoMatch[3]);
        const sep = dateStr.includes('/') ? '/' : '-';
        return { day, month, year, format: 'iso', separator: sep };
    }

    const euMatch = dateStr.match(/(\d{1,2})[-/](\d{1,2})[-/](\d{2,4})/);
    if (euMatch) {
        day = parseInt(euMatch[1]);
        month = parseInt(euMatch[2]);
        year = parseInt(euMatch[3]);
        if (year < 100) year += 2000;
        const sep = dateStr.includes('-') ? '-' : '/';
        return { day, month, year, format: 'eu', separator: sep };
    }

    return null;
}

function generateNearbyDate(original) {
    const monthNames = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin',
                       'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];

    const parsed = parseDateFromString(original);

    if (!parsed) {
        const fakeDay = randomNumber(1, 28);
        const fakeMonth = randomNumber(1, 12);
        const fakeYear = randomNumber(1950, 2010);
        return `${fakeDay.toString().padStart(2, '0')}/${fakeMonth.toString().padStart(2, '0')}/${fakeYear}`;
    }

    const baseDate = new Date(parsed.year, parsed.month - 1, parsed.day);
    let offset = randomNumber(-30, 30);
    if (offset === 0) offset = randomNumber(1, 7);

    const newDate = new Date(baseDate);
    newDate.setDate(newDate.getDate() + offset);

    const newDay = newDate.getDate();
    const newMonth = newDate.getMonth() + 1;
    const newYear = newDate.getFullYear();

    switch (parsed.format) {
        case 'french':
            return `${newDay} ${monthNames[newMonth - 1]} ${newYear}`;
        case 'iso':
            return `${newYear}${parsed.separator}${newMonth.toString().padStart(2, '0')}${parsed.separator}${newDay.toString().padStart(2, '0')}`;
        case 'eu':
        default:
            return `${newDay.toString().padStart(2, '0')}${parsed.separator}${newMonth.toString().padStart(2, '0')}${parsed.separator}${newYear}`;
    }
}

// ============================================
// GENERATION DE DONNEES FICTIVES
// ============================================
function generateFakeData(category, original = '', gender = null) {
    switch (category) {
        case 'identity':
            const genderChoice = gender || AppState.selectedGender;
            let isFemale;
            if (genderChoice === 'female') {
                isFemale = true;
            } else if (genderChoice === 'male') {
                isFemale = false;
            } else {
                isFemale = Math.random() > 0.5;
            }
            const firstName = randomFrom(isFemale ? FakeData.firstNamesFemale : FakeData.firstNamesMale);
            const lastName = randomFrom(FakeData.lastNames);
            if (original.includes(' ')) {
                return `${firstName} ${lastName}`;
            }
            return firstName;

        case 'contact':
            if (original.includes('@')) {
                const name = randomFrom(FakeData.firstNamesMale).toLowerCase();
                const domain = randomFrom(FakeData.domains);
                return `${name}@${domain}`;
            } else {
                return `${randomNumber(200, 999)}-${randomNumber(100, 999)}-${randomNumber(1000, 9999)}`;
            }

        case 'location':
            if (/[A-Za-z]\d[A-Za-z]/.test(original)) {
                const letters = 'ABCEGHJKLMNPRSTVWXYZ';
                return `${letters[randomNumber(0, letters.length-1)]}${randomNumber(1, 9)}${letters[randomNumber(0, letters.length-1)]} ${randomNumber(1, 9)}${letters[randomNumber(0, letters.length-1)]}${randomNumber(1, 9)}`;
            } else if (/\d{5}/.test(original)) {
                return String(randomNumber(10000, 95999));
            } else {
                return `${randomNumber(1, 999)} ${randomFrom(FakeData.streets)}, ${randomFrom(FakeData.cities)}`;
            }

        case 'id':
            if (original.length >= 16) {
                return `${randomNumber(4000, 4999)}-${randomNumber(1000, 9999)}-${randomNumber(1000, 9999)}-${randomNumber(1000, 9999)}`;
            } else {
                return `${randomNumber(100, 999)}-${randomNumber(100, 999)}-${randomNumber(100, 999)}`;
            }

        case 'date':
            if (original && original.trim()) {
                return generateNearbyDate(original);
            }
            const fakeDay = randomNumber(1, 28);
            const fakeMonth = randomNumber(1, 12);
            const fakeYear = randomNumber(1950, 2010);
            return `${fakeDay.toString().padStart(2, '0')}/${fakeMonth.toString().padStart(2, '0')}/${fakeYear}`;

        case 'money':
            const amount = randomNumber(100, 99999);
            if (original.includes('euro')) {
                return `${amount.toLocaleString('fr-FR')} euros`;
            }
            return `${amount.toLocaleString('fr-CA')} $`;

        case 'other':
        default:
            const allOther = [...FakeData.companies, ...FakeData.projects];
            return randomFrom(allOther);
    }
}

function generateVariants(category, original = '', count = 4) {
    const variants = new Set();
    let attempts = 0;
    while (variants.size < count && attempts < 20) {
        const variant = generateFakeData(category, original);
        variants.add(variant);
        attempts++;
    }
    return Array.from(variants);
}

function displayVariants() {
    const category = AppState.selectedCategory;
    const original = document.getElementById('inputOriginal').value;
    const variants = generateVariants(category, original, 4);
    const container = document.getElementById('variantsList');

    container.innerHTML = variants.map(v =>
        `<button type="button" class="variant-chip" data-value="${escapeHtml(v)}">${escapeHtml(v)}</button>`
    ).join('');

    container.querySelectorAll('.variant-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.getElementById('inputReplacement').value = chip.dataset.value;
        });
    });
}

// ============================================
// GESTION DES REGLES
// ============================================
function addRule(original, replacement, category, exceptions = '') {
    const rule = {
        id: generateId(),
        original: original.trim(),
        replacement: replacement.trim(),
        category: category,
        exceptions: exceptions.trim()
    };
    AppState.rules.push(rule);
    saveRules();
    renderRules();
    updateAnonymizedText();
    updateStats();
    showToast('Regle ajoutee', 'success');
}

function updateRule(id, original, replacement, category, exceptions = '') {
    const index = AppState.rules.findIndex(r => r.id === id);
    if (index !== -1) {
        AppState.rules[index] = {
            ...AppState.rules[index],
            original: original.trim(),
            replacement: replacement.trim(),
            category: category,
            exceptions: exceptions.trim()
        };
        saveRules();
        renderRules();
        updateAnonymizedText();
        updateStats();
        showToast('Regle modifiee', 'success');
    }
}

function deleteRule(id) {
    AppState.rules = AppState.rules.filter(r => r.id !== id);
    saveRules();
    renderRules();
    updateAnonymizedText();
    updateStats();
    showToast('Regle supprimee', 'success');
}

function saveRules() {
    localStorage.setItem('anonymizer_rules_v2', JSON.stringify(AppState.rules));
}

function loadRules() {
    const saved = localStorage.getItem('anonymizer_rules_v2');
    if (saved) {
        try {
            AppState.rules = JSON.parse(saved);
            renderRules();
            updateStats();
        } catch (e) {
            console.error('Erreur chargement regles:', e);
        }
    }
}

function exportRules() {
    const data = {
        version: '2.0',
        exportDate: new Date().toISOString(),
        rules: AppState.rules
    };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `anonymisation_${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url);
    showToast('Regles exportees', 'success');
}

function importRules(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = JSON.parse(e.target.result);
            if (data.rules && Array.isArray(data.rules)) {
                AppState.rules = [...AppState.rules, ...data.rules];
                saveRules();
                renderRules();
                updateAnonymizedText();
                updateStats();
                showToast(`${data.rules.length} regles importees`, 'success');
            }
        } catch (err) {
            showToast('Fichier invalide', 'error');
        }
    };
    reader.readAsText(file);
}

// ============================================
// RENDU DES REGLES
// ============================================
function getCategoryIcon(category) {
    const icons = {
        identity: '👤',
        contact: '📞',
        location: '🏠',
        id: '🔢',
        date: '📅',
        money: '💰',
        other: '📝'
    };
    return icons[category] || '📝';
}

function renderRules() {
    const container = document.getElementById('rulesList');

    if (AppState.rules.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <p>Aucune regle definie</p>
                <p class="text-muted mt-2">Selectionnez du texte ou cliquez sur "Detecter"</p>
            </div>
        `;
        return;
    }

    container.innerHTML = AppState.rules.map(rule => `
        <div class="rule-item" data-id="${rule.id}">
            <div class="rule-icon ${rule.category}">${getCategoryIcon(rule.category)}</div>
            <div class="rule-content">
                <div class="rule-original">${escapeHtml(rule.original)}</div>
                <div class="rule-replacement">-> ${escapeHtml(rule.replacement)}</div>
            </div>
            <div class="rule-actions">
                <button onclick="editRule('${rule.id}')" title="Modifier">✏️</button>
                <button onclick="deleteRule('${rule.id}')" title="Supprimer">🗑️</button>
            </div>
        </div>
    `).join('');
}

function editRule(id) {
    const rule = AppState.rules.find(r => r.id === id);
    if (rule) {
        AppState.editingRuleId = id;
        AppState.selectedCategory = rule.category;
        document.getElementById('inputExceptions').value = rule.exceptions || '';
        document.getElementById('modalTitle').textContent = 'Modifier la regle';

        if (rule.category === 'identity') {
            const originalParts = rule.original.split(' ');
            const replacementParts = rule.replacement.split(' ');

            if (originalParts.length >= 2) {
                document.getElementById('inputFirstName').value = originalParts[0];
                document.getElementById('inputLastName').value = originalParts.slice(1).join(' ');
            } else {
                document.getElementById('inputFirstName').value = rule.original;
                document.getElementById('inputLastName').value = '';
            }

            if (replacementParts.length >= 2) {
                document.getElementById('inputFakeFirstName').value = replacementParts[0];
                document.getElementById('inputFakeLastName').value = replacementParts.slice(1).join(' ');
            } else {
                document.getElementById('inputFakeFirstName').value = rule.replacement;
                document.getElementById('inputFakeLastName').value = '';
            }
        } else {
            document.getElementById('inputOriginal').value = rule.original;
            document.getElementById('inputReplacement').value = rule.replacement;
        }

        updateCategorySelection();
        updateGenderVisibility();
        updateFieldsVisibility();
        openModal();
    }
}

// ============================================
// DETECTION AUTOMATIQUE
// ============================================
function detectPII() {
    const text = document.getElementById('sourceText').innerText;
    const detections = [];
    const alreadyDetected = new Set();
    const existingOriginals = new Set(AppState.rules.map(r => r.original.toLowerCase()));

    for (const [key, pattern] of Object.entries(DetectionPatterns)) {
        const matches = text.match(pattern.regex) || [];
        for (const match of matches) {
            const normalized = match.trim();
            if (!alreadyDetected.has(normalized.toLowerCase()) &&
                !existingOriginals.has(normalized.toLowerCase())) {
                alreadyDetected.add(normalized.toLowerCase());
                detections.push({
                    text: normalized,
                    category: pattern.category,
                    label: pattern.label
                });
            }
        }
    }

    renderDetections(detections);
    if (detections.length > 0) {
        document.getElementById('detectionsBar').classList.remove('hidden');
        showToast(`${detections.length} elements detectes`, 'success');
    } else {
        showToast('Aucun element detecte', 'warning');
    }
}

function renderDetections(detections) {
    const container = document.getElementById('detectionsList');
    container.innerHTML = detections.map((d, i) => `
        <span class="detection-badge" onclick="addDetection(${i})" data-index="${i}"
              data-text="${escapeHtml(d.text)}" data-category="${d.category}">
            ${getCategoryIcon(d.category)} ${escapeHtml(d.text)}
        </span>
    `).join('');
}

function addDetection(index) {
    const badge = document.querySelector(`.detection-badge[data-index="${index}"]`);
    if (badge && !badge.classList.contains('added')) {
        const text = badge.dataset.text;
        const category = badge.dataset.category;

        AppState.detectionBadgeIndex = index;
        AppState.editingRuleId = null;
        AppState.selectedCategory = category;

        if (category === 'identity') {
            const parts = text.split(' ');
            if (parts.length >= 2) {
                document.getElementById('inputFirstName').value = parts[0];
                document.getElementById('inputLastName').value = parts.slice(1).join(' ');
            } else {
                document.getElementById('inputFirstName').value = text;
                document.getElementById('inputLastName').value = '';
            }
            generateFakeIdentity();
        } else {
            document.getElementById('inputOriginal').value = text;
            const replacement = generateFakeData(category, text);
            document.getElementById('inputReplacement').value = replacement;
        }

        updateCategorySelection();
        updateGenderVisibility();
        updateFieldsVisibility();

        document.getElementById('modalTitle').textContent = 'Anonymiser la detection';
        document.getElementById('ruleModal').classList.add('active');
    }
}

// ============================================
// ANONYMISATION
// ============================================
let highlightTimeout = null;
let isEditing = false;

function isException(match, fullText, matchIndex, exceptions) {
    if (!exceptions || exceptions.trim() === '') return false;

    const exceptionList = exceptions.split(',').map(e => e.trim().toLowerCase()).filter(e => e);
    if (exceptionList.length === 0) return false;

    const before = fullText.slice(0, matchIndex);
    const after = fullText.slice(matchIndex + match.length);

    const wordStartMatch = before.match(/\w*$/);
    const wordEndMatch = after.match(/^\w*/);

    const wordStart = wordStartMatch ? wordStartMatch[0] : '';
    const wordEnd = wordEndMatch ? wordEndMatch[0] : '';
    const fullWord = (wordStart + match + wordEnd).toLowerCase();

    return exceptionList.some(exc => fullWord === exc.toLowerCase() || fullWord.includes(exc.toLowerCase()));
}

function updateAnonymizedText() {
    const sourceEl = document.getElementById('sourceText');
    const sourceText = sourceEl.innerText;
    let anonymizedText = sourceText;

    const sortedRules = [...AppState.rules].sort((a, b) => b.original.length - a.original.length);

    for (const rule of sortedRules) {
        const regex = createBoundedRegex(rule.original, 'gi');

        anonymizedText = anonymizedText.replace(regex, (match, offset) => {
            if (isException(match, sourceText, offset, rule.exceptions)) {
                return match;
            }
            return rule.replacement;
        });
    }

    document.getElementById('anonymizedText').innerText = anonymizedText;

    clearTimeout(highlightTimeout);
    highlightTimeout = setTimeout(() => {
        if (!isEditing) {
            applyHighlights();
        }
    }, 300);
}

function applyHighlights() {
    const sourceEl = document.getElementById('sourceText');
    const plainText = sourceEl.innerText;

    if (AppState.rules.length === 0 || !plainText.trim()) {
        return;
    }

    const selection = window.getSelection();
    let cursorOffset = 0;
    if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const preCaretRange = range.cloneRange();
        preCaretRange.selectNodeContents(sourceEl);
        preCaretRange.setEnd(range.startContainer, range.startOffset);
        cursorOffset = preCaretRange.toString().length;
    }

    let html = escapeHtml(plainText);
    const sortedRules = [...AppState.rules].sort((a, b) => b.original.length - a.original.length);

    for (const rule of sortedRules) {
        const highlightClass = `highlight-inline ${rule.category}`;
        const regex = createBoundedRegex(rule.original, 'gi');

        html = html.replace(regex, (match, offset) => {
            if (isException(match, plainText, offset, rule.exceptions)) {
                return match;
            }
            return `<span class="${highlightClass}">${match}</span>`;
        });
    }

    sourceEl.innerHTML = html;
    restoreCursor(sourceEl, cursorOffset);
}

function restoreCursor(element, offset) {
    const selection = window.getSelection();
    const range = document.createRange();

    let charCount = 0;
    let found = false;

    function traverse(node) {
        if (found) return;

        if (node.nodeType === Node.TEXT_NODE) {
            const nextCount = charCount + node.length;
            if (offset <= nextCount) {
                range.setStart(node, offset - charCount);
                range.collapse(true);
                found = true;
            }
            charCount = nextCount;
        } else {
            for (const child of node.childNodes) {
                traverse(child);
                if (found) return;
            }
        }
    }

    traverse(element);

    if (found) {
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

function clearHighlights() {
    const sourceEl = document.getElementById('sourceText');
    const plainText = sourceEl.innerText;
    sourceEl.innerText = plainText;
}

function updateStats() {
    document.getElementById('statRules').textContent = AppState.rules.length;

    const sourceText = document.getElementById('sourceText').innerText;
    let replacementCount = 0;

    for (const rule of AppState.rules) {
        const regex = createBoundedRegex(rule.original, 'gi');
        let match;
        while ((match = regex.exec(sourceText)) !== null) {
            if (!isException(match[0], sourceText, match.index, rule.exceptions)) {
                replacementCount++;
            }
        }
    }

    document.getElementById('statReplacements').textContent = replacementCount;
}

// ============================================
// DECRYPTAGE
// ============================================
function restoreOriginalData() {
    const aiResponse = document.getElementById('aiResponse').value;
    let restoredText = aiResponse;

    const sortedRules = [...AppState.rules].sort((a, b) => b.replacement.length - a.replacement.length);

    for (const rule of sortedRules) {
        const regex = createBoundedRegex(rule.replacement, 'gi');
        restoredText = restoredText.replace(regex, rule.original);
    }

    document.getElementById('restoredText').innerText = restoredText;

    document.querySelectorAll('.result-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.result-content').forEach(c => c.classList.remove('active'));
    document.querySelector('[data-tab="output"]').classList.add('active');
    document.querySelector('[data-tab-content="output"]').classList.add('active');

    showToast('Donnees restaurees', 'success');
}

// ============================================
// MODAL
// ============================================
function openModal() {
    document.getElementById('ruleModal').classList.add('active');
    updateGenderVisibility();
    updateFieldsVisibility();
    resetGenderSelection();
    setTimeout(displayVariants, 50);
}

function closeModal() {
    document.getElementById('ruleModal').classList.remove('active');
    AppState.editingRuleId = null;
    AppState.detectionBadgeIndex = null;
    document.getElementById('inputOriginal').value = '';
    document.getElementById('inputReplacement').value = '';
    document.getElementById('inputExceptions').value = '';
    document.getElementById('modalTitle').textContent = 'Nouvelle regle';
    resetGenderSelection();
    clearIdentityFields();
}

function updateCategorySelection() {
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.category === AppState.selectedCategory);
    });
    displayVariants();
}

function updateGenderVisibility() {
    const genderGroup = document.getElementById('genderGroup');
    if (AppState.selectedCategory === 'identity') {
        genderGroup.classList.add('visible');
    } else {
        genderGroup.classList.remove('visible');
    }
}

function updateFieldsVisibility() {
    const identityFields = document.getElementById('identityFields');
    const otherFields = document.getElementById('otherFields');

    if (AppState.selectedCategory === 'identity') {
        identityFields.classList.add('visible');
        otherFields.classList.add('hidden');
    } else {
        identityFields.classList.remove('visible');
        otherFields.classList.remove('hidden');

        const config = CategoryConfig[AppState.selectedCategory];
        if (config) {
            document.getElementById('labelOriginal').textContent = config.label;
            document.getElementById('inputOriginal').placeholder = config.placeholder;
            document.getElementById('labelReplacement').textContent = config.replacementLabel;
            document.getElementById('inputReplacement').placeholder = config.replacementPlaceholder;
            document.getElementById('hintOriginal').textContent = config.hint;
        }
    }
}

function resetGenderSelection() {
    AppState.selectedGender = 'random';
    document.querySelectorAll('.gender-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.gender === 'random');
    });
}

function clearIdentityFields() {
    document.getElementById('inputFirstName').value = '';
    document.getElementById('inputLastName').value = '';
    document.getElementById('inputFakeFirstName').value = '';
    document.getElementById('inputFakeLastName').value = '';
}

function generateFakeIdentity() {
    const genderChoice = AppState.selectedGender;
    let isFemale;
    if (genderChoice === 'female') {
        isFemale = true;
    } else if (genderChoice === 'male') {
        isFemale = false;
    } else {
        isFemale = Math.random() > 0.5;
    }
    const firstName = randomFrom(isFemale ? FakeData.firstNamesFemale : FakeData.firstNamesMale);
    const lastName = randomFrom(FakeData.lastNames);

    document.getElementById('inputFakeFirstName').value = firstName;
    document.getElementById('inputFakeLastName').value = lastName;
}

function addIdentityRules(firstName, lastName, fakeFirstName, fakeLastName, exceptions = '') {
    const baseId = generateId();
    const trimmedExceptions = exceptions.trim();

    if (firstName && lastName) {
        AppState.rules.push({
            id: baseId,
            original: `${firstName} ${lastName}`,
            replacement: `${fakeFirstName} ${fakeLastName}`,
            category: 'identity',
            isMainRule: true,
            exceptions: trimmedExceptions
        });
    }

    if (firstName && fakeFirstName) {
        AppState.rules.push({
            id: generateId(),
            original: firstName,
            replacement: fakeFirstName,
            category: 'identity',
            isSubRule: true,
            parentId: baseId,
            exceptions: trimmedExceptions
        });
    }

    if (lastName && fakeLastName) {
        AppState.rules.push({
            id: generateId(),
            original: lastName,
            replacement: fakeLastName,
            category: 'identity',
            isSubRule: true,
            parentId: baseId,
            exceptions: trimmedExceptions
        });
    }

    saveRules();
    renderRules();
    updateAnonymizedText();
    updateStats();
    showToast('Identite ajoutee avec variantes', 'success');
}

// ============================================
// TOAST
// ============================================
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ============================================
// MODAL DE CONFIRMATION
// ============================================
function showConfirmModal(message, icon = '⚠️') {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const messageEl = document.getElementById('confirmMessage');
        const iconEl = document.getElementById('confirmIcon');
        const btnOk = document.getElementById('confirmOk');
        const btnCancel = document.getElementById('confirmCancel');

        messageEl.textContent = message;
        iconEl.textContent = icon;
        modal.classList.add('active');

        function cleanup() {
            modal.classList.remove('active');
            btnOk.removeEventListener('click', handleOk);
            btnCancel.removeEventListener('click', handleCancel);
            modal.removeEventListener('click', handleOverlay);
            document.removeEventListener('keydown', handleKeydown);
        }

        function handleOk() {
            cleanup();
            resolve(true);
        }

        function handleCancel() {
            cleanup();
            resolve(false);
        }

        function handleOverlay(e) {
            if (e.target === modal) {
                cleanup();
                resolve(false);
            }
        }

        function handleKeydown(e) {
            if (e.key === 'Escape') {
                cleanup();
                resolve(false);
            }
        }

        btnOk.addEventListener('click', handleOk);
        btnCancel.addEventListener('click', handleCancel);
        modal.addEventListener('click', handleOverlay);
        document.addEventListener('keydown', handleKeydown);

        btnCancel.focus();
    });
}

// ============================================
// COPIE PRESSE-PAPIERS
// ============================================
async function copyToClipboard(text, successMessage = 'Copie !') {
    try {
        await navigator.clipboard.writeText(text);
        showToast(successMessage, 'success');
    } catch (err) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast(successMessage, 'success');
    }
}

// ============================================
// NAVIGATION PAR ETAPES
// ============================================
function goToStep(step) {
    AppState.currentStep = step;

    document.querySelectorAll('.step').forEach(s => {
        s.classList.toggle('active', parseInt(s.dataset.step) === step);
    });

    document.querySelectorAll('.step-content').forEach(c => {
        c.classList.toggle('active', parseInt(c.dataset.stepContent) === step);
    });

    if (step === 2) {
        updateAnonymizedText();
    }
}

// ============================================
// MODE PLEINE PAGE
// ============================================
function toggleFullpage() {
    AppState.isFullpage = !AppState.isFullpage;
    document.body.classList.toggle('fullpage-mode', AppState.isFullpage);
    const btn = document.getElementById('btnFullpage');
    btn.textContent = AppState.isFullpage ? '⛶ Mode normal' : '⛶ Pleine page';
}

function checkFullpageParam() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('fullpage') === '1' || params.get('fullpage') === 'true') {
        AppState.isFullpage = true;
        document.body.classList.add('fullpage-mode');
        document.getElementById('btnFullpage').textContent = '⛶ Mode normal';
    }
}

// ============================================
// SELECTION DE TEXTE
// ============================================
function handleTextSelection() {
    const selection = window.getSelection();
    const selectedText = selection.toString().trim();

    if (selectedText.length > 0 && selectedText.length < 500) {
        const exists = AppState.rules.some(r => r.original.toLowerCase() === selectedText.toLowerCase());
        if (!exists) {
            AppState.selectedCategory = 'identity';
            AppState.editingRuleId = null;

            // Parser le texte selectionne en prenom et nom
            const parts = selectedText.split(' ');
            if (parts.length >= 2) {
                document.getElementById('inputFirstName').value = parts[0];
                document.getElementById('inputLastName').value = parts.slice(1).join(' ');
            } else {
                document.getElementById('inputFirstName').value = selectedText;
                document.getElementById('inputLastName').value = '';
            }

            // Generer les faux noms
            generateFakeIdentity();

            updateCategorySelection();
            openModal();
        }
    }
}

// ============================================
// INITIALISATION
// ============================================
function init() {
    loadRules();
    checkFullpageParam();
    document.getElementById('btnFullpage').addEventListener('click', toggleFullpage);

    document.querySelectorAll('.step').forEach(step => {
        step.addEventListener('click', () => goToStep(parseInt(step.dataset.step)));
    });

    document.getElementById('btnExport').addEventListener('click', exportRules);
    document.getElementById('btnImport').addEventListener('click', () => {
        document.getElementById('fileImport').click();
    });
    document.getElementById('fileImport').addEventListener('change', (e) => {
        if (e.target.files[0]) {
            importRules(e.target.files[0]);
            e.target.value = '';
        }
    });

    document.getElementById('btnClearAllRules').addEventListener('click', async () => {
        if (AppState.rules.length === 0) return;
        const confirmed = await showConfirmModal('Effacer toutes les regles d\'anonymisation ?', '🗑️');
        if (confirmed) {
            AppState.rules = [];
            saveRules();
            renderRules();
            updateAnonymizedText();
            updateStats();
            document.getElementById('detectionsBar').classList.add('hidden');
            showToast('Toutes les regles ont ete effacees', 'success');
        }
    });

    const bannerClosed = localStorage.getItem('anonymizer_banner_closed');
    if (bannerClosed) {
        document.getElementById('infoBanner').classList.add('hidden');
    }
    document.getElementById('closeBanner').addEventListener('click', () => {
        document.getElementById('infoBanner').classList.add('hidden');
        localStorage.setItem('anonymizer_banner_closed', 'true');
    });

    const sourceText = document.getElementById('sourceText');
    sourceText.addEventListener('input', () => {
        const text = sourceText.innerText;
        document.getElementById('charCount').textContent = `${text.length} caracteres`;
        updateAnonymizedText();
        updateStats();
    });
    sourceText.addEventListener('mouseup', handleTextSelection);

    sourceText.addEventListener('focus', () => {
        isEditing = true;
    });
    sourceText.addEventListener('blur', () => {
        isEditing = false;
        const modalOpen = document.getElementById('ruleModal').classList.contains('active') ||
                          document.getElementById('confirmModal').classList.contains('active');
        if (!modalOpen) {
            applyHighlights();
        }
    });

    document.getElementById('btnDetect').addEventListener('click', detectPII);
    document.getElementById('btnClear').addEventListener('click', () => {
        sourceText.innerText = '';
        document.getElementById('charCount').textContent = '0 caracteres';
        document.getElementById('detectionsBar').classList.add('hidden');
        updateAnonymizedText();
        updateStats();
    });

    document.getElementById('btnAddRule').addEventListener('click', () => {
        AppState.editingRuleId = null;
        AppState.selectedCategory = 'identity';
        updateCategorySelection();
        openModal();
    });

    const actionsMenu = document.getElementById('actionsMenu');
    const actionsMenuTrigger = document.getElementById('btnActionsMenu');

    actionsMenuTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        actionsMenu.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
        if (!actionsMenu.contains(e.target) && e.target !== actionsMenuTrigger) {
            actionsMenu.classList.remove('active');
        }
    });

    actionsMenu.querySelectorAll('.action-menu-item').forEach(item => {
        item.addEventListener('click', () => {
            actionsMenu.classList.remove('active');
        });
    });

    document.getElementById('closeModal').addEventListener('click', closeModal);
    document.getElementById('btnCancelRule').addEventListener('click', closeModal);
    document.getElementById('ruleModal').addEventListener('click', (e) => {
        if (e.target.id === 'ruleModal') closeModal();
    });

    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const previousCategory = AppState.selectedCategory;
            const newCategory = btn.dataset.category;

            if (previousCategory === 'identity' && newCategory !== 'identity') {
                const firstName = document.getElementById('inputFirstName').value.trim();
                const lastName = document.getElementById('inputLastName').value.trim();
                const fullName = [firstName, lastName].filter(Boolean).join(' ');
                if (fullName && !document.getElementById('inputOriginal').value) {
                    document.getElementById('inputOriginal').value = fullName;
                    document.getElementById('inputReplacement').value = generateFakeData(newCategory, fullName);
                }
            } else if (previousCategory !== 'identity' && newCategory === 'identity') {
                const original = document.getElementById('inputOriginal').value.trim();
                if (original && !document.getElementById('inputFirstName').value) {
                    const parts = original.split(' ');
                    if (parts.length >= 2) {
                        document.getElementById('inputFirstName').value = parts[0];
                        document.getElementById('inputLastName').value = parts.slice(1).join(' ');
                    } else {
                        document.getElementById('inputFirstName').value = original;
                    }
                    generateFakeIdentity();
                }
            }

            AppState.selectedCategory = newCategory;
            updateCategorySelection();
            updateGenderVisibility();
            updateFieldsVisibility();
        });
    });

    document.querySelectorAll('.gender-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            AppState.selectedGender = btn.dataset.gender;
            document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    document.getElementById('btnRefreshVariant').addEventListener('click', () => {
        displayVariants();
        const firstVariant = document.querySelector('.variant-chip');
        if (firstVariant) {
            document.getElementById('inputReplacement').value = firstVariant.dataset.value;
        }
    });

    document.getElementById('btnGenerateIdentity').addEventListener('click', generateFakeIdentity);

    document.getElementById('btnSaveRule').addEventListener('click', () => {
        const markDetectionAsAdded = () => {
            if (AppState.detectionBadgeIndex !== null) {
                const badge = document.querySelector(`.detection-badge[data-index="${AppState.detectionBadgeIndex}"]`);
                if (badge) badge.classList.add('added');
            }
        };

        const exceptions = document.getElementById('inputExceptions').value.trim();

        if (AppState.selectedCategory === 'identity') {
            const firstName = document.getElementById('inputFirstName').value.trim();
            const lastName = document.getElementById('inputLastName').value.trim();
            const fakeFirstName = document.getElementById('inputFakeFirstName').value.trim();
            const fakeLastName = document.getElementById('inputFakeLastName').value.trim();

            if ((!firstName && !lastName) || (!fakeFirstName && !fakeLastName)) {
                showToast('Veuillez remplir au moins un prenom ou nom', 'error');
                return;
            }

            if (AppState.editingRuleId) {
                const original = firstName && lastName ? `${firstName} ${lastName}` : (firstName || lastName);
                const replacement = fakeFirstName && fakeLastName ? `${fakeFirstName} ${fakeLastName}` : (fakeFirstName || fakeLastName);
                updateRule(AppState.editingRuleId, original, replacement, 'identity', exceptions);
            } else {
                addIdentityRules(firstName, lastName, fakeFirstName, fakeLastName, exceptions);
            }
            markDetectionAsAdded();
            closeModal();
            return;
        }

        const original = document.getElementById('inputOriginal').value.trim();
        const replacement = document.getElementById('inputReplacement').value.trim();

        if (!original || !replacement) {
            showToast('Veuillez remplir tous les champs', 'error');
            return;
        }

        if (AppState.editingRuleId) {
            updateRule(AppState.editingRuleId, original, replacement, AppState.selectedCategory, exceptions);
        } else {
            addRule(original, replacement, AppState.selectedCategory, exceptions);
        }
        markDetectionAsAdded();
        closeModal();
    });

    document.getElementById('btnCopy').addEventListener('click', () => {
        const text = document.getElementById('anonymizedText').innerText;
        copyToClipboard(text, 'Texte anonymise copie !');
    });

    document.querySelectorAll('.result-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.result-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.result-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.querySelector(`[data-tab-content="${tab.dataset.tab}"]`).classList.add('active');
        });
    });

    document.getElementById('btnRestore').addEventListener('click', restoreOriginalData);
    document.getElementById('btnCopyRestored').addEventListener('click', () => {
        const text = document.getElementById('restoredText').innerText;
        copyToClipboard(text, 'Texte restaure copie !');
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

document.addEventListener('DOMContentLoaded', init);
