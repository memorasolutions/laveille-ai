// tests/js/constructeur-prompts-cardtemplate-pii-guard.test.cjs
// Round 119 (2026-07-27, passe adversariale) : le textarea « Gabarit de requête » des cartes
// personnalisées n'avait AUCUN id, donc le garde-fou anti-PII de prompt-anon-panel.js - qui
// résout ses champs par getElementById - ne pouvait structurellement pas le surveiller. Or son
// contenu est persisté en base par persistCustomCards() dès le blur, puis réinjecté
// automatiquement dans de futurs prompts : c'est une source de PII RÉUTILISÉE, jamais scannée.
// Exactement la classe de manque corrigée au round 112 pour le panneau « Mon profil ».
// Ces textareas étant montés/démontés dynamiquement par Alpine (x-if dans x-for), une liste
// statique ne peut pas les couvrir : le correctif passe par une écoute DÉLÉGUÉE sur document.
// Lancer avec : node tests/js/constructeur-prompts-cardtemplate-pii-guard.test.cjs
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
  const parentNode = {
    inserted: [],
    insertBefore(node, ref) { this.inserted.push({ node, ref }); }
  };
  return {
    id,
    value: '',
    addEventListener() {},
    dispatchEvent() {},
    parentNode,
    scrollIntoView() {},
    focus() {}
  };
}

function makeElementStub() {
  return {
    style: {},
    setAttribute() {},
    appendChild() {},
    addEventListener() {},
    remove() {},
    textContent: '',
    nextElementSibling: null,
    children: []
  };
}

// Les 5 champs fixes déjà surveillés depuis le round 109 (les autres ids renvoient null).
const fields = {
  cpTaskObject: makeField('cpTaskObject'),
  cpPersonaCustom: makeField('cpPersonaCustom'),
  cpAudienceCustom: makeField('cpAudienceCustom'),
  cpVerbCustom: makeField('cpVerbCustom'),
  cpConstraintCustom: makeField('cpConstraintCustom')
};

let domReady;
const delegated = { input: [], blur: [] };

global.document = {
  addEventListener(type, cb, capture) {
    if (type === 'DOMContentLoaded') {
      domReady = cb;
    } else if (type === 'input') {
      delegated.input.push(cb);
    } else if (type === 'blur') {
      delegated.blur.push({ cb, capture });
    }
  },
  getElementById(id) { return fields[id] || null; },
  createElement() { return makeElementStub(); },
  querySelector() { return { content: 'csrf' }; }
};

global.window = {
  promptBuilderConfig: { i18n: {} },
  dispatchEvent() {},
  AnonymizerCore: {
    detectEntities(txt) { return /@/.test(txt) ? [{ type: 'email' }] : []; }
  }
};

global.sessionStorage = {
  getItem() { return null; },
  removeItem() {}
};

global.CustomEvent = class {
  constructor(t, o) { this.type = t; this.detail = o && o.detail; }
};

global.Event = class {
  constructor(t, o) { this.type = t; this.bubbles = o && o.bubbles; }
};

const sourcePath = path.join(__dirname, '../../public/assets/tools/constructeur-prompts/prompt-anon-panel.js');
new Function(fs.readFileSync(sourcePath, 'utf8'))();
domReady();

assert(delegated.blur.length >= 1, 'round 119 : un écouteur blur délégué est bien posé sur document');
assert(delegated.blur[0] && delegated.blur[0].capture === true, 'round 119 : l\'écoute blur déléguée est en phase de CAPTURE (blur ne bulle pas)');
assert(delegated.input.length >= 1, 'round 119 : un écouteur input délégué est bien posé sur document');

// Gabarit de carte contenant une vraie information personnelle.
const card = makeField('cpCardTemplate-abc123');
card.value = 'Relance jean.dupont@gmail.com au sujet du dossier';
for (const entry of delegated.blur) { entry.cb({ target: card }); }
assert(card.parentNode.inserted.length === 1, 'round 119 : le gabarit de carte contenant un courriel déclenche RÉELLEMENT le bandeau (insertBefore appelé)');

// Gabarit propre : aucun avertissement (pas de faux positif).
const clean = makeField('cpCardTemplate-def456');
clean.value = 'Corrige les fautes de ce texte';
for (const entry of delegated.blur) { entry.cb({ target: clean }); }
assert(clean.parentNode.inserted.length === 0, 'round 119 : un gabarit sans info personnelle ne déclenche aucun bandeau');

// Anti sur-portée : un id hors convention ne doit jamais être capté par la délégation.
const other = makeField('cpAutreChamp');
other.value = 'test@example.com';
for (const entry of delegated.blur) { entry.cb({ target: other }); }
assert(other.parentNode.inserted.length === 0, 'round 119 : l\'écoute déléguée ne s\'applique QU\'aux ids cpCardTemplate- (pas de sur-portée)');

console.log(pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
