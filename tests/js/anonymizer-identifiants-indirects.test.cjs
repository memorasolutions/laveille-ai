// tests/js/anonymizer-identifiants-indirects.test.cjs
// #3098 bug 3 - identifiants indirects NON couverts (mesurés par sonde) : numéro d'employé,
// identifiant/numéro de client/membre, permis de conduire préfixé d'une lettre, plaque
// d'immatriculation. Toutes ces détections sont ANCRÉES SUR UN MOT-CLÉ (employé, client, permis,
// plaque...) pour rester à très faible taux de faux positifs : un nombre nu SANS contexte n'est
// jamais capté ici (ce serait un faux positif). Exécuter : node tests/js/anonymizer-identifiants-indirects.test.cjs
const fs = require('fs');
const path = require('path');
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/anonymiseur/anonymizer-core.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const A = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }
const norm = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[\s-]/g, '');
// Un identifiant est capté si une entité (catégorie id/dossier) contient la suite de caractères attendue.
const capte = (ents, attendu) => ents.some(e => ['id', 'dossier'].includes(e.category) && norm(e.value).includes(norm(attendu)));

console.log('\n--- A. Identifiants indirects ancrés sur mot-clé (DOIVENT être détectés) ---');
[
  ["Mon numéro d'employé est 4471.", '4471'],
  ['Identifiant client CLI-88412.', 'CLI-88412'],
  ['Numéro de membre 998877.', '998877'],
  ['Son numéro de dossier client est AB-4471.', 'AB-4471'],
  ['Permis de conduire T1234-567890-12.', 'T1234-567890-12'],
  ["Plaque d'immatriculation F12 ABC.", 'F12 ABC'],
  ['Vehicule immatricule F12 ABC au Quebec.', 'F12 ABC'],
].forEach(([t, attendu]) => assert(capte(A.detectEntities(t), attendu), `« ${attendu} » capté dans « ${t} »`));

console.log('\n--- B. Anti-faux-positifs : un nombre SANS contexte identifiant ne doit PAS être capté ---');
const pasId = (t, interdit) => !A.detectEntities(t).some(e => ['id', 'dossier'].includes(e.category) && norm(e.value).includes(norm(interdit)));
[
  ['Il y a 4471 habitants dans ce village.', '4471'],
  ['La chambre 123 est libre.', '123'],
  ['Nous avons vendu 998877 unites cette annee.', '998877'],
  ['Le trajet fait 123 ABC kilometres.', '123ABC'],
  // Trouvés par la revue adversariale Codex (#3098) :
  ['Le numéro de client comporte 8 chiffres.', '8'],      // description, pas un identifiant
  ['Cette plaque de 20 cm est en acier.', '20'],          // plaque physique, pas d'immatriculation
].forEach(([t, interdit]) => assert(pasId(t, interdit), `« ${interdit} » PAS capté dans « ${t} »`));

console.log('\n--- D. Anti-ReDoS : une longue suite d\'espaces après le mot-clé reste rapide ---');
{
  const t0 = Date.now();
  for (const kw of ['permis', 'plaque', 'immatricule']) A.detectEntities(kw + ' '.repeat(4000) + '!');
  const ms = Date.now() - t0;
  assert(ms < 500, `3 entrées « mot-clé + 4000 espaces + ! » traitées en ${ms} ms (< 500 ms, pas de backtracking catastrophique)`);
}

console.log('\n--- C. Bout à bout : l\'identifiant est bien REMPLACÉ puis RESTITUÉ ---');
{
  const texte = "Mon numéro d'employé est 4471.";
  const ents = A.detectEntities(texte);
  let rules = A.buildRules(ents.map(e => ({ value: e.value, category: e.category })), { mode: 'pseudo' });
  const anon = A.anonymize(texte, rules);
  assert(!/\b4471\b/.test(anon), `« 4471 » n'est plus en clair (sortie : « ${anon} »)`);
  assert(/\b4471\b/.test(A.restore(anon, rules).text), '« 4471 » restitué à la restauration');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
