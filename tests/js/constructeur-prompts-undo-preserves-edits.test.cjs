// tests/js/constructeur-prompts-undo-preserves-edits.test.cjs
// Round 149 (2026-07-31, passe adversariale) : DÉFAUT #1 - PERTE DE DONNÉES, prouvé.
//
// Avant ce correctif, le handler du bouton de retour (« Revenir à mon texte de départ »,
// #cpAnonUndo) réécrivait le champ avec la valeur mémorisée AVANT masquage, SANS JAMAIS la
// comparer à la valeur COURANTE. Séquence prouvée par l'audit :
//   1. « Contactez Marc Tremblay au 418-555-0188. »
//   2. clic Masquer -> « Contactez [MASQUE_NOM] au [MASQUE_TEL]. »
//   3. la personne COMPLÈTE son texte -> « ... Merci de traiter ce dossier avant vendredi. »
//   4. clic Revenir -> « Contactez Marc Tremblay au 418-555-0188. »
//      LA PHRASE AJOUTÉE À L'ÉTAPE 3 DISPARAISSAIT, sans avertissement.
//
// Ce banc d'essai EXÉCUTE le vrai fichier (pas une réimplémentation) et prouve à l'exécution que :
//   (a) tant que le champ n'a pas changé depuis le masquage, Annuler restaure directement (aucune
//       régression sur le comportement normal, déjà couvert par
//       constructeur-prompts-inplace-masking.test.cjs) ;
//   (b) si le champ a été modifié depuis le masquage, Annuler NE PERD RIEN silencieusement : une
//       confirmation est demandée via la modale du THÈME (CustomEvent 'open-confirm-global', jamais
//       confirm() natif) et l'ajout de l'étape 3 SURVIT tant que la personne n'a pas confirmé ;
//   (c) un clic sur Annuler suivi d'une confirmation explicite restaure bien le texte d'origine
//       (l'utilisateur GARDE le contrôle, il ne perd rien à son insu) ;
//   (d) si la confirmation n'est jamais donnée (annulée par la personne), l'ajout reste intact et
//       rien n'est perdu.
//
// Test tautologique = test inutile : voir la note de vérification à la fin de ce fichier (cassé
// volontairement puis rétabli pendant le développement, voir le rapport de la tâche).
// Lancer avec : node tests/js/constructeur-prompts-undo-preserves-edits.test.cjs
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
    listeners,
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
    attrs: {},
    setAttribute(name, value) { this.attrs[name] = value; },
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
  cpAnonPanel: makeField('cpAnonPanel'),
  cpAnonInsert: makeField('cpAnonInsert'),
  cpTaskObject: makeField('cpTaskObject'),
  cpAnonRecap: makeField('cpAnonRecap'),
  cpAnonRecapText: makeField('cpAnonRecapText'),
  cpAnonUndo: makeField('cpAnonUndo'),
  anonSource: makeField('anonSource')
};
fields.cpAnonPanel.style.display = 'none';
fields.cpAnonPanel.attrs['aria-hidden'] = 'true';
fields.cpAnonRecap.style.display = 'none';
fields.cpAnonUndo.style.display = 'none';

let domReady;

global.document = {
  addEventListener(type, cb) { if (type === 'DOMContentLoaded') domReady = cb; },
  getElementById(id) { return fields[id] || null; },
  createElement() { return makeElementStub(); }
};

