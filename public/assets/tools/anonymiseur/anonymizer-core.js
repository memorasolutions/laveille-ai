// anonymizer-core.js — moteur pur réversible (testé en Node + navigateur)

const FAKE_DATA = {
  firstNamesM: ['Jean', 'Pierre', 'Michel', 'André', 'Luc', 'Marc', 'Philippe', 'François', 'David', 'Mathieu'],
  firstNamesF: ['Marie', 'Julie', 'Sophie', 'Isabelle', 'Nathalie', 'Claire', 'Émilie', 'Caroline', 'Manon', 'Audrey'],
  lastNames: ['Tremblay', 'Gagnon', 'Bouchard', 'Gauthier', 'Morin', 'Lavoie', 'Fortin', 'Gagné', 'Pelletier', 'Bélanger'],
  streets: ['rue Principale', 'avenue du Parc', 'boulevard Saint-Joseph', 'chemin de la Rivière', 'rue des Érables', 'avenue Laurier', 'boulevard René-Lévesque', 'rue Saint-Denis', 'chemin Sainte-Foy', 'rue de la Gauchetière'],
  cities: ['Montréal', 'Québec', 'Laval', 'Gatineau', 'Longueuil', 'Sherbrooke', 'Saguenay', 'Lévis', 'Trois-Rivières', 'Terrebonne'],
  domains: ['gmail.com', 'hotmail.com', 'yahoo.ca', 'videotron.ca', 'bell.net']
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
  const entities = [];
  const seen = new Set();
  const push = (value, category, label, confidence) => {
    const k = value.trim();
    if (k && !seen.has(k.toLowerCase())) { seen.add(k.toLowerCase()); entities.push({ value: k, category, label, confidence }); }
  };
  let m;
  const dossier = /(?:#|dossier\s*(?:n[°o]\s*)?)\d+/gi;
  while ((m = dossier.exec(text))) push(m[0], 'dossier', 'Numéro de dossier', 0.95);
  const email = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g;
  while ((m = email.exec(text))) push(m[0], 'email', 'Courriel', 0.99);
  const phone = /(?:\(\d{3}\)|\d{3})[-.\s]?\d{3}[-.\s]?\d{4}/g;
  while ((m = phone.exec(text))) push(m[0], 'phone', 'Téléphone', 0.9);
  const address = /\b\d+\s+(?:rue|avenue|av\.?|boulevard|boul\.?|chemin|ch\.?)\s+(?:de\s+la\s+|de\s+|du\s+|des\s+|la\s+|le\s+)?[A-Za-zÀ-ÿ'-]+(?:\s+[A-Za-zÀ-ÿ'-]+)?/gi;
  while ((m = address.exec(text))) push(m[0], 'address', 'Adresse', 0.85);
  const name = /(?<![A-Za-zÀ-ÿ])[A-ZÀ-Ÿ][a-zà-ÿ]+(?:[-'][A-ZÀ-Ÿ]?[a-zà-ÿ]+)?\s+[A-ZÀ-Ÿ][a-zà-ÿ]+(?:[-'][A-ZÀ-Ÿ]?[a-zà-ÿ]+)?(?![A-Za-zÀ-ÿ])/g;
  while ((m = name.exec(text))) push(m[0], 'name', 'Nom complet', 0.8);
  const amount = /\$\s*\d+(?:[ ,]\d{3})*(?:[.,]\d{2})?\s*(?:CAD|cad)?/g;
  while ((m = amount.exec(text))) push(m[0], 'amount', 'Montant', 0.95);
  const date = /\b(?:\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4}|\d{1,2}\s+(?:janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)\s+\d{4})\b/gi;
  while ((m = date.exec(text))) push(m[0], 'date', 'Date', 0.9);
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
      const num = (Math.floor(Math.random() * 990) + 10).toString();
      return `${num} ${getRandomItem(FAKE_DATA.streets)}`;
    }
    case 'name': return `${getRandomItem([...FAKE_DATA.firstNamesM, ...FAKE_DATA.firstNamesF])} ${getRandomItem(FAKE_DATA.lastNames)}`;
    case 'firstName': return getRandomItem([...FAKE_DATA.firstNamesM, ...FAKE_DATA.firstNamesF]);
    case 'lastName': return getRandomItem(FAKE_DATA.lastNames);
    case 'amount': return '$' + ((Math.floor(Math.random() * 9000) + 100)) + ',00';
    case 'date': {
      const y = 2000 + Math.floor(Math.random() * 25);
      const mo = String(Math.floor(Math.random() * 12) + 1).padStart(2, '0');
      const d = String(Math.floor(Math.random() * 28) + 1).padStart(2, '0');
      return `${y}-${mo}-${d}`;
    }
    default: return '***';
  }
}

function buildRules(selections) {
  const rules = [];
  const nameMap = new Map();
  for (const sel of selections) {
    const { value, category } = sel;
    const replacement = generateFake(category, value);
    const id = `rule_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    rules.push({ id, original: value, replacement, category });
    if (category === 'name') {
      const parts = value.split(/\s+/);
      const fakeParts = replacement.split(/\s+/);
      if (parts.length === 2 && fakeParts.length === 2) {
        const [first, last] = parts;
        if (!nameMap.has(first.toLowerCase())) {
          nameMap.set(first.toLowerCase(), fakeParts[0]);
          rules.push({ id: `${id}_first`, original: first, replacement: fakeParts[0], category: 'firstName' });
        }
        if (!nameMap.has(last.toLowerCase())) {
          nameMap.set(last.toLowerCase(), fakeParts[1]);
          rules.push({ id: `${id}_last`, original: last, replacement: fakeParts[1], category: 'lastName' });
        }
      }
    }
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
