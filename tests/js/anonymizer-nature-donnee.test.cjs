// tests/js/anonymizer-nature-donnee.test.cjs
// MEMORA solutions - la nature d'une donnée doit être préservée au remplacement : province -> province,
// entreprise -> entreprise, nom saisi en minuscules -> nom. Exécuter : node tests/js/anonymizer-nature-donnee.test.cjs
const fs = require('fs');
const path = require('path');
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/anonymiseur/anonymizer-core.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const Core = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }
const N = 200;
const PROVINCES = ['Québec', 'Ontario', 'Manitoba', 'Saskatchewan', 'Alberta', 'Colombie-Britannique', 'Nouveau-Brunswick', 'Nouvelle-Écosse', 'Île-du-Prince-Édouard', 'Terre-Neuve-et-Labrador', 'Yukon', 'Territoires du Nord-Ouest', 'Nunavut'];

console.log('\n--- A. Province ---');
{
  let ok = true;
  for (let i = 0; i < N; i++) {
    const f = Core.generateFake('province', 'Ontario');
    if (f === 'Ontario' || !PROVINCES.includes(f)) ok = false;
  }
  assert(ok, "generateFake('province', 'Ontario') : toujours une AUTRE province (" + N + " tirages)");
  assert(Core.guessSelectionCategory('Ontario') === 'province', "sélection « Ontario » -> province");
  assert(Core.guessSelectionCategory('Québec') === 'city', "« Québec » seul reste détecté comme ville (ambigu, non modifié)");
  const rules = Core.buildRules([{ value: 'Alberta', category: 'province' }], { mode: 'tokens', existing: [] });
  assert(/PROVINCE/.test(rules[0].replacement), "jeton de mode tokens = PROVINCE");
}

console.log('\n--- B. Entreprise ---');
{
  assert(Core.guessSelectionCategory('Julie Morin inc.') === 'organization', "« Julie Morin inc. » -> organization");
  assert(Core.guessSelectionCategory('Garage Tremblay ltée') === 'organization', "suffixe « ltée » -> organization");
  assert(Core.guessSelectionCategory('Boulangerie Lafleur') === 'organization', "mot générique en tête -> organization");
  assert(Core.guessSelectionCategory('Julie Morin') === 'name', "« Julie Morin » sans suffixe reste un nom");
  const fake = Core.generateFake('organization', 'Julie Morin inc.');
  assert(!/^(Jean|Pierre|Michel|André|Luc|Marc|Philippe|François|David|Mathieu|Marie|Julie|Sophie|Isabelle|Nathalie|Claire|Émilie|Caroline|Manon|Audrey) /.test(fake), "faux d'entreprise : pas un prénom + nom (" + fake + ")");
}

console.log('\n--- C. Nom en minuscules ---');
{
  assert(Core.guessSelectionCategory('julie morin') === 'name', "sélection « julie morin » -> name");
  assert(Core.guessSelectionCategory('tremblay') === 'lastName', "sélection « tremblay » -> lastName");
  assert(Core.detectEntities('m. tremblay est venu').some(e => e.category === 'lastName' && e.value === 'tremblay'), "auto : « m. tremblay » détecté");
  assert(Core.detectEntities('mme gagnon a appelé').some(e => e.value === 'gagnon'), "auto : « mme gagnon » détecté");
  assert(!Core.detectEntities('il me dit que tout va bien').some(e => e.category === 'lastName'), "auto : pronom « me » sans point = aucun faux positif");
  assert(!Core.detectEntities('on parle de tomates et de maisons').length, "auto : texte courant en minuscules = rien détecté");
}

console.log('\n--- D. Va-et-vient restauration (format des règles intact) ---');
{
  const rules = Core.buildRules([{ value: 'Ontario', category: 'province' }, { value: 'Julie Morin inc.', category: 'organization' }], { mode: 'pseudo', existing: [] });
  assert(rules.every(r => r.original && r.replacement && r.category), "règles {original, replacement, category}");
  const txt = 'Siège en Ontario chez Julie Morin inc. ce matin.';
  const anon = Core.anonymize(txt, rules, []);
  assert(!/Ontario|Julie Morin/.test(anon), "texte anonymisé sans vraies données");
  const res = Core.restore(anon, rules);
  const restored = typeof res === 'string' ? res : (res.text || res.restored || '');
  assert(restored === txt, "restauration identique au texte d'origine");
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