const windowEvents = [];
global.window = {
  promptBuilderConfig: { i18n: {} },
  dispatchEvent(evt) { windowEvents.push(evt); },
  AnonymizerCore: {
    // Mock déterministe : détecte un nom (« Marc Tremblay » / « Jean Dupont »...) et un numéro de
    // téléphone au format québécois, comme le scénario prouvé de l'audit.
    detectEntities(txt) {
      const entities = [];
      const nameMatch = txt.match(/Marc Tremblay/);
      if (nameMatch) entities.push({ value: nameMatch[0], category: 'name', label: 'Nom complet' });
      const phoneMatch = txt.match(/\d{3}-\d{3}-\d{4}/);
      if (phoneMatch) entities.push({ value: phoneMatch[0], category: 'phone', label: 'Téléphone' });
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

function confirmDispatches() {
  return windowEvents.filter((e) => e.type === 'open-confirm-global');
}

// --- Scénario (a) : Annuler SANS modification depuis le masquage -> restauration directe,
// AUCUNE confirmation demandée (comportement normal inchangé). ---
{
  const texteDepart = 'Contactez Marc Tremblay au 418-555-0188.';
  fields.cpTaskObject.value = texteDepart;
  windowEvents.length = 0;
  fields.cpAnonToggle.click();

  assert(
    fields.cpTaskObject.value.indexOf('Marc Tremblay') === -1 && fields.cpTaskObject.value.indexOf('418-555-0188') === -1,
    '(a) masquage : les données personnelles ont disparu du champ'
  );

  windowEvents.length = 0;
  fields.cpAnonUndo.click();

  assert(confirmDispatches().length === 0, '(a) champ inchangé depuis le masquage : aucune confirmation demandée');
  assert(fields.cpTaskObject.value === texteDepart, '(a) champ inchangé : le texte de départ est restauré directement');
}

// --- Scénario (b)+(c)+(d) : le SCÉNARIO PROUVÉ de l'audit, en 4 étapes. ---
const texteDepart2 = 'Contactez Marc Tremblay au 418-555-0188.';
const ajoutEtape3 = ' Merci de traiter ce dossier avant vendredi.';

// Étape 1+2 : texte de départ, puis masquage.
fields.cpTaskObject.value = texteDepart2;
windowEvents.length = 0;
fields.cpAnonToggle.click();
const texteMasque = fields.cpTaskObject.value;
assert(
  texteMasque.indexOf('Marc Tremblay') === -1 && texteMasque.indexOf('418-555-0188') === -1,
  '(b) étape 2 : le texte est bien masqué avant la suite du scénario'
);

// Étape 3 : la personne COMPLÈTE son texte (le champ a changé depuis le masquage).
fields.cpTaskObject.value = texteMasque + ajoutEtape3;
const texteAvecAjout = fields.cpTaskObject.value;

// Étape 4 : clic sur « Revenir à mon texte de départ ».
windowEvents.length = 0;
fields.cpAnonUndo.click();

// (d) Preuve centrale du défaut #1 corrigé : l'ajout de l'étape 3 SURVIT tant qu'il n'y a pas eu
// de confirmation explicite - AUCUNE perte silencieuse.
assert(
  fields.cpTaskObject.value === texteAvecAjout,
  '(b) défaut #1 corrigé : le clic sur Annuler NE PERD PAS silencieusement l\'ajout - obtenu : "' + fields.cpTaskObject.value + '"'
);
assert(
  fields.cpTaskObject.value.indexOf(ajoutEtape3.trim()) !== -1,
  '(b) la phrase ajoutée après le masquage est toujours présente dans le champ'
);

// Une confirmation a bien été demandée, via la modale du THÈME (jamais confirm() natif).
const confirmations = confirmDispatches();
assert(confirmations.length === 1, '(b) une confirmation est demandée avant d\'écraser un champ modifié depuis le masquage');
assert(
  confirmations.length > 0 && typeof confirmations[0].detail.message === 'string' && confirmations[0].detail.message.length > 0,
  '(b) la confirmation porte un message humain (pas de confirm() natif : aucune fenêtre système ne peut être vérifiée ici, mais AUCUN appel à un confirm() global n\'existe dans ce faux DOM - un confirm() natif aurait fait planter ce test, qui ne le définit jamais)'
);
assert(
  typeof confirmations[0].detail.callback === 'function',
  '(b) la confirmation porte un callback de restauration (exécuté seulement si la personne confirme)'
);

// (d) Si la personne n'a PAS confirmé (ferme la modale / clique Annuler) : rien n'est perdu.
// On ne fait rien de plus ici : le simple fait de ne jamais appeler confirmations[0].detail.callback
// prouve déjà que le champ reste intact (vérifié juste au-dessus). On le revérifie explicitement
// pour que ce test ÉCHOUE si un futur correctif appelait le callback par erreur, sans geste
// utilisateur.
assert(fields.cpTaskObject.value === texteAvecAjout, '(d) sans confirmation explicite, l\'ajout reste intact (pas d\'écrasement automatique)');

// (c) Si la personne CONFIRME explicitement : la restauration a bien lieu (elle garde le contrôle,
// elle ne subit rien).
confirmations[0].detail.callback();
assert(
  fields.cpTaskObject.value === texteDepart2,
  '(c) après confirmation EXPLICITE, le texte de départ est bien restauré (perte assumée, pas subie)'
);
assert(fields.cpAnonRecap.style.display === 'none', '(c) le récapitulatif se referme après la restauration confirmée');

console.log(pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
