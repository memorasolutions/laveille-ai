// tests/js/anonymizer-montant-ville.test.cjs
// MEMORA solutions - regressions de detection de l'anonymiseur (inventaire /100 du 2026-10-12) :
//   (A) montant avec $ APRES le nombre, sans separateur de milliers (« 1250$ ») doit etre capte
//       EN ENTIER (avant : seul « 250$ » l'etait, « 1 » fuyait en clair) ;
//   (B) ville introduite par « a X » doit etre captee par le contexte (avant : le \b initial
//       echouait devant « a », donc « a Toronto » n'etait jamais detecte).
// Executer : node tests/js/anonymizer-montant-ville.test.cjs
const fs = require('fs');
const path = require('path');
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/anonymiseur/anonymizer-core.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const Core = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }
const amounts = (t) => Core.detectEntities(t).filter(e => e.category === 'amount').map(e => e.value.replace(/\s/g, ''));
const cities = (t) => Core.detectEntities(t).filter(e => e.category === 'city').map(e => e.value);

console.log('\n--- A. Montant avec $ apres le nombre ---');
assert(amounts('La facture est de 1250$ au total.').includes('1250$'),
  "« 1250$ » (4 chiffres sans separateur) capte EN ENTIER");
assert(!amounts('La facture est de 1250$ au total.').includes('250$'),
  "« 1250$ » ne laisse PAS « 250$ » comme capture tronquee");
assert(amounts('Montant de 1 250,00 $ a payer').includes('1250,00$'),
  "« 1 250,00 $ » (milliers groupes) toujours capte");
assert(amounts('Solde : 875$ net').includes('875$'),
  "« 875$ » (3 chiffres) toujours capte (non-regression)");
assert(amounts('Prix 12345$').includes('12345$'),
  "« 12345$ » (5 chiffres sans separateur) capte en entier");

console.log('\n--- B. Ville introduite par « a X » (contexte) ---');
assert(cities('Le bureau demenage à Toronto cet ete.').includes('Toronto'),
  "« à Toronto » (ville inconnue de la liste) detectee par le contexte");
assert(cities('Le colis part à Brouillardville lundi.').includes('Brouillardville'),
  "« à Brouillardville » (lieu invente capitalise) detecte par le contexte");
// Non-regression des autres declencheurs de contexte (succursale/bureau) sur un lieu inconnu.
assert(cities('Rendez-vous à la succursale de Pintendre vendredi.').includes('Pintendre'),
  "« succursale de Pintendre » toujours detecte (branche non cassee)");

console.log('\n--- C. « à <prénom> » n\'est PAS une ville (anti-regression de la passe /100) ---');
assert(!cities('Merci à Jean pour son aide.').includes('Jean'),
  "« à Jean » : prénom connu NON capté comme ville");
assert(!cities('Bonjour à Marie, à bientôt.').includes('Marie'),
  "« à Marie » : prénom connu NON capté comme ville");
assert(!cities('J\'écris à Pierre ce matin.').includes('Pierre'),
  "« à Pierre » : prénom connu NON capté comme ville");
// Et « à Toronto » reste bien une ville malgré le garde-prénom.
assert(cities('Le bureau demenage à Toronto cet ete.').includes('Toronto'),
  "« à Toronto » : ville toujours captée (le garde-prénom ne l'exclut pas)");
// EXCEPTION hagionyme : « Saint(e)-<prénom> » est une municipalité, pas une personne (round 2 /100).
assert(cities('Il vit à Sainte-Marie depuis dix ans.').includes('Sainte-Marie'),
  "« à Sainte-Marie » : toponyme hagionyme capté comme ville (exempté du garde-prénom)");
assert(cities('Rendez-vous à Saint-Jean demain.').includes('Saint-Jean'),
  "« à Saint-Jean » : toponyme hagionyme capté comme ville");

console.log('\n=== ' + pass + ' pass, ' + fail + ' fail ===');
process.exit(fail === 0 ? 0 : 1);
