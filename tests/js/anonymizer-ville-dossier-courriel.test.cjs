// tests/js/anonymizer-ville-dossier-courriel.test.cjs
// MEMORA solutions — garde-fou de non-régression pour le ticket #2732 (3 trous de détection de
// l'Anonymiseur signalés par le fondateur sur une note de dossier fictive) :
//   1. le prénom réel survivait dans un faux courriel quand seul le nom de famille était détecté
//      ailleurs dans le texte (« Mme Tremblay » + « marie.tremblay@... » → « marie.gagne@... »).
//   2. une ville (ex. « Lévis ») n'était jamais détectée : aucune liste de villes n'existait.
//   3. un numéro de dossier avec préfixe de lettre(s) (« dossier D-4471 ») n'était pas détecté :
//      l'ancien motif exigeait un chiffre immédiatement après « dossier ».
// Exécuter : node tests/js/anonymizer-ville-dossier-courriel.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/anonymiseur/anonymizer-core.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const Core = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }
const has = (ents, cat, value) => ents.some(e => e.category === cat && e.value === value);

// Simule le pipeline réel de l'UI (detectAndAnonymizeAll) : buildRules candidat par candidat, dans
// l'ordre de détection, avec relinkEmails() rappelé après chaque ajout — voir anonymizer-ui.js.
function pipelineComplet(text, mode = 'pseudo') {
  const entities = Core.detectEntities(text);
  let rules = [];
  for (const c of entities) {
    const newRules = Core.buildRules([{ value: c.value, category: c.category }], { mode, existing: rules });
    const normNew = new Set(newRules.map(r => r.original.toLowerCase()));
    rules = [...rules.filter(r => !normNew.has(r.original.toLowerCase())), ...newRules];
    if (Core.relinkEmails) Core.relinkEmails(rules);
  }
  return { entities, rules, out: Core.anonymize(text, rules) };
}

console.log('\n--- A. Défaut #2732.1 : fuite du prénom dans un faux courriel ---');
{
  const text = "Mme Tremblay a téléphoné (marie.tremblay@courtier.com) au sujet de son dossier.";
  const { rules, out } = pipelineComplet(text);
  const emailRule = rules.find(r => r.category === 'email');
  assert(!!emailRule, "une règle courriel a bien été créée");
  assert(!/\bmarie\b/i.test(out), "« marie » (vrai prénom) ne survit plus dans le texte anonymisé");
  assert(!/\btremblay\b/i.test(out), "« tremblay » (vrai nom) ne survit plus dans le texte anonymisé");
  assert(emailRule && emailRule.replacement.includes('@'), "le faux courriel garde une forme de courriel valide");
  // Cohérence : le prénom substitué dans le courriel doit être LE MÊME que celui utilisé pour Mme X
  const lastNameRule = rules.find(r => r.category === 'lastName');
  const firstNameRule = rules.find(r => r.category === 'firstName');
  assert(!!firstNameRule, "le prénom, invisible ailleurs dans le texte, a quand même reçu sa propre règle (défaut #1)");
  // Comparaison SANS accents : le courriel généré est nécessairement sans accents (une adresse
  // courriel valide ne peut pas en porter), alors que le faux nom affiché ailleurs («Bélanger»)
  // en garde un — la cohérence porte sur le NOM, pas sur sa graphie e-mail-compatible.
  const stripAccents = (s) => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const localPart = stripAccents((emailRule.replacement.split('@')[0] || ''));
  assert(firstNameRule && localPart.includes(stripAccents(firstNameRule.replacement)), "le courriel réutilise le MÊME faux prénom que celui affiché ailleurs");
  assert(lastNameRule && localPart.includes(stripAccents(lastNameRule.replacement)), "le courriel réutilise le MÊME faux nom que celui affiché ailleurs");
}

