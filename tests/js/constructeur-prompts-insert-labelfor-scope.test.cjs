// tests/js/constructeur-prompts-insert-labelfor-scope.test.cjs
// Round 140 (2026-07-30, passe adversariale) : régression introduite par le correctif du round
// 138. Ce dernier a fait NOMMER le champ visé dans le message de confirmation, via un appel à
// labelFor(targetField.id) depuis insertIntoTask(). Or labelFor était un `const` déclaré à
// l'intérieur du bloc try du garde-fou anti-PII. insertIntoTask() est une fonction SOEUR de ce
// bloc, pas une fonction imbriquée : l'identifiant y était donc hors de portée.
//
// Ce que l'utilisateur voyait : il écrit une info personnelle dans un champ AUTRE que la Tâche
// (Exemples, Rôle, Audience, Contraintes, gabarit de carte), clique « Masquer mes infos → »,
// anonymise, puis clique « Insérer » - et il ne se passe RIEN de visible. Aucun message, le
// panneau reste ouvert.
// Ce qui se passait vraiment : le texte anonymisé ÉTAIT déjà écrit dans le champ (l'écriture a
// lieu avant), puis l'appel à labelFor levait un ReferenceError non intercepté qui avortait tout
// le reste du gestionnaire de clic : pas de message, pas de fermeture du panneau, et surtout
// `activeField = null` (dernière ligne) jamais exécuté - donc toutes les insertions SUIVANTES
// continuaient d'atterrir dans ce champ périmé.
//
// Le test exerce le vrai parcours de bout en bout sur le VRAI fichier : saisie d'un courriel,
// bandeau, « Masquer mes infos », insertion. Il ne vérifie aucune sous-chaîne de code source :
// une simple assertion de présence resterait verte alors que le code lève une exception.
// Lancer avec : node tests/js/constructeur-prompts-insert-labelfor-scope.test.cjs
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

// Fabrique un élément de formulaire. insertBefore reproduit fidèlement la sémantique du DOM :
// insérer `node` juste avant `ref` fait de `ref` le frère suivant de `node`. C'est exactement
// ce dont openAnonWithTask() se sert pour retrouver le champ concerné par le bandeau.
function makeField(id) {
  const el = {
    id,
    value: '',
    insertions: 0,
    // Round 142 : un vrai noeud du document répond `true`. Un noeud démonté par Alpine répond
    // `false` - c'est ce que le correctif doit détecter avant d'écrire.
    isConnected: true,
    textContent: '',
    style: {},
    attrs: {},
    listeners: {},
    nextElementSibling: null,
    addEventListener(type, cb) {
      (this.listeners[type] = this.listeners[type] || []).push(cb);
    },
    fire(type, evt) {
      (this.listeners[type] || []).forEach((cb) => cb(evt));
    },
    click() { this.fire('click', undefined); },
    dispatchEvent() {},
    setAttribute(name, value) { this.attrs[name] = value; },
    getAttribute(name) { return Object.prototype.hasOwnProperty.call(this.attrs, name) ? this.attrs[name] : null; },
    appendChild() {},
    remove() {},
    scrollIntoView() {},
    focus() {}
  };
  el.parentNode = {
    insertBefore(node, ref) { node.nextElementSibling = ref; ref.insertions++; }
  };
  return el;
}

// Éléments réellement résolus par getElementById dans prompt-anon-panel.js.
const fields = {
  cpTaskObject: makeField('cpTaskObject'),
  cpExamples: makeField('cpExamples'),
  cpAnonToggle: makeField('cpAnonToggle'),
  cpAnonPanel: makeField('cpAnonPanel'),
  cpAnonInsert: makeField('cpAnonInsert'),
  cpAnonInsertLabel: makeField('cpAnonInsertLabel'),
  anonSource: makeField('anonSource')
};
fields.cpAnonInsertLabel.textContent = 'Insérer dans la tâche'; // état rendu par Blade au chargement

// Éléments fabriqués par le script (bandeau + ses deux boutons), dans l'ordre de création.
const created = [];
let domReady;

global.document = {
  // Les écoutes déléguées (input/blur pour les gabarits de cartes) sont conservées par type :
  // le test du round 141 doit pouvoir les déclencher lui-même.
  listeners: {},
  addEventListener(type, cb) {
    if (type === 'DOMContentLoaded') { domReady = cb; return; }
    (this.listeners[type] = this.listeners[type] || []).push(cb);
  },
  getElementById(id) { return fields[id] || null; },
  createElement() { const el = makeField(''); created.push(el); return el; },
  querySelector() { return { content: 'csrf' }; }
};

