// anonymizer-core.js — moteur pur réversible (testé en Node + navigateur)

const FAKE_DATA = {
  firstNamesM: ['Jean', 'Pierre', 'Michel', 'André', 'Luc', 'Marc', 'Philippe', 'François', 'David', 'Mathieu'],
  firstNamesF: ['Marie', 'Julie', 'Sophie', 'Isabelle', 'Nathalie', 'Claire', 'Émilie', 'Caroline', 'Manon', 'Audrey'],
  lastNames: ['Tremblay', 'Gagnon', 'Bouchard', 'Gauthier', 'Morin', 'Lavoie', 'Fortin', 'Gagné', 'Pelletier', 'Bélanger'],
  streets: ['rue Principale', 'avenue du Parc', 'boulevard Saint-Joseph', 'chemin de la Rivière', 'rue des Érables', 'avenue Laurier', 'boulevard René-Lévesque', 'rue Saint-Denis', 'chemin Sainte-Foy', 'rue de la Gauchetière'],
  cities: ['Montréal', 'Québec', 'Laval', 'Gatineau', 'Longueuil', 'Sherbrooke', 'Saguenay', 'Lévis', 'Trois-Rivières', 'Terrebonne'],
  domains: ['gmail.com', 'hotmail.com', 'yahoo.ca', 'videotron.ca', 'bell.net'],
  companies: ['Constructions Boréal inc.', 'Groupe Solva', 'Entreprises Lemay-Côté', 'Gestion Riverin', 'Atelier Norjak', 'Services Permafort', 'Coopérative Verdelis', 'Industries Cap-Vert', 'Groupe Makila', 'Solutions Drakkar']
};

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function getAccentClass(char) {
  const base = char.toLowerCase();
  const map = {
    'a': '[aàâäAÀÂÄ]', 'e': '[eéèêëEÉÈÊË]', 'i': '[iîïIÎÏ]',
    'o': '[oôöOÔÖ]', 'u': '[uùûüUÙÛÜ]', 'c': '[cçCÇ]', 'n': '[nñNÑ]'
  };
  return map[base] || char;
}

// Construit une regex bornée, insensible casse + accents, espaces flexibles.
function buildAccentInsensitiveBoundedRegex(str) {
  let escaped = escapeRegex(str).replace(/\s+/g, '\\s+');
  let pattern = '';
  for (let i = 0; i < escaped.length; i++) {
    const char = escaped[i];
    if (char === '\\') { pattern += char + escaped[++i]; continue; } // garde \. \s etc.
    if (char === '+') { pattern += char; continue; }
    pattern += getAccentClass(char);
  }
  const startBoundary = /^\w/.test(str) ? '\\b' : '(?<!\\w)';
  const endBoundary = /\w$/.test(str) ? '\\b' : '(?!\\w)';
  return new RegExp(startBoundary + pattern + endBoundary, 'gi');
}

