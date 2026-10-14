// tests/js/anonymizer-courriel-coherent.test.cjs
// #3098 bug 2 - pseudonyme incohérent nom <-> courriel. Deux formes mesurées :
// (a) courriel à INITIALE (« m.tremblay@ ») : l'initiale restait « m » au lieu de suivre le faux
//     prénom (« François » -> devrait donner « f.tremblay@ »).
// (b) prénom COMPOSÉ dont le jeton est CONCATÉNÉ dans le courriel (« marieclaude.tremblay@ ») :
//     ce jeton recevait un faux prénom DIFFÉRENT de celui du nom affiché.
// Le faux est aléatoire : on vérifie la COHÉRENCE (le prénom du faux courriel correspond au faux
// prénom du nom), pas une valeur précise. Exécuter : node tests/js/anonymizer-courriel-coherent.test.cjs
const fs = require('fs');
const path = require('path');
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/anonymiseur/anonymizer-core.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const A = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }
const norm = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/['’\s-]/g, '');

const N = 60; // le faux est aléatoire : répéter pour attraper une incohérence probabiliste
function regles(texte) {
  const ents = A.detectEntities(texte);
  let rules = A.buildRules(ents.map(e => ({ value: e.value, category: e.category })), { mode: 'pseudo' });
  if (A.relinkEmails) rules = A.relinkEmails(rules);
  return rules;
}
function fauxPrenomDuNom(rules) {
  const r = rules.find(x => x.category === 'name');
  return r ? norm(r.replacement.split(/\s+/)[0]) : null;
}
function prenomLocalCourriel(rules) {
  const r = rules.find(x => x.category === 'email');
  if (!r) return null;
  const local = r.replacement.slice(0, r.replacement.indexOf('@'));
  return norm(local.split(/[._+-]/)[0]);
}

console.log('\n--- A. Courriel à initiale : l\'initiale suit le faux prénom ---');
{
  let incoherences = 0;
  for (let i = 0; i < N; i++) {
    const rules = regles('Marc Tremblay, écrire à m.tremblay@example.com');
    const fp = fauxPrenomDuNom(rules), loc = prenomLocalCourriel(rules);
    // l'initiale du courriel (1 lettre) doit être la 1re lettre du faux prénom
    if (!fp || !loc || loc.length !== 1 || loc[0] !== fp[0]) incoherences++;
  }
  assert(incoherences === 0, `initiale du courriel cohérente avec le faux prénom (${N - incoherences}/${N})`);
}

console.log('\n--- B. Prénom composé concaténé : le courriel porte le MÊME faux prénom que le nom ---');
{
  let incoherences = 0;
  for (let i = 0; i < N; i++) {
    const rules = regles('Marie-Claude Tremblay, écrire à marieclaude.tremblay@example.com');
    const fp = fauxPrenomDuNom(rules), loc = prenomLocalCourriel(rules);
    if (!fp || !loc || loc !== fp) incoherences++;
  }
  assert(incoherences === 0, `prénom du courriel == faux prénom du nom (${N - incoherences}/${N})`);
}

console.log('\n--- C. Non-régression : courriel prénom.nom standard reste cohérent + aucune fuite ---');
{
  let ko = 0;
  for (let i = 0; i < N; i++) {
    const texte = 'Jean Tremblay. Écrire à jean.tremblay@example.com';
    const rules = regles(texte);
    const anon = A.anonymize(texte, rules);
    if (/jean/i.test(anon) || /tremblay/i.test(anon)) ko++;              // aucune fuite du vrai nom
    if (prenomLocalCourriel(rules) !== fauxPrenomDuNom(rules)) ko++;      // cohérent
  }
  assert(ko === 0, `« Jean Tremblay » + jean.tremblay@ : cohérent et sans fuite (${N - ko}/${N} sains... 0 attendu en échec)`);
}

console.log('\n--- D. Revue Codex : deux personnes dont la forme concaténée entre en collision ---');
{
  // « Annabelle » (personne 1) et « Anna-Belle » (personne 2) se concatènent toutes deux en
  // « annabelle ». Le courriel « annabelle.roy@ » appartient à la personne 1 : il doit suivre SON
  // faux prénom (premier gagne), jamais mélanger les deux identités.
  const rules = [
    { category: 'name', original: 'Annabelle Roy', replacement: 'Julie Fortin' },
    { category: 'name', original: 'Anna-Belle Gagnon', replacement: 'Sophie Morin' },
    { category: 'email', original: 'annabelle.roy@example.com', replacement: 'x.y@example.net' },
  ];
  const r = A.relinkEmails(rules).find(x => x.category === 'email');
  const local = norm(r.replacement.slice(0, r.replacement.indexOf('@')));
  // le prénom du courriel doit être « julie » (personne 1), pas « sophie » (personne 2)
  assert(local.startsWith('julie') && !local.includes('sophie'),
    `« annabelle.roy@ » suit la personne 1 (julie.fortin), sans mélange : « ${r.replacement} »`);
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
