// tests/js/constructeur-prompts-uniform-inplace-masking.test.cjs
// Round 149 (2026-07-31, passe adversariale) : DÉFAUT #2 - incohérence entre les champs.
//
// Avant ce correctif, le masquage EN PLACE (récapitulatif + retour, sans panneau) n'existait QUE
// pour #cpTaskObject (bouton #cpAnonToggle). Les 5 autres champs surveillés (cpExamples,
// cpPersonaCustom, cpAudienceCustom, cpVerbCustom, cpConstraintCustom) passaient TOUJOURS par
// l'ancien flux à 2 zones : le bouton « Masquer mes infos → » du bandeau proactif ouvrait le
// panneau partagé (openAnonWithTask), sans jamais modifier le champ directement tant que la
// personne n'avait pas cliqué « Insérer ».
//
// Ce banc d'essai EXÉCUTE le vrai fichier et prouve à l'exécution que :
//   (a) pour un des 5 champs (cpExamples, représentatif), cliquer « Masquer mes infos → » dans le
//       bandeau MASQUE DIRECTEMENT le contenu du champ (comme #cpAnonToggle pour la Tâche) - le
//       même mécanisme maskFieldInPlace() est réellement invoqué, pas une réimplémentation ;
//   (b) le bandeau se referme après ce clic (l'utilisateur n'a plus besoin d'un panneau) ;
//   (c) à l'inverse, un gabarit de carte personnalisée (cpCardTemplate-*) - hors du périmètre des 6
//       champs statiques unifiés, justification documentée dans prompt-anon-panel.js - continue de
//       passer par l'ancien parcours : cliquer son bouton « Masquer mes infos → » n'altère PAS
//       directement le contenu du champ (il ouvre le panneau, comme avant round 149).
// Lancer avec : node tests/js/constructeur-prompts-uniform-inplace-masking.test.cjs
// Auteur : MEMORA solutions, https://memora.solutions
const fs = require('fs');
const path = require('path');

let pass = 0;
let fail = 0;

function assert(condition, label) {
  if (condition) {
    console.log('  ✅ ' + label);
    pass++;
  } else {
    console.log('  ❌ ' + label);
    fail++;
  }
}

// Simule fidèlement insertBefore(node, ref) : après l'appel, node devient le frère PRÉCÉDENT
// immédiat de ref - donc node.nextElementSibling === ref, exactement comme dans un vrai DOM.
// C'est cette propriété que checkEntities()/handleMaskBannerClick() utilisent pour retrouver
// quel champ est concerné par le bandeau partagé.
function makeField(id) {
  const listeners = {};
  const field = {
    id,
    value: '',
    style: {},
    listeners,
    parentNode: {
      insertBefore(node, ref) { node.nextElementSibling = ref; }
    },
    addEventListener(type, cb) { (listeners[type] = listeners[type] || []).push(cb); },
    dispatchEvent(evt) { (listeners[evt.type] || []).forEach((cb) => cb(evt)); },
    focus() {},
    scrollIntoView() {}
  };
  return field;
}

const createdElements = [];
function makeElementStub() {
  const listeners = {};
  const el = {
    style: {},
    attrs: {},
    textContent: '',
    nextElementSibling: null,
    setAttribute(name, value) { this.attrs[name] = value; },
    appendChild() {},
    addEventListener(type, cb) { (listeners[type] = listeners[type] || []).push(cb); },
    dispatchEvent(evt) { (listeners[evt.type] || []).forEach((cb) => cb(evt)); },
    click() { (listeners.click || []).forEach((cb) => cb(undefined)); },
    remove() {}
  };
  createdElements.push(el);
  return el;
}

const fields = {
  // cpExamples : représentatif des 5 champs qui subissaient le défaut #2.
  cpExamples: makeField('cpExamples'),
  // cpCardTemplate-xxx : hors périmètre, doit rester sur l'ancien parcours (contraste).
  'cpCardTemplate-abc123': makeField('cpCardTemplate-abc123')
};