function detectEntities(text) {
  const STOPWORDS = new Set([
    'bonjour', 'bonsoir', 'salut', 'merci', 'cordialement', 'madame', 'monsieur',
    'docteur', 'clinique', 'hopital', 'centre', 'est', 'ouest', 'nord', 'sud',
    'quebec', 'canada', 'objet', 'suivi', 'nom', 'adresse',
    'dr', 'm', 'mme', 'me', 'pr', 'mr', 'mlle'
  ]);
  const normalize = (str) => str.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  const entities = [];
  const seen = new Set();
  const push = (value, category, label, confidence) => {
    const k = (value || '').trim();
    if (k && !seen.has(normalize(k))) { seen.add(normalize(k)); entities.push({ value: k, category, label, confidence: confidence || 0.9 }); }
  };
  let m;
  // 1. Noms avec titre de civilité (capture le nom, pas le titre)
  const titled = /\b(?:Dr\.?|M\.?|Mme\.?|Me|Pr|Mr)[^\S\r\n]+([A-ZÀ-Ÿ][a-zà-ÿ'’\-]+(?:[^\S\r\n]+[A-ZÀ-Ÿ][a-zà-ÿ'’\-]+)?)/g;
  while ((m = titled.exec(text))) push(m[1], 'name', 'Nom complet', 0.85);
  // 3. RAMQ
  const ramq = /\b[A-ZÀ-Ÿ]{4}\s?\d{4}\s?\d{2}\s?\d{2}\b/g;
  while ((m = ramq.exec(text))) push(m[0], 'id', 'RAMQ', 0.95);
  // 5. Numéro de permis / matricule (avec contexte)
  const permit = /(?:permis|oiiq|matricule|n[°o]\s*de\s*permis)[^\d]{0,15}(\d{5,})/gi;
  while ((m = permit.exec(text))) push(m[1], 'id', 'Numéro de permis', 0.9);
  // 6. Adresse civique
  const address = /\b\d{1,5},?\s+(?:rue|avenue|av\.?|boulevard|boul\.?|chemin|ch\.?|rang|montée|place|impasse)\s+[A-Za-zÀ-ÿ'’\-]+(?:\s+(?:Est|Ouest|Nord|Sud))?/gi;
  while ((m = address.exec(text))) push(m[0], 'address', 'Adresse', 0.85);
  // 4. Code postal canadien
  const postal = /\b[A-Z]\d[A-Z]\s?\d[A-Z]\d\b/g;
  while ((m = postal.exec(text))) push(m[0], 'address', 'Code postal', 0.9);
  // 7. Courriel
  const email = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g;
  while ((m = email.exec(text))) push(m[0], 'email', 'Courriel', 0.99);
  // 8. Téléphone CA
  const phone = /(?:\(\d{3}\)|\d{3})[-.\s]?\d{3}[-.\s]?\d{4}/g;
  while ((m = phone.exec(text))) push(m[0], 'phone', 'Téléphone', 0.9);
  // 9. Numéro de dossier
  const dossier = /(?:#|dossier\s*(?:n[°o]\s*)?)\d+/gi;
  while ((m = dossier.exec(text))) push(m[0], 'dossier', 'Numéro de dossier', 0.95);
  // 10. Montant
  const amount = /\$\s*\d+(?:[ ,]\d{3})*(?:[.,]\d{2})?\s*(?:CAD|cad)?/g;
  while ((m = amount.exec(text))) push(m[0], 'amount', 'Montant', 0.95);
  // 11. Date FR/ISO
  const date = /\b(?:\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4}|\d{1,2}\s+(?:janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)\s+\d{4})\b/gi;
  while ((m = date.exec(text))) push(m[0], 'date', 'Date', 0.9);
  // 2. Noms sans titre (deux mots capitalisés, hors stopwords)
  const name = /(?<![A-Za-zÀ-ÿ])([A-ZÀ-Ÿ][a-zà-ÿ'’]+(?:-[A-ZÀ-Ÿ]?[a-zà-ÿ'’]+)*)[^\S\r\n]+([A-ZÀ-Ÿ][a-zà-ÿ'’]+(?:-[A-ZÀ-Ÿ]?[a-zà-ÿ'’]+)*)(?![A-Za-zÀ-ÿ])/g;
  while ((m = name.exec(text))) {
    if (!STOPWORDS.has(normalize(m[1])) && !STOPWORDS.has(normalize(m[2]))) push(`${m[1]} ${m[2]}`, 'name', 'Nom complet', 0.8);
  }
  return entities;
}

function getRandomItem(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

function generateFake(category, original) {
  switch (category) {
    case 'dossier': {
      const num = original.match(/\d+/)[0];
      const fake = num.replace(/\d/g, () => Math.floor(Math.random() * 10).toString());
      return original.replace(/\d+/, fake);
    }
    case 'email': {
      return `${getRandomItem(FAKE_DATA.firstNamesM).toLowerCase()}.${getRandomItem(FAKE_DATA.lastNames).toLowerCase()}@${getRandomItem(FAKE_DATA.domains)}`;
    }
    case 'phone': return original.replace(/\d/g, () => Math.floor(Math.random() * 10).toString());
    case 'address': {
      const canadianPostalCode = /^[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d$/;
      if (original.trim().match(canadianPostalCode)) {
        const letters = 'ABCEGHJKLMNPRSTVXY';
        const rl = () => letters.charAt(Math.floor(Math.random() * letters.length));
        const rd = () => Math.floor(Math.random() * 10);
        return `${rl()}${rd()}${rl()} ${rd()}${rl()}${rd()}`;
      }
      const num = Math.floor(Math.random() * 990) + 10;
      return `${num} ${getRandomItem(FAKE_DATA.streets)}`;
    }
    case 'name': return `${getRandomItem([...FAKE_DATA.firstNamesM, ...FAKE_DATA.firstNamesF])} ${getRandomItem(FAKE_DATA.lastNames)}`;
    case 'firstName': return getRandomItem([...FAKE_DATA.firstNamesM, ...FAKE_DATA.firstNamesF]);
    case 'lastName': return getRandomItem(FAKE_DATA.lastNames);
    case 'amount': return '$' + ((Math.floor(Math.random() * 9000) + 100)) + ',00';
    case 'date': {
      const year = Math.floor(Math.random() * (2024 - 1950 + 1)) + 1950;
      const month = Math.floor(Math.random() * 12) + 1;
      const day = Math.floor(Math.random() * 28) + 1;
      const pad = (n) => n.toString().padStart(2, '0');
      if (/^\d{4}-\d{2}-\d{2}$/.test(original)) return `${year}-${pad(month)}-${pad(day)}`;
      if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(original)) return `${pad(day)}/${pad(month)}/${year}`;
      const monthsFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
      if (monthsFr.some(m => original.toLowerCase().includes(m))) return `${day} ${monthsFr[month - 1]} ${year}`;
      return `${year}-${pad(month)}-${pad(day)}`;
    }
    case 'id': // RAMQ, permis, matricule : on randomise chiffres ET lettres en gardant le format
      return original.replace(/\d/g, () => Math.floor(Math.random() * 10).toString())
                     .replace(/[A-ZÀ-Ÿ]/g, () => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 26)]);
    case 'organization':
    case 'other': return getRandomItem(FAKE_DATA.companies);
    default: return getRandomItem(FAKE_DATA.companies);
  }
}