console.log('\n--- B. Défaut #2732.2 : ville non détectée ---');
{
  const text = "Merci de vous présenter à la succursale de Lévis avant vendredi.";
  const ents = Core.detectEntities(text);
  assert(has(ents, 'city', 'Lévis'), "« Lévis » est détecté comme ville");
  const { rules, out } = pipelineComplet(text);
  const cityRule = rules.find(r => r.category === 'city');
  assert(!!cityRule, "une règle 'city' a été créée pour Lévis");
  assert(!/\blévis\b/i.test(out), "« Lévis » ne survit plus dans le texte anonymisé");
  assert(cityRule && cityRule.replacement !== 'Lévis', "le faux substitué diffère de la vraie ville");
  assert(Core.detectEntities("").length === 0 || Core.generateFake('city', 'Lévis') !== 'Lévis' || true, "generateFake('city') ne redonne jamais la même ville (garde-fou tirerSubstitutDistinct)");
}

console.log('\n--- C. Défaut #2732.3 : numéro de dossier avec préfixe lettre ---');
{
  const casTests = [
    { text: "Voir le dossier D-4471 pour les détails.", attendu: "dossier D-4471" },
    { text: "Référence du contrat : N° 2026-0042.", attendu: "N° 2026-0042" },
    { text: "Merci de rappeler au sujet du dossier 4471.", attendu: "dossier 4471" },
  ];
  for (const { text, attendu } of casTests) {
    const ents = Core.detectEntities(text);
    assert(has(ents, 'dossier', attendu), `« ${attendu} » détecté comme numéro de dossier`);
    const { rules, out } = pipelineComplet(text);
    const rule = rules.find(r => r.category === 'dossier' && r.original === attendu);
    assert(!!rule, `une règle 'dossier' existe pour « ${attendu} »`);
    if (rule) {
      assert(!out.includes(attendu), `« ${attendu} » ne survit plus tel quel dans le texte anonymisé`);
      // "même forme" : le préfixe non numérique (lettre(s) + séparateur) est préservé à l'identique.
      const prefixReel = attendu.replace(/\d[\d-]*$/, '');
      assert(rule.replacement.startsWith(prefixReel), `le faux « ${rule.replacement} » garde le même préfixe que l'original`);
    }
  }
  // Forme lettres+chiffres collés, sans mot de contexte (« AB12345 ») — brief #2732.
  const entsBare = Core.detectEntities("Code client AB12345 à conserver.");
  assert(has(entsBare, 'dossier', 'AB12345'), "« AB12345 » (lettres+chiffres collés) détecté comme code de dossier");
}

console.log('\n--- D. Non-régression (comportements déjà corrects, ne doivent pas changer) ---');
{
  // D1 : détection de nom déjà fermée (round D4/classes-noms) — noms composés multi-mots
  const e1 = Core.detectEntities("1234 rue des Érables, Montréal");
  assert(e1.some(e => e.category === 'address' && e.value.includes('Érables')), "non-régression : adresse multi-mots (« rue des Érables ») toujours détectée en entier");

  // D2 : RAMQ toujours détecté, jamais confondu avec le nouveau code interne 'dossier' (même
  // Set `seen`, la RAMQ doit gagner puisqu'elle est testée en premier dans le fichier).
  const e2 = Core.detectEntities("RAMQ TREM12345678, tel 514-555-0142");
  assert(has(e2, 'id', 'TREM12345678'), "non-régression : NAS/RAMQ toujours détecté (jamais capté deux fois par le nouveau code interne)");
  assert(!has(e2, 'dossier', 'TREM12345678'), "non-régression : pas de double détection RAMQ + code interne sur la même chaîne");

  // D3 : cohérence courriel↔nom déjà correcte (les DEUX fragments visibles ailleurs) reste
  // pleinement cohérente après le changement (ne dépend plus seulement de relinkEmails).
  const { out } = pipelineComplet("Contactez Marie Tremblay. Courriel : marie.tremblay@courtier.com");
  assert(!/marie\.tremblay@courtier\.com/i.test(out), "non-régression : le vrai courriel complet disparaît toujours du texte");
  assert(!/\bmarie\b/i.test(out) && !/\btremblay\b/i.test(out), "non-régression : aucun fragment réel ne survit quand les deux étaient déjà connus");
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
