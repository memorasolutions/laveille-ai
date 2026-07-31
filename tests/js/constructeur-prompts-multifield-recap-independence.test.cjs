// tests/js/constructeur-prompts-multifield-recap-independence.test.cjs
// Round 150 (2026-07-31) : PERTE DE DONNÉES PROUVÉE PAR EXÉCUTION - correctif.
//
// Séquence prouvée par le banc d'essai qui a motivé ce correctif :
//   1. Masquer le champ Tâche (#cpTaskObject) -> « Contactez [faux nom] pour le dossier. »
//   2. Masquer ENSUITE le champ Rôle personnalisé (#cpPersonaCustom) via le bandeau anti-PII.
//   3. Cliquer « Revenir à mon texte de départ » (#cpAnonUndo).
// AVANT ce correctif : seul le Rôle était restauré. Le champ Tâche restait masqué, et il
// n'existait PLUS AUCUN moyen dans l'interface d'y revenir - #cpAnonRecap/#cpAnonUndo étaient un
// bloc UNIQUE, repositionné dynamiquement, lié à une seule variable globale (`currentRecapFieldId`) :
// masquer un 2e champ déplaçait ce bloc vers lui et faisait disparaître le récapitulatif/bouton
// Annuler du 1er champ, dont le texte d'origine restait pourtant en mémoire (maskState) mais
// devenait fonctionnellement INACCESSIBLE depuis l'interface.
//
// Ce banc d'essai EXÉCUTE le vrai fichier (pas une réimplémentation) et prouve à l'exécution que :
//   (a) masquer la Tâche PUIS le Rôle personnalisé laisse les DEUX récapitulatifs actifs
//       SIMULTANÉMENT, chacun avec son propre bouton Annuler fonctionnel ;
//   (b) annuler la Tâche ne touche PAS au Rôle (et inversement) ;
//   (c) la même preuve, étendue à TROIS champs (Tâche + Rôle + Audience) ;
//   (d) la séquence complète masquer -> annuler -> remasquer -> réannuler fonctionne sur DEUX
//       champs différents, sans fuite de gestionnaire de clic (un seul bouton Annuler par champ,
//       jamais dupliqué à chaque remasquage).
//
// Lancer avec : node tests/js/constructeur-prompts-multifield-recap-independence.test.cjs
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
// immédiat de ref - même pattern que constructeur-prompts-uniform-inplace-masking.test.cjs (déjà
// validé pour ce fichier source).
function makeField(id) {
  const listeners = {};
  return {
    id,
    value: '',
    style: {},
    attrs: {},
    parentNode: {
      insertBefore(node, ref) { node.nextElementSibling = ref; }
    },
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
  cpAnonToggle: makeField('cpAnonToggle'),
  cpAnonPanel: makeField('cpAnonPanel'),
  cpTaskObject: makeField('cpTaskObject'),
  cpAnonRecap: makeField('cpAnonRecap'),
  cpAnonRecapText: makeField('cpAnonRecapText'),
  cpAnonUndo: makeField('cpAnonUndo'),
  cpPersonaCustom: makeField('cpPersonaCustom'),
  cpAudienceCustom: makeField('cpAudienceCustom'),
  anonSource: makeField('anonSource')
};
fields.cpAnonPanel.style.display = 'none';
fields.cpAnonPanel.attrs['aria-hidden'] = 'true';
fields.cpAnonRecap.style.display = 'none';
fields.cpAnonUndo.style.display = 'none';

let domReady;
const documentBlurListeners = [];

global.document = {
  addEventListener(type, cb) {
    if (type === 'DOMContentLoaded') { domReady = cb; return; }
    if (type === 'blur') { documentBlurListeners.push(cb); }
  },
  getElementById(id) { return fields[id] || null; },
  createElement() { return makeElementStub(); }
};

const toasts = [];
global.window = {
  promptBuilderConfig: { i18n: {} },
  dispatchEvent(evt) { toasts.push(evt); },
  AnonymizerCore: {
    // Mock déterministe : une catégorie de PII par champ, pour distinguer sans ambiguïté quel
    // champ a réellement été masqué/restauré.
    detectEntities(txt) {
      const entities = [];
      if (/Marc Tremblay/.test(txt)) entities.push({ value: 'Marc Tremblay', category: 'name', label: 'Nom complet' });
      if (/jean\.dupont@exemple\.com/.test(txt)) entities.push({ value: 'jean.dupont@exemple.com', category: 'email', label: 'Courriel' });
      if (/418-555-0199/.test(txt)) entities.push({ value: '418-555-0199', category: 'phone', label: 'Téléphone' });
      return entities;
    },
    buildRules(selections) {
      return selections.map((s, i) => ({ id: 'r' + i, original: s.value, replacement: '[MASQUE_' + s.category.toUpperCase() + ']', category: s.category }));
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

// Ordre de création connu du fichier source (bandeau anti-PII, créé une seule fois à l'init) :
// warnBanner (div, 0), textSpan (span, 1), anonBtn (button, 2), dismissBtn (button, 3).
const warnBanner = createdElements[0];
const anonBtn = createdElements[2];
assert(createdElements.length === 4, 'préalable : seuls les 4 éléments du bandeau partagé ont été créés à l\'initialisation (aucun bloc récapitulatif créé tant qu\'aucun champ n\'a été masqué)');

// Masque un champ SURVEILLÉ (autre que la Tâche) via le parcours bandeau anti-PII -> maskFieldInPlace
// (round 149, chemin partagé) : blur avec PII, puis clic sur « Masquer mes infos → ».
function maskViaBanner(field) {
  field.dispatchEvent({ type: 'blur' });
  assert(warnBanner.nextElementSibling === field, 'préalable : le bandeau est bien positionné juste avant ' + field.id);
  anonBtn.click();
}

// ========================================================================================
// (a)+(b) : DEUX CHAMPS - Tâche puis Rôle personnalisé, exactement la séquence prouvée du bug.
// ========================================================================================
const texteTache = 'Contactez Marc Tremblay pour le dossier.';
const textePersona = 'Rédige comme jean.dupont@exemple.com le ferait.';

fields.cpTaskObject.value = texteTache;
toasts.length = 0;
fields.cpAnonToggle.click();

assert(fields.cpTaskObject.value.indexOf('Marc Tremblay') === -1, '(a) la Tâche est bien masquée');
assert(fields.cpAnonRecap.style.display === '', '(a) le récapitulatif de la Tâche (bloc statique) est affiché');
assert(fields.cpAnonUndo.style.display === '', '(a) le bouton Annuler de la Tâche est affiché');
const texteRecapTache = fields.cpAnonRecapText.textContent;
assert(texteRecapTache.length > 0, '(a) le récapitulatif de la Tâche porte un texte non vide');

const elementsAvantPersona = createdElements.length;
fields.cpPersonaCustom.value = textePersona;
toasts.length = 0;
maskViaBanner(fields.cpPersonaCustom);

assert(fields.cpPersonaCustom.value.indexOf('jean.dupont@exemple.com') === -1, '(a) le Rôle personnalisé est bien masqué à son tour');
assert(warnBanner.style.display === 'none', '(a) le bandeau se referme après la prise en charge du Rôle');

const nouveauxElements = createdElements.slice(elementsAvantPersona);
assert(nouveauxElements.length === 3, '(a) DRY : un bloc récapitulatif DYNAMIQUE (div + p + bouton) a été bâti pour le Rôle, jamais plus');
const personaUndo = nouveauxElements[2];

// ***** COEUR DE LA PREUVE : masquer le Rôle n'a PAS fait disparaître le récapitulatif de la Tâche. *****
assert(fields.cpAnonRecap.style.display === '', '(b) DÉFAUT CORRIGÉ : le récapitulatif de la Tâche est TOUJOURS affiché après avoir masqué le Rôle');
assert(fields.cpAnonUndo.style.display === '', '(b) DÉFAUT CORRIGÉ : le bouton Annuler de la Tâche est TOUJOURS visible après avoir masqué le Rôle');
assert(fields.cpAnonRecapText.textContent === texteRecapTache, '(b) DÉFAUT CORRIGÉ : le texte du récapitulatif de la Tâche n\'a pas été écrasé par celui du Rôle');

// Annuler la Tâche ne touche PAS au Rôle.
toasts.length = 0;
fields.cpAnonUndo.click();
assert(fields.cpTaskObject.value === texteTache, '(b) annuler la Tâche restaure bien son texte d\'origine (avec les vraies infos)');
assert(fields.cpAnonRecap.style.display === 'none', '(b) le récapitulatif de la Tâche se referme après son annulation');
assert(
  fields.cpPersonaCustom.value.indexOf('jean.dupont@exemple.com') === -1,
  '(b) DÉFAUT CORRIGÉ : annuler la Tâche NE RESTAURE PAS le Rôle - celui-ci reste masqué, intact'
);

// Annuler le Rôle (bouton dynamique, indépendant) restaure SEULEMENT le Rôle.
toasts.length = 0;
personaUndo.click();
assert(fields.cpPersonaCustom.value === textePersona, '(b) annuler le Rôle (son propre bouton) restaure bien SON texte d\'origine');

// ========================================================================================
// (c) : TROIS CHAMPS - Tâche + Rôle + Audience, tous les trois masqués simultanément.
// ========================================================================================
const textePersona2 = 'Rédige comme jean.dupont@exemple.com le ferait.';
const texteAudience2 = 'Public cible : joignable au 418-555-0199.';

fields.cpTaskObject.value = texteTache;
fields.cpAnonToggle.click();
fields.cpPersonaCustom.value = textePersona2;
maskViaBanner(fields.cpPersonaCustom);
const elementsAvantAudience = createdElements.length;
fields.cpAudienceCustom.value = texteAudience2;
maskViaBanner(fields.cpAudienceCustom);
const audienceEls = createdElements.slice(elementsAvantAudience);
assert(audienceEls.length === 3, '(c) un 3e bloc récapitulatif INDÉPENDANT a été bâti pour l\'Audience');
const audienceUndo = audienceEls[2];
// Le Rôle a déjà son propre contrôleur (créé au bloc (a)/(b) ci-dessus, capturé dans `personaUndo`)
// et getOrCreateRecapController() NE RECRÉE JAMAIS un bloc pour un champ déjà connu (voir la
// preuve DRY du bloc (d) plus bas : aucun nouvel élément créé au remasquage) - c'est donc TOUJOURS
// le même bouton `personaUndo` qui gère le Rôle ici.
const personaUndo2 = personaUndo;

assert(
  fields.cpTaskObject.value.indexOf('Marc Tremblay') === -1 &&
  fields.cpPersonaCustom.value.indexOf('jean.dupont@exemple.com') === -1 &&
  fields.cpAudienceCustom.value.indexOf('418-555-0199') === -1,
  '(c) les TROIS champs sont simultanément masqués'
);
assert(
  fields.cpAnonRecap.style.display === '' && fields.cpAnonUndo.style.display === '',
  '(c) le récapitulatif/bouton de la Tâche est TOUJOURS actif malgré 2 masquages ultérieurs'
);
assert(audienceUndo.style.display === '', '(c) le bouton Annuler de l\'Audience est visible');

// Annule les TROIS, dans un ordre différent de leur masquage (Audience -> Tâche -> Rôle), et
// vérifie que chaque annulation ne restaure QUE son propre champ.
audienceUndo.click();
assert(fields.cpAudienceCustom.value === texteAudience2, '(c) annuler l\'Audience restaure SON texte');
assert(fields.cpTaskObject.value.indexOf('Marc Tremblay') === -1, '(c) annuler l\'Audience NE restaure PAS la Tâche (toujours masquée)');
assert(fields.cpPersonaCustom.value.indexOf('jean.dupont@exemple.com') === -1, '(c) annuler l\'Audience NE restaure PAS le Rôle (toujours masqué)');

fields.cpAnonUndo.click();
assert(fields.cpTaskObject.value === texteTache, '(c) annuler la Tâche restaure SON texte');
assert(fields.cpPersonaCustom.value.indexOf('jean.dupont@exemple.com') === -1, '(c) annuler la Tâche NE restaure PAS le Rôle (toujours masqué)');

personaUndo2.click();
assert(fields.cpPersonaCustom.value === textePersona2, '(c) annuler le Rôle en dernier restaure bien SON texte');

// ========================================================================================
// (d) : masquer -> annuler -> remasquer -> réannuler, sur DEUX champs différents (Tâche + Rôle),
// sans fuite de gestionnaire de clic (le même bouton doit continuer de fonctionner correctement,
// jamais dupliqué).
// ========================================================================================
// --- Cycle complet sur la Tâche ---
fields.cpTaskObject.value = texteTache;
fields.cpAnonToggle.click();
assert(fields.cpTaskObject.value.indexOf('Marc Tremblay') === -1, '(d) Tâche : masquage #2 fonctionne');
fields.cpAnonUndo.click();
assert(fields.cpTaskObject.value === texteTache, '(d) Tâche : annulation #2 fonctionne');
fields.cpAnonToggle.click();
assert(fields.cpTaskObject.value.indexOf('Marc Tremblay') === -1, '(d) Tâche : REMASQUAGE (#3) fonctionne toujours');
fields.cpAnonUndo.click();
assert(fields.cpTaskObject.value === texteTache, '(d) Tâche : RÉANNULATION (#3) fonctionne toujours - aucune régression après plusieurs cycles');

// --- Cycle complet sur le Rôle (bouton dynamique RÉUTILISÉ, pas recréé) ---
const elementsAvantCycle = createdElements.length;
fields.cpPersonaCustom.value = textePersona2;
maskViaBanner(fields.cpPersonaCustom);
assert(
  createdElements.length === elementsAvantCycle,
  '(d) DRY confirmé : remasquer un champ déjà connu ne crée AUCUN nouvel élément (getOrCreateRecapController réutilise le contrôleur existant)'
);
assert(fields.cpPersonaCustom.value.indexOf('jean.dupont@exemple.com') === -1, '(d) Rôle : remasquage fonctionne');
personaUndo.click();
assert(fields.cpPersonaCustom.value === textePersona2, '(d) Rôle : réannulation fonctionne, avec le MÊME bouton (aucun doublon de gestionnaire de clic)');
maskViaBanner(fields.cpPersonaCustom);
assert(fields.cpPersonaCustom.value.indexOf('jean.dupont@exemple.com') === -1, '(d) Rôle : un 3e masquage fonctionne toujours');
personaUndo.click();
assert(fields.cpPersonaCustom.value === textePersona2, '(d) Rôle : une 3e annulation fonctionne toujours - aucune régression après plusieurs cycles');

console.log(pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