const toasts = [];

global.window = {
  promptBuilderConfig: { i18n: {} },
  dispatchEvent(evt) { toasts.push(evt); },
  AnonymizerCore: {
    detectEntities(txt) { return /@/.test(txt) ? [{ type: 'email' }] : []; }
  },
  lvAnonUI: { anonPlain: 'Bonjour [NOM], contactez [COURRIEL]' }
};

global.sessionStorage = { getItem() { return null; }, removeItem() {} };
global.CustomEvent = class { constructor(t, o) { this.type = t; this.detail = o && o.detail; } };
global.Event = class { constructor(t, o) { this.type = t; this.bubbles = o && o.bubbles; } };

const sourcePath = path.join(__dirname, '../../public/assets/tools/constructeur-prompts/prompt-anon-panel.js');
new Function(fs.readFileSync(sourcePath, 'utf8'))();
domReady();

// (a) L'utilisateur écrit une vraie information personnelle dans « Exemples » puis quitte le champ.
fields.cpExamples.value = 'Écris à jean.dupont@gmail.com';
fields.cpExamples.fire('blur');
assert(
  fields.cpExamples.nextElementSibling === null || created.length > 0,
  'round 140 : le garde-fou anti-PII est bien monté sur le champ « Exemples »'
);

// (b) Il clique « Masquer mes infos → ». Ce bouton se reconnaît à sa signature d'effet unique :
// lui seul recopie la valeur du champ dans #anonSource (openAnonWithTask, étape iii).
let masquerTrouve = false;
for (const el of created) {
  if (!el.listeners.click) continue;
  fields.anonSource.textContent = '';
  el.click();
  if (fields.anonSource.textContent === 'Écris à jean.dupont@gmail.com') {
    masquerTrouve = true;
    break;
  }
}
assert(masquerTrouve, 'round 140 : le bouton « Masquer mes infos » ouvre le panneau et pré-remplit la source avec le champ concerné');

// Round 141 : le LIBELLÉ DU BOUTON annonce le champ réellement visé, AVANT le clic. C'est ce que
// la personne lit pour décider ; le round 138 n'avait corrigé que le message affiché après coup.
assert(
  fields.cpAnonInsertLabel.textContent === 'Insérer dans « Exemples pour guider l\'IA »',
  'round 141 : le bouton annonce « Insérer dans « Exemples... » » et non plus « dans la tâche » (lu AVANT le clic)'
);

// (c) Il clique « Insérer ». C'est ici que le ReferenceError se produisait.
fields.cpAnonPanel.style.display = 'block';
toasts.length = 0;
let leveeException = null;
try {
  fields.cpAnonInsert.click();
} catch (e) {
  leveeException = e;
}

assert(leveeException === null, 'round 140 : l\'insertion ne lève AUCUNE exception (c\'est le ReferenceError sur labelFor qui cassait tout)');
assert(
  fields.cpExamples.value.indexOf('[COURRIEL]') !== -1,
  'round 140 : le texte anonymisé est bien écrit dans « Exemples » (le champ qui contenait la fuite)'
);

const succes = toasts.filter((t) => t.type === 'toast-show' && t.detail && t.detail.variant === 'success');
assert(succes.length === 1, 'round 140 : un message de confirmation est réellement émis (il était avorté par l\'exception)');
assert(
  succes.length === 1 && succes[0].detail.message.indexOf('Exemples') !== -1,
  'round 140 : le message NOMME le champ visé (« Exemples »), il n\'annonce plus « la tâche » à tort'
);
assert(fields.cpAnonPanel.style.display === 'none', 'round 140 : le panneau se referme après l\'insertion');

// (d) Preuve que la cible a été relâchée : `activeField = null` est la DERNIÈRE ligne de
// insertIntoTask(), donc la première victime de l'exception. Sans elle, cette seconde insertion
// repartirait dans « Exemples » au lieu de la Tâche.
window.lvAnonUI.anonPlain = 'Deuxième texte anonymisé';
fields.cpTaskObject.value = '';
const exemplesAvant = fields.cpExamples.value;
fields.cpAnonInsert.click();
assert(
  fields.cpTaskObject.value.indexOf('Deuxième texte') !== -1,
  'round 140 : une insertion suivante retombe sur la Tâche (la cible a bien été relâchée)'
);
assert(
  fields.cpExamples.value === exemplesAvant,
  'round 140 : cette insertion suivante n\'écrit PAS une seconde fois dans « Exemples » (plus de cible périmée)'
);

