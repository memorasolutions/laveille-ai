// tests/js/constructeur-prompts-recap-gender-agreement.test.cjs
// Round 149 (2026-07-31, passe adversariale) : DÉFAUT #3 - accord grammatical figé (cosmétique).
//
// Avant ce correctif, resumerMasquage() affichait « une adresse a été masqué » (masculin figé)
// pour des entités FÉMININES (adresse, adresse IP, date). Ce banc d'essai EXÉCUTE le vrai
// resumerMasquage() (via un déclenchement réel de maskFieldInPlace(), pas une réimplémentation) et
// prouve que l'accord est maintenant correct :
//   - une seule catégorie FÉMININE (répétée ou non) -> accord féminin ;
//   - une seule catégorie MASCULINE (répétée ou non) -> accord masculin (non-régression) ;
//   - plusieurs catégories DIFFÉRENTES jointes par « et » -> masculin pluriel générique (règle
//     grammaticale française standard pour un groupe mixte), non-régression du comportement déjà
//     couvert par constructeur-prompts-inplace-masking.test.cjs.
// Lancer avec : node tests/js/constructeur-prompts-recap-gender-agreement.test.cjs
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

function makeField(id) {
  const listeners = {};
  return {
    id,
    value: '',
    style: {},
    attrs: {},
    addEventListener(type, cb) { (listeners[type] = listeners[type] || []).push(cb); },
    dispatchEvent(evt) { (listeners[evt.type] || []).forEach((cb) => cb(evt)); },
    click() { (listeners.click || []).forEach((cb) => cb(undefined)); },
    setAttribute(name, value) { this.attrs[name] = value; },
    getAttribute(name) { return Object.prototype.hasOwnProperty.call(this.attrs, name) ? this.attrs[name] : null; },
    focus() {},
    scrollIntoView() {},
    textContent: ''
  };
}

function makeElementStub() {
  return {
    style: {},
    setAttribute() {},
    appendChild() {},
    addEventListener() {},
    dispatchEvent() {},
    remove() {},
    textContent: '',
    nextElementSibling: null
  };
}

const fields = {
  cpAnonToggle: makeField('cpAnonToggle'),
  cpTaskObject: makeField('cpTaskObject'),
  cpAnonRecap: makeField('cpAnonRecap'),
  cpAnonRecapText: makeField('cpAnonRecapText'),
  cpAnonUndo: makeField('cpAnonUndo')
};
fields.cpAnonRecap.style.display = 'none';
fields.cpAnonUndo.style.display = 'none';

let domReady;
global.document = {
  addEventListener(type, cb) { if (type === 'DOMContentLoaded') domReady = cb; },
  getElementById(id) { return fields[id] || null; },
  createElement() { return makeElementStub(); }
};

// i18n.anonPluralLabels reproduit EXACTEMENT le format PHP ($anonPluralLabels, 3e élément = accord
// féminin bool) - voir Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php.
const i18n = {
  anonPluralLabels: {
    'Adresse': ['adresse', 'adresses', true],
    'Courriel': ['courriel', 'courriels', false]
  },
  anonMaskedSingular: 'a été masqué',
  anonMaskedSingularFeminine: 'a été masquée',
  anonMaskedPlural: 'ont été masqués',
  anonMaskedPluralFeminine: 'ont été masquées',
  anonAnd: 'et'
};

let detectResult = [];
global.window = {
  promptBuilderConfig: { i18n: i18n },
  dispatchEvent() {},
  AnonymizerCore: {
    detectEntities() { return detectResult; },
    buildRules(selections) { return selections.map((s, i) => ({ id: 'r' + i, original: s.value, replacement: '[M' + i + ']', category: s.category })); },
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

function masquerEtLireRecap(texte, entities) {
  fields.cpTaskObject.value = texte;
  detectResult = entities;
  fields.cpAnonToggle.click();
  return fields.cpAnonRecapText.textContent;
}

assert(
  masquerEtLireRecap('Mon adresse : 123 rue Test', [{ value: '123 rue Test', category: 'address', label: 'Adresse' }]) === '1 adresse a été masquée.',
  'défaut #3 corrigé : une seule catégorie FÉMININE (Adresse) -> accord féminin singulier'
);

assert(
  masquerEtLireRecap('Adresse A et adresse B', [
    { value: 'Adresse A', category: 'address', label: 'Adresse' },
    { value: 'adresse B', category: 'address', label: 'Adresse' }
  ]) === '2 adresses ont été masquées.',
  'défaut #3 corrigé : une seule catégorie FÉMININE répétée -> accord féminin pluriel (pas masculin générique)'
);

assert(
  masquerEtLireRecap('jean@exemple.com', [{ value: 'jean@exemple.com', category: 'email', label: 'Courriel' }]) === '1 courriel a été masqué.',
  'non-régression : une seule catégorie MASCULINE -> accord masculin singulier inchangé'
);

assert(
  masquerEtLireRecap('123 rue Test, jean@exemple.com', [
    { value: '123 rue Test', category: 'address', label: 'Adresse' },
    { value: 'jean@exemple.com', category: 'email', label: 'Courriel' }
  ]) === '1 adresse et 1 courriel ont été masqués.',
  'non-régression : catégories MIXTES (féminin + masculin) jointes par « et » -> masculin pluriel générique (règle française standard)'
);

console.log(pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