// Garde-fou anti-fuite : garantit que le faux n'égale jamais l'original (insensible casse/accents)
function safeFake(category, original) {
  const norm = s => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const no = norm(original);
  let result = generateFake(category, original), attempts = 0;
  while (norm(result) === no && attempts < 8) { result = generateFake(category, original); attempts++; }
  return norm(result) === no ? result + '_x' : result;
}

function tokenLabel(category) {
  const labelMap = {
    name: 'PERSONNE', firstName: 'PERSONNE', lastName: 'PERSONNE',
    dossier: 'DOSSIER', address: 'ADRESSE', email: 'COURRIEL',
    phone: 'TEL', amount: 'MONTANT', date: 'DATE'
  };
  return labelMap[category] || 'ORG';
}

function buildRules(selections, opts = {}) {
  const mode = opts.mode || 'pseudo';
  const existing = opts.existing || [];
  if (mode === 'tokens') {
    const normalize = (str) => str.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const tokenIndex = new Map();
    const existingTokens = new Map();
    for (const rule of existing) {
      const match = /^\[([A-Z_]+)_(\d+)\]$/.exec(rule.replacement || '');
      if (match) {
        const label = match[1], idx = parseInt(match[2], 10);
        if (!existingTokens.has(label) || existingTokens.get(label) < idx) existingTokens.set(label, idx);
      }
    }
    const rules = [];
    const seen = new Map();
    for (const sel of selections) {
      const { value, category } = sel;
      const normValue = normalize(value);
      const label = tokenLabel(category);
      const id = `rule_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
      if (seen.has(normValue)) {
        rules.push({ id, original: value, replacement: seen.get(normValue), category });
        continue;
      }
      let currentIndex = tokenIndex.has(label) ? tokenIndex.get(label) : (existingTokens.get(label) || 0);
      currentIndex += 1;
      tokenIndex.set(label, currentIndex);
      const replacement = `[${label}_${currentIndex}]`;
      seen.set(normValue, replacement);
      rules.push({ id, original: value, replacement, category });
    }
    return rules;
  }
  const rules = [];
  const nameMap = new Map(); // partie réelle (minuscule) -> faux (cohérence + garde-fou)
  const subDone = new Set();  // parties ayant déjà une sous-règle
  for (const sel of selections) {
    const { value, category } = sel;
    const id = `rule_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    if (category === 'name') {
      const parts = value.split(/\s+/);
      if (parts.length === 2) {
        const [first, last] = parts;
        const fk = first.toLowerCase(), lk = last.toLowerCase();
        let fakeFirst = nameMap.get(fk); if (fakeFirst === undefined) { fakeFirst = safeFake('firstName', first); nameMap.set(fk, fakeFirst); }
        let fakeLast = nameMap.get(lk); if (fakeLast === undefined) { fakeLast = safeFake('lastName', last); nameMap.set(lk, fakeLast); }
        rules.push({ id, original: value, replacement: `${fakeFirst} ${fakeLast}`, category });
        if (!subDone.has(fk)) { subDone.add(fk); rules.push({ id: `${id}_first`, original: first, replacement: fakeFirst, category: 'firstName' }); }
        if (!subDone.has(lk)) { subDone.add(lk); rules.push({ id: `${id}_last`, original: last, replacement: fakeLast, category: 'lastName' }); }
        continue;
      }
    }
    rules.push({ id, original: value, replacement: safeFake(category, value), category });
  }
  return rules;
}

function anonymize(text, rules) {
  if (!rules.length) return text;
  const sorted = [...rules].sort((a, b) => b.original.length - a.original.length);
  let result = text;
  for (const rule of sorted) {
    result = result.replace(buildAccentInsensitiveBoundedRegex(rule.original), rule.replacement);
  }
  return result;
}

function restore(aiText, rules) {
  if (!rules.length) return { text: aiText, found: [], notFound: [] };
  const sorted = [...rules].sort((a, b) => b.replacement.length - a.replacement.length);
  let result = aiText;
  const found = [], notFound = [];
  for (const rule of sorted) {
    if (buildAccentInsensitiveBoundedRegex(rule.replacement).test(result)) {
      result = result.replace(buildAccentInsensitiveBoundedRegex(rule.replacement), rule.original);
      found.push(rule);
    } else {
      notFound.push(rule);
    }
  }
  return { text: result, found, notFound };
}

const AnonymizerCore = { detectEntities, generateFake, buildRules, anonymize, restore, buildAccentInsensitiveBoundedRegex };
if (typeof module !== 'undefined' && module.exports) module.exports = AnonymizerCore;
if (typeof window !== 'undefined') window.AnonymizerCore = AnonymizerCore;