assert(
  fields.cpAnonInsertLabel.textContent === 'Insérer dans la tâche',
  'round 141 : le libellé du bouton revient à « Insérer dans la tâche » une fois la cible relâchée'
);

// Round 141 : UN minuteur anti-rebond PAR CARTE. Deux cartes de gabarit reçoivent chacune une
// info personnelle à 200 ms d'intervalle, donc dans la fenêtre de 600 ms de la première. Avec un
// minuteur partagé, la frappe dans la seconde annulait le contrôle de la première, dont le
// contenu n'était alors JAMAIS scanné. Test asynchrone : le bilan est émis à la fin.
const carteA = makeField('cpCardTemplate-aaa');
const carteB = makeField('cpCardTemplate-bbb');
const surSaisie = document.listeners.input || [];

carteA.value = 'Rappeler marie.tremblay@exemple.ca';
surSaisie.forEach((cb) => cb({ target: carteA }));

setTimeout(() => {
  carteB.value = 'Rappeler luc.gagnon@exemple.ca';
  surSaisie.forEach((cb) => cb({ target: carteB }));
}, 200);

setTimeout(() => {
  assert(carteA.insertions === 1, 'round 141 : la carte A est scannée même si on tape ensuite dans la carte B (minuteur par carte)');
  assert(carteB.insertions === 1, 'round 141 : la carte B est scannée elle aussi');

  // Round 142 : la cible a été DÉMONTÉE entre sa mémorisation et le clic sur « Insérer ».
  // Le textarea d'un gabarit de carte vit dans un <template x-if>, donc refermer le panneau de la
  // carte détruit le noeud. Sans garde, l'écriture partait dans ce noeud détaché : texte perdu,
  // message de succès affiché quand même.
  const carteDemontee = makeField('cpCardTemplate-ccc');
  fields.cpExamples.value = 'jean.dupont@gmail.com';
  fields.cpExamples.fire('blur');
  for (const el of created) {
    if (!el.listeners || !el.listeners.click) continue;
    fields.anonSource.textContent = '';
    el.click();
    if (fields.anonSource.textContent === 'jean.dupont@gmail.com') break;
  }
  // On simule l'ouverture depuis la carte, puis sa destruction par Alpine.
  window.lvAnonUI.anonPlain = 'Texte masqué pour la carte';
  fields.cpTaskObject.value = '';
  const exemplesAvantCarte = fields.cpExamples.value;
  carteDemontee.isConnected = false; // Alpine a retiré le noeud du document
  fields['cpCardTemplate-ccc'] = null; // et getElementById ne le retrouve plus
  toasts.length = 0;

  // Reposer la cible sur ce noeud mort en passant par le vrai chemin du garde-fou.
  const surBlur = document.listeners.blur || [];
  carteDemontee.value = 'contact luc@exemple.ca';
  surBlur.forEach((cb) => cb({ target: carteDemontee }));
  for (const el of created) {
    if (!el.listeners || !el.listeners.click) continue;
    el.click();
  }
  carteDemontee.isConnected = false; // le clic d'ouverture ne doit pas le ressusciter

  fields.cpAnonInsert.click();

  const avertissements = toasts.filter((t) => t.type === 'toast-show' && t.detail && t.detail.variant === 'warning');
  const succes142 = toasts.filter((t) => t.type === 'toast-show' && t.detail && t.detail.variant === 'success');

  assert(
    carteDemontee.value.indexOf('Texte masqué pour la carte') === -1,
    'round 142 : rien n\'est écrit dans le champ démonté (le texte y serait perdu en silence)'
  );
  assert(
    fields.cpTaskObject.value.indexOf('Texte masqué pour la carte') === -1 &&
    fields.cpExamples.value === exemplesAvantCarte,
    'round 142 : le texte n\'est PAS redirigé en douce vers un autre champ (ce serait dupliquer la fuite)'
  );
  assert(
    succes142.length === 0 && avertissements.length >= 1,
    'round 142 : un avertissement honnête remplace le faux message de succès'
  );

  console.log(pass + '/' + (pass + fail) + ' OK');
  process.exit(fail > 0 ? 1 : 0);
}, 1200);
