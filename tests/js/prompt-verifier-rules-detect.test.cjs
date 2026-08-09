// tests/js/prompt-verifier-rules-detect.test.cjs
// Garde-fou de non-régression du moteur de détection déterministe du Constructeur de prompts
// (réécriture 2026-08-02, étape 4 de .outils/PLAN-CONSTRUCTEUR-PROMPTS-ULTRA-2026-08-02.md,
// couvert par les tests Pest de l'étape 8 - section 8 - pour la partie serveur ; ce fichier
// couvre uniquement le moteur JS pur detect(), zéro DOM, testable directement via Node).
// Exécuter : node tests/js/prompt-verifier-rules-detect.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');
// Le fichier source est écrit pour le navigateur mais garde un export CJS explicite
// (typeof module !== 'undefined' -> module.exports = api), même pattern que anonymizer-core.js.
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/prompt-verifier-rules.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const PromptVerifierRules = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }
const hasCategory = (ents, category) => ents.some(e => e.category === category);
const hasLabel = (ents, label) => ents.some(e => e.label === label);

// --- Courriel ---
const e1 = PromptVerifierRules.detect('Écrivez à marie.tremblay@ecole.qc.ca pour confirmer.');
assert(hasCategory(e1, 'email'), 'détecte un courriel');

// --- Téléphone (indicatif québécois vs générique) ---
const e2 = PromptVerifierRules.detect('Rejoignez-moi au 514-555-0142 avant vendredi.');
assert(hasCategory(e2, 'phone'), 'détecte un téléphone');
assert(hasLabel(e2, 'Téléphone (indicatif québécois)'), 'étiquette un indicatif régional québécois (514)');

const e2b = PromptVerifierRules.detect('Appelez le 212-555-0142.');
assert(hasCategory(e2b, 'phone'), 'détecte un téléphone hors Québec');
assert(hasLabel(e2b, 'Téléphone') && !hasLabel(e2b, 'Téléphone (indicatif québécois)'), 'n\'étiquette PAS un indicatif hors Québec comme québécois');

// --- Code permanent / RAMQ (même format 4 lettres + 8 chiffres) ---
const e3 = PromptVerifierRules.detect("Le code permanent de l'élève est TREM12345678.");
assert(hasCategory(e3, 'id'), 'détecte un code permanent/RAMQ');

// --- Prénom composé (cas validé manuellement lors de la réécriture) ---
const e4 = PromptVerifierRules.detect('Marie-Ève adore les sciences et pose toujours de bonnes questions.');
assert(hasCategory(e4, 'name'), 'détecte "Marie-Ève" comme prénom composé probable');

// --- Anti faux-positif : un mot capitalisé accentué seul (École, Élève) ne doit RIEN détecter ---
const e5 = PromptVerifierRules.detect("École secondaire des Sentiers, préparation de l'examen d'Éthique.");
assert(e5.length === 0, 'ne détecte RIEN pour "École secondaire..." (anti faux-positif du round Schedule 2026-08-02)');

// --- Texte vide / non-string : ne plante jamais ---
assert(Array.isArray(PromptVerifierRules.detect('')) && PromptVerifierRules.detect('').length === 0, 'chaîne vide -> tableau vide');
assert(Array.isArray(PromptVerifierRules.detect(undefined)) && PromptVerifierRules.detect(undefined).length === 0, 'valeur non-string -> tableau vide, ne plante pas');

// --- Détection multiple dans un même texte, triée par ordre d'apparition ---
const e6 = PromptVerifierRules.detect('Contactez jean@ecole.qc.ca ou au 418-555-0199 pour Anne-Sophie.');
assert(e6.length >= 3, 'détecte plusieurs entités dans un même texte (courriel + téléphone + prénom composé)');
assert(e6.every((ent, i) => i === 0 || ent.index >= e6[i - 1].index), 'entités triées par ordre d\'apparition dans le texte');

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