let domReady;
// Les gabarits de carte (cpCardTemplate-*) sont surveillés par une écoute DÉLÉGUÉE sur `document`
// (montés/démontés dynamiquement par Alpine - voir round 119) : 'blur' ne bulle pas, elle est donc
// posée en phase de CAPTURE. Il faut la capturer ici comme le fait déjà
// constructeur-prompts-cardtemplate-pii-guard.test.cjs, sinon le blur du gabarit de carte ne
// déclenche jamais checkEntities() dans ce faux DOM.
const delegatedBlur = [];
global.document = {
  addEventListener(type, cb) {
    if (type === 'DOMContentLoaded') domReady = cb;
    else if (type === 'blur') delegatedBlur.push(cb);
  },
  getElementById(id) { return fields[id] || null; },
  createElement() { return makeElementStub(); }
};

global.window = {
  promptBuilderConfig: { i18n: {} },
  dispatchEvent() {},
  AnonymizerCore: {
    detectEntities(txt) {
      const entities = [];
      if (/jean\.dupont@exemple\.com/.test(txt)) {
        entities.push({ value: 'jean.dupont@exemple.com', category: 'email', label: 'Courriel' });
      }
      return entities;
    },
    buildRules(selections) {
      return selections.map((s, i) => ({ id: 'r' + i, original: s.value, replacement: '[MASQUE_' + i + ']', category: s.category }));
    },
    anonymize(text, rules) {
      let out = text;
      rules.forEach((r) => { out = out.split(r.original).join(r.replacement); });
      return out;
    }
  }
};

global.sessionStorage = { getItem() { return null; }, removeItem() {} };
global.CustomEvent = class { constructor(t, o) { this.type = t; this.detail = o && o.detail; } };
global.Event = class { constructor(t, o) { this.type = t; this.bubbles = o && o.bubbles; } };

const sourcePath = path.join(__dirname, '../../public/assets/tools/constructeur-prompts/prompt-anon-panel.js');
new Function(fs.readFileSync(sourcePath, 'utf8'))();
domReady();

// Ordre de création connu du fichier source (dans ce bloc, exécuté une seule fois à l'init) :
// warnBanner (div, 0), textSpan (span, 1), anonBtn (button, 2), dismissBtn (button, 3).
const warnBanner = createdElements[0];
const anonBtn = createdElements[2];
assert(createdElements.length >= 4, 'préalable : le bandeau partagé (4 éléments) a bien été créé une seule fois à l\'initialisation');

// --- (a)+(b) : champ cpExamples avec une vraie fuite -> bandeau -> clic « Masquer mes infos → »
// masque le champ DIRECTEMENT, sans jamais passer par le panneau à 2 zones. ---
{
  const texteAvecFuite = 'Exemple 1 :\nEntrée : contacte jean.dupont@exemple.com\nSortie : ...';
  fields.cpExamples.value = texteAvecFuite;
  fields.cpExamples.dispatchEvent({ type: 'blur' });

  assert(warnBanner.nextElementSibling === fields.cpExamples, 'préalable : le bandeau est bien positionné juste avant cpExamples (fuite détectée)');

  anonBtn.click();

  assert(
    fields.cpExamples.value.indexOf('jean.dupont@exemple.com') === -1,
    'défaut #2 corrigé : cliquer « Masquer mes infos → » masque DIRECTEMENT le contenu de cpExamples (comme #cpAnonToggle pour la Tâche)'
  );
  assert(
    fields.cpExamples.value.indexOf('[MASQUE_') !== -1,
    'défaut #2 corrigé : le champ contient bien le texte produit par AnonymizerCore.anonymize() - masquage réel, pas une promesse'
  );
  assert(warnBanner.style.display === 'none', 'le bandeau se referme après la prise en charge (l\'utilisateur n\'a plus besoin d\'un panneau)');
}

// --- (c) : contraste - un gabarit de carte personnalisée reste sur l'ancien parcours (justifié :
// champ dynamique Alpine, hors périmètre des 6 champs statiques unifiés). ---
{
  const card = fields['cpCardTemplate-abc123'];
  const texteAvecFuite = 'Relance jean.dupont@exemple.com au sujet du dossier';
  card.value = texteAvecFuite;
  delegatedBlur.forEach((cb) => cb({ target: card }));

  assert(warnBanner.nextElementSibling === card, 'préalable : le bandeau est bien repositionné devant le gabarit de carte');

  anonBtn.click();

  assert(
    card.value === texteAvecFuite,
    'gabarits de carte HORS périmètre (justifié) : le clic n\'altère PAS directement le champ - il ouvre encore l\'ancien panneau, comme avant le round 149'
  );
}

console.log(pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
