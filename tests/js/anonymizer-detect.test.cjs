// tests/js/anonymizer-detect.test.cjs
// Garde-fou de non-régression du moteur de détection de l'Anonymiseur.
// Exécuter : node tests/js/anonymizer-detect.test.cjs (ou npm run test:js)
// Couvre l'ajout v1.65.228 (cartes/IBAN/IP) + le bornage anti-faux-téléphone.
const fs = require('fs');
const path = require('path');
// Le fichier source est en "type":"module" (ESM) côté projet ; son export CJS est gardé.
// On l'évalue ici avec un objet `module` injecté pour récupérer le moteur sans modifier la source.
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/anonymiseur/anonymizer-core.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const AnonymizerCore = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }
const has = (ents, label, value) => ents.some(e => e.label === label && e.value === value);

// --- Test 1 : détection multiple + anti-régression IBAN→faux téléphone ---
const t1 = "Paiement 4111 1111 1111 1111, IBAN FR1420041010050500013M02606, IP 192.168.1.50, tel 514-555-0142, NAS 046 454 286, courriel marie@example.com, M. Éric Côté";
const e1 = AnonymizerCore.detectEntities(t1);
assert(has(e1, 'Carte bancaire', '4111 1111 1111 1111'), "carte bancaire (avec espaces)");
assert(has(e1, 'IBAN', 'FR1420041010050500013M02606'), "IBAN complet");
assert(has(e1, 'Adresse IP', '192.168.1.50'), "adresse IP");
assert(has(e1, 'Téléphone', '514-555-0142'), "téléphone");
assert(has(e1, 'NAS', '046 454 286'), "NAS");
assert(has(e1, 'Courriel', 'marie@example.com'), "courriel");
assert(!has(e1, 'Téléphone', '1420041010'), "anti-régression : pas de faux téléphone dans l'IBAN");

// --- Test 2 : carte sans espace + rejet Luhn invalide ---
const e2a = AnonymizerCore.detectEntities("carte 4111111111111111");
assert(has(e2a, 'Carte bancaire', '4111111111111111'), "carte sans espace");
const e2b = AnonymizerCore.detectEntities("1234 5678 9012 3456");
assert(!has(e2b, 'Carte bancaire', '1234 5678 9012 3456'), "rejet d'une carte Luhn invalide");

// --- Test 3 : le faker 'id' garde le format et change la valeur (moteur actuel) ---
const orig = 'FR1420041010050500013M02606';
const fake = AnonymizerCore.generateFake('id', orig);
assert(typeof fake === 'string' && fake.length > 0, "generateFake('id') retourne une chaîne");
assert(/^[A-Z]{2}\d{2}/.test(fake), "generateFake('id', IBAN) préserve le format (2 lettres + 2 chiffres)");
assert(fake !== orig, "generateFake('id', IBAN) != original");

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
