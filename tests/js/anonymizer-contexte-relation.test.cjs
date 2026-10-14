// tests/js/anonymizer-contexte-relation.test.cjs
// Defaut de CONFIDENTIALITE #3098 : un prenom en MINUSCULES introduit par un mot de relation
// (« son conjoint marc », « ma collegue julie ») n'etait NI detecte NI signale, parce que toute
// la detection de noms exige une majuscule initiale (\p{Lu}) ou un titre de civilite. Un prenom
// reel laisse en clair dans le texte envoye a l'IA est la pire defaillance possible pour un outil
// de confidentialite (invisible pour la personne qui copie le texte « anonymise »).
//
// Ce fichier verrouille le correctif : detection en CONTEXTE DE RELATION (determinant + nom de
// relation + prenom, meme minuscule), haute precision, faux positifs bornes.
// Executer : node tests/js/anonymizer-contexte-relation.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');
const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/anonymiseur/anonymizer-core.js'), 'utf8');
const _mod = { exports: {} };
new Function('module', src)(_mod);
const A = _mod.exports;

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }
const norm = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
// Un nom detecte, quelle que soit sa categorie (name / firstName / lastName), dont UNE des
// composantes (normalisee) egale la cible normalisee.
const detecteNom = (ents, cible) => ents.some(e =>
  ['name', 'firstName', 'lastName'].includes(e.category) &&
  (norm(e.value) === norm(cible) ||
   norm(e.value).split(/[\s'’-]+/).includes(norm(cible))));

// ============================================================================================
// PARTIE A — Le prenom minuscule en contexte de relation DOIT etre detecte (le bug).
// ============================================================================================
console.log('\n--- A. Prenom minuscule en contexte de relation (DOIT etre detecte) ---');
[
  ['Je vis avec mon conjoint marc depuis dix ans.', 'marc'],
  ['Son conjoint marc a appele hier.', 'marc'],
  ['Ma collegue julie est en conge.', 'julie'],
  ['Mon fils thomas commence l\'ecole.', 'thomas'],
  ['Ma fille rose adore lire.', 'rose'],
  ['J\'en ai parle a mon patron luc.', 'luc'],
  ['Mon voisin jean-francois tond sa pelouse.', 'jean-francois'],
  ['Ma soeur genevieve arrive demain.', 'genevieve'],
  ['Mon oncle rejean habite a cote.', 'rejean'],
].forEach(([t, cible]) => {
  assert(detecteNom(A.detectEntities(t), cible), `« ${cible} » detecte dans « ${t} »`);
});

// ============================================================================================
// PARTIE B — Anti-faux-positifs : contexte de relation SUIVI D'UN MOT COURANT, et homographes.
// Aucun de ces cas ne doit produire une entite de type nom/prenom.
// ============================================================================================
console.log('\n--- B. Anti-faux-positifs (ne DOIT rien detecter comme nom) ---');
const aucunNom = (t) => !A.detectEntities(t).some(e => ['name', 'firstName', 'lastName'].includes(e.category));
[
  'Mon conjoint travaille de nuit.',
  'Mon fils aine part en voyage.',
  'Ma fille unique habite ici.',
  'Mon patron dit toujours oui.',
  'Ma collegue arrive demain.',
  'Mon pere est a la retraite.',
  'Ma soeur cadette etudie encore.',
  'Mon frere jumeau reste proche.',
  // Homographes : le mot ne suit AUCUN mot de relation -> jamais un prenom.
  'J\'ai jete le marc de cafe.',
  'Il porte un jean bleu.',
  'Une claire allusion au reglement.',
  'La rose rouge est fanee.',
].forEach((t) => assert(aucunNom(t), `aucun nom dans « ${t} »`));

// ============================================================================================
// PARTIE C — Bout a bout : le prenom minuscule est bien REMPLACE (anonymise), puis RESTITUE.
// ============================================================================================
console.log('\n--- C. Anonymisation + restauration bout a bout ---');
{
  const texte = 'Je vis avec mon conjoint marc depuis dix ans.';
  const ents = A.detectEntities(texte);
  let rules = A.buildRules(ents.map(e => ({ value: e.value, category: e.category })), { mode: 'pseudo' });
  if (A.relinkEmails) rules = A.relinkEmails(rules);
  const anon = A.anonymize(texte, rules);
  // « marc » (en tant que mot borne) ne doit plus apparaitre tel quel dans la sortie.
  const marcSurvit = /(?<![\p{L}])marc(?![\p{L}])/iu.test(anon);
  assert(!marcSurvit, `« marc » n'est plus en clair apres anonymisation (sortie : « ${anon} »)`);
  const restaure = A.restore(anon, rules).text;
  assert(/(?<![\p{L}])marc(?![\p{L}])/iu.test(restaure), '« marc » est bien restitue a la restauration');
}

// ============================================================================================
// PARTIE D — Non-regression : la forme CAPITALISEE (deja couverte) marche toujours.
// ============================================================================================
console.log('\n--- D. Non-regression forme capitalisee ---');
{
  const ents = A.detectEntities('Mon conjoint Marc Tremblay a signe.');
  assert(ents.some(e => e.category === 'name' && e.value.replace(/\s+/g, ' ') === 'Marc Tremblay'),
    '« Marc Tremblay » (capitalise) toujours detecte comme nom complet');
}

// ============================================================================================
// PARTIE E — Phrases RÉALISTES (le type de texte collé à une formation) : contexte de relation
// suivi d'un mot courant, d'une élision, d'une préposition ou d'une ponctuation. Aucun nom.
// ============================================================================================
console.log('\n--- E. Phrases réalistes anti-faux-positifs (ne DOIT rien detecter comme nom) ---');
[
  'Mon conjoint et moi avons deux enfants.',
  'Ma fille de six ans est malade.',
  'Mon patron m\'a convoqué ce matin.',
  'Ma mère adore son jardin.',
  'Mon voisin a un chien.',
  'Le patron de la compagnie est absent.',
  'Son amie d\'enfance lui manque.',
  'Notre fille revient demain.',
  'Mon père travaille encore.',
  'J\'ai vu mon ex.',
].forEach((t) => assert(aucunNom(t), `aucun nom dans « ${t} »`));

// ============================================================================================
// PARTIE F — Rappel supplémentaire : prénom minuscule bien détecté, y compris hyphené.
// ============================================================================================
console.log('\n--- F. Rappel supplementaire ---');
[
  ['mon chum steve est parti tot', 'steve'],
  ['ma blonde sophie adore ca', 'sophie'],
  ['mon ex martin a rappele', 'martin'],
  ['ma collegue marie-helene est geniale', 'marie-helene'],
  // Prénom CAPITALISÉ isolé (sans nom de famille) : non capté par 2/2b, doit l'être par 2d.
  ['Mon conjoint Marc est parti.', 'Marc'],
  ['Ma fille Rose adore lire.', 'Rose'],
].forEach(([t, cible]) => assert(detecteNom(A.detectEntities(t), cible), `« ${cible} » detecte dans « ${t} »`));

// ============================================================================================
// PARTIE G — Défauts trouvés par la revue adversariale (Codex) : verbes du quotidien pris pour
// un prénom, et DOUBLE-COMPTAGE quand le prénom capitalisé fait déjà partie d'un nom complet.
// ============================================================================================
console.log('\n--- G. Revue adversariale : verbes du quotidien + anti-doublon capitalisé ---');
[
  'Mon conjoint dort encore.',
  'Ma fille mange une pomme.',
  'Mon fils lit un livre.',
  'Mon père regarde la télé.',
  'Ma mère parle fort.',
  'Mon frère voyage souvent.',
  'Ma soeur danse le samedi.',
].forEach((t) => assert(aucunNom(t), `verbe non pris pour un prenom : « ${t} »`));
{
  // « Ma collègue Sophie Durand sera présente. » : le nom complet capitalisé est déjà capté par
  // les étapes 2/2b ; l'étape 2d ne doit PAS y ajouter un prénom isolé « Sophie » (doublon).
  const ents = A.detectEntities('Ma collègue Sophie Durand sera présente.');
  assert(ents.some(e => e.category === 'name' && e.value.replace(/\s+/g, ' ') === 'Sophie Durand'),
    '« Sophie Durand » détecté comme nom complet');
  const sophieIsoles = ents.filter(e => e.category === 'firstName' && norm(e.value) === 'sophie').length;
  assert(sophieIsoles === 0, `aucun prénom isolé « Sophie » en doublon (trouvé ${sophieIsoles})`);
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
