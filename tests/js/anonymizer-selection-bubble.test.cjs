// tests/js/anonymizer-selection-bubble.test.cjs
// Garde-fou comportemental de la bulle de sélection de l'Anonymiseur.
// Exécuter : node tests/js/anonymizer-selection-bubble.test.cjs (ou npm run test:js)
//
// Ce test aurait attrapé le défaut du round 137 (2026-07-30) : la bulle
// « 🕵️ Anonymiser « ... » » n'était branchée que sur #anonAnnotated, le volet annoté, qui reste
// VIDE tant qu'aucune détection n'a tourné. Sélectionner un passage dans #anonSource (« Votre
// texte », là où l'utilisateur colle son contenu) ne produisait donc rien du tout, en silence,
// alors que la consigne affichée en haut du panneau promet dès le premier écran « Sélectionnez un
// passage, surlignez, anonymisez ».
//
// Il EXÉCUTE le vrai code plutôt que d'inspecter des sous-chaînes : un test de chaîne aurait
// continué de passer tant que le mot « anonSource » apparaissait quelque part dans le fichier,
// même si l'écouteur n'était jamais posé.

const fs = require('fs');
const path = require('path');

const rawSrc = fs.readFileSync(
  path.join(__dirname, '../../public/assets/tools/anonymiseur/anonymizer-ui.js'),
  'utf8'
);

// Le fichier se termine par un bloc d'auto-initialisation qui construit une vraie instance dès que
// #anonSource existe. On le retire ici : notre faux DOM ferait tourner tout le constructeur (et ses
// effets de bord) alors qu'on ne teste qu'une méthode. La classe, elle, est exportée à la volée.
const src = rawSrc.replace(/\n\/\/ Expose l'instance[\s\S]*$/, '\n');

// `module` est déjà une liaison CommonJS dans ce fichier : on injecte un objet distinct (_mod),
// comme le fait anonymizer-detect.test.cjs. `new Function(...)()` ne retourne rien par lui-même,
// c'est donc _mod.exports qu'il faut relire après l'appel.
const _mod = { exports: {} };

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

function makeEl(id) {
  const el = {
    id,
    listeners: {},
    _children: [],
    style: {},
    textContent: '',
    classList: {
      _set: new Set(),
      add(cls) { this._set.add(cls); },
      remove(cls) { this._set.delete(cls); },
      contains(cls) { return this._set.has(cls); }
    },
    addEventListener(type, fn) {
      if (!this.listeners[type]) this.listeners[type] = [];
      this.listeners[type].push(fn);
    },
    contains(node) { return node === el || el._children.includes(node); },
    // Largeur simulée de la bulle : le bornage horizontal du round 137 la mesure pour rester
    // dans la fenêtre. Un vrai élément masqué renverrait 0, d'où le démasquage avant mesure.
    _width: 260,
    getBoundingClientRect() { return { width: this._width, height: 36, top: 0, bottom: 36, left: 0 }; }
  };
  return el;
}

function fire(el, type, evt) {
  (el.listeners[type] || []).forEach(fn => fn(evt || {}));
}

const anonSelBubble = makeEl('anonSelBubble');
const anonSelBubbleBtn = makeEl('anonSelBubbleBtn');
const anonSelBubbleCustom = makeEl('anonSelBubbleCustom');
const anonAnnotated = makeEl('anonAnnotated');
const anonSource = makeEl('anonSource');
const anonEditorWrap = makeEl('anonEditorWrap');
const elements = { anonSelBubble, anonSelBubbleBtn, anonSelBubbleCustom, anonAnnotated, anonSource, anonEditorWrap };

anonSelBubble.classList.add('hidden');

// Sélection pilotable : c'est elle qui simule le geste de l'utilisateur.
let currentText = '';
let currentAnchor = null;
// Position de la sélection à l'écran, mutable pour tester le bornage horizontal de la bulle.
let selectionRect = { top: 100, bottom: 120, left: 50, width: 80 };

const fakeDocument = {
  getElementById: id => elements[id] || null,
  addEventListener() {}
};

const fakeWindow = {
  innerWidth: 390, // mobile : c'est la largeur où la bulle débordait à gauche (left: -64px)
  addEventListener() {},
  getSelection: () => ({
    toString: () => currentText,
    rangeCount: currentText ? 1 : 0,
    anchorNode: currentAnchor,
    getRangeAt: () => ({ getBoundingClientRect: () => selectionRect }),
    removeAllRanges() {}
  })
};

new Function('document', 'window', 'module', src + '\nmodule.exports = AnonymizerUI;')(
  fakeDocument, fakeWindow, _mod
);
const AnonymizerUI = _mod.exports;

// On n'appelle PAS le constructeur : on ne teste qu'une méthode, et le constructeur touche à
// beaucoup d'autres éléments du DOM réel.
const ui = Object.create(AnonymizerUI.prototype);
ui.lastSelection = '';

ui.bindSelectionBubble();

// --- Test 1 : LE cas du round 137 - sélection dans le volet source ---
currentText = 'LFC Construction';
currentAnchor = anonSource;
fire(anonSource, 'mouseup');
assert(!anonSelBubble.classList.contains('hidden'), "la bulle apparaît sur une sélection dans #anonSource (round 137)");
assert(ui.lastSelection === 'LFC Construction', "lastSelection retient le passage sélectionné dans #anonSource");
assert(anonSelBubbleBtn.textContent.includes('LFC Construction'), "le libellé du bouton nomme le passage");

// --- Test 2 : l'ancien comportement (volet annoté) n'a pas été perdu ---
anonSelBubble.classList.add('hidden');
currentText = 'Entreprise Dupont';
currentAnchor = anonAnnotated;
fire(anonAnnotated, 'mouseup');
assert(!anonSelBubble.classList.contains('hidden'), "la bulle apparaît toujours sur une sélection dans #anonAnnotated");
assert(ui.lastSelection === 'Entreprise Dupont', "lastSelection retient le passage sélectionné dans #anonAnnotated");

// --- Test 3 : sélection vidée -> la bulle disparaît ---
currentText = '';
currentAnchor = anonSource;
fire(anonSource, 'mouseup');
assert(anonSelBubble.classList.contains('hidden'), "la bulle se cache quand la sélection devient vide");

// --- Test 4 : sélection ancrée HORS des deux volets ---
// L'évènement part bien d'un volet écouté (sinon rien ne se déclencherait et le test passerait à
// vide), mais l'ancre de la sélection est ailleurs dans la page : le garde d'appartenance doit
// refuser ce texte.
const otherEl = makeEl('other');
anonSelBubble.classList.remove('hidden');
currentText = 'Texte étranger';
currentAnchor = otherEl;
fire(anonSource, 'mouseup');
assert(anonSelBubble.classList.contains('hidden'), "la bulle se cache si la sélection est ancrée hors des volets");
assert(ui.lastSelection === 'Entreprise Dupont', "lastSelection n'est pas écrasé par une sélection hors volets");

// --- Test 5 : libellé tronqué au-delà de 22 caractères ---
currentText = 'Ce texte est beaucoup trop long pour tenir';
currentAnchor = anonSource;
fire(anonSource, 'mouseup');
assert(anonSelBubbleBtn.textContent.includes('…'), "le libellé est tronqué avec des points de suspension");

// --- Test 6 : les DEUX volets sont réellement écoutés ---
assert((anonAnnotated.listeners.mouseup || []).length >= 1, "#anonAnnotated a un écouteur mouseup");
assert((anonSource.listeners.mouseup || []).length >= 1, "#anonSource a un écouteur mouseup");
assert((anonSource.listeners.keyup || []).length >= 1, "#anonSource a un écouteur keyup");
assert((anonSource.listeners.keydown || []).length >= 1, "#anonSource a un écouteur keydown (Cmd/Ctrl+A confiné)");

// --- Test 7 : bornage horizontal sur mobile (round 137, constaté en validation visuelle) ---
// Sélection collée au bord gauche : le centre de la bulle (left, avec translateX(-50%) en CSS)
// doit être repoussé à demi-largeur + 8, sinon son début sort de l'écran.
selectionRect = { top: 300, bottom: 320, left: 2, width: 10 };
currentText = 'LFC Construction';
currentAnchor = anonSource;
fire(anonSource, 'mouseup');
const leftPx = parseFloat(anonSelBubble.style.left);
assert(leftPx >= (260 / 2) + 8 - 0.01, "la bulle ne sort pas par la gauche (centre borné) : left=" + leftPx);

// Sélection collée au bord droit : borne symétrique.
selectionRect = { top: 300, bottom: 320, left: 380, width: 8 };
fire(anonSource, 'mouseup');
const rightPx = parseFloat(anonSelBubble.style.left);
assert(rightPx <= 390 - (260 / 2) - 8 + 0.01, "la bulle ne sort pas par la droite (centre borné) : left=" + rightPx);

// --- Test 8 : anonymizeValue ingère la source quand aucune détection n'a tourné ---
// C'est le second défaut trouvé en validation visuelle : la règle était créée et le toast
// annonçait « Passage anonymisé », mais sourceText restant vide, l'écran ne changeait pas.
const ui2 = Object.create(AnonymizerUI.prototype);
ui2.sourceText = '';
ui2.sourceHtml = '';
ui2.rules = [];
ui2.candidates = [];
ui2.anonMode = 'realistic';
let ingested = false;
ui2.richText = () => 'LFC Construction, une entreprise familiale.';
ui2.saveRules = () => {};
ui2.renderAnnotated = () => {};
ui2.updateOutput = () => { ingested = !!ui2.sourceText.trim(); };
// La classe a été évaluée avec `window` en paramètre : c'est CET objet qu'elle consulte, pas le
// global. On greffe donc le moteur ici.
fakeWindow.AnonymizerCore = {
  buildRules: () => [{ original: 'LFC Construction', replacement: 'Manon Gagné', category: 'org' }]
};
ui2.anonymizeValue('LFC Construction', 'org');
assert(ui2.sourceText.trim() !== '', "anonymizeValue ingère la source quand sourceText est vide");
assert(ingested, "updateOutput dispose d'un texte réel au moment du rendu (pas de faux succès)");

// Et l'inverse : si une détection a déjà rempli sourceText, on ne l'écrase PAS.
const ui3 = Object.create(AnonymizerUI.prototype);
ui3.sourceText = 'texte déjà ingéré par la détection';
ui3.sourceHtml = '<p>déjà</p>';
ui3.rules = []; ui3.candidates = []; ui3.anonMode = 'realistic';
ui3.richText = () => 'CE TEXTE NE DOIT PAS APPARAITRE';
ui3.saveRules = () => {}; ui3.renderAnnotated = () => {}; ui3.updateOutput = () => {};
ui3.anonymizeValue('LFC Construction', 'org');
assert(ui3.sourceText === 'texte déjà ingéré par la détection', "le chemin de détection n'est pas écrasé");

// --- Test 9 : round 143 - la bulle doit RENDRE VISIBLE le résultat, pas seulement l'appliquer ---
// Constaté au navigateur, pas par lecture de code : après un clic sur la bulle, le message
// « Passage anonymisé » s'affichait, la règle était bien créée dans #anonAnnotated ... mais
// #anonEditorWrap restait en `mode-edit`, donc le volet annoté restait en display:none. Rien ne
// changeait à l'écran. Seul « Détecter et anonymiser » faisait apparaître le résultat.
function stubUi(mode) {
  const u = Object.create(AnonymizerUI.prototype);
  u.lastSelection = '';
  u.modeCalls = [];
  u.anonymized = [];
  u.toasts = [];
  u.setMode = m => u.modeCalls.push(m);
  u.anonymizeValue = v => u.anonymized.push(v);
  u.guessCategory = () => 'org';
  u.toast = (msg, kind) => u.toasts.push(kind);
  anonEditorWrap.classList._set = new Set([mode]);
  return u;
}

const ui4 = stubUi('mode-edit');
const traite = ui4.anonymizeSelectedPassage('Jean Tremblay');
assert(traite === true, "anonymizeSelectedPassage confirme le traitement d'un passage réel");
assert(ui4.modeCalls.includes('annotate'), "round 143 : le mode bascule en annoté, sinon le résultat reste invisible");
assert(ui4.anonymized.includes('Jean Tremblay'), "le passage est bien anonymisé");
assert(ui4.lastSelection === '', "la sélection retenue est relâchée après traitement");

// Déjà en mode annoté : pas de bascule inutile (le volet est visible, rien à faire).
const ui5 = stubUi('mode-annotate');
ui5.anonymizeSelectedPassage('Jean Tremblay');
assert(!ui5.modeCalls.includes('annotate'), "aucune bascule superflue quand le volet annoté est déjà affiché");

// Passage vide : refus explicite, aucun faux message de succès.
const ui6 = stubUi('mode-edit');
assert(ui6.anonymizeSelectedPassage('   ') === false, "un passage vide est refusé");
assert(ui6.anonymized.length === 0, "aucune règle créée pour un passage vide");
assert(!ui6.toasts.includes('success'), "aucun message de succès pour un passage vide");

// --- Test 10 : le bouton DE LA BULLE passe réellement par ce point d'entrée ---
// C'est la moitié qui manquait : la méthode partagée existait, mais tant que la bulle ne
// l'appelait pas, le défaut restait entier. On exerce le vrai écouteur posé par
// bindSelectionBubble(), pas un appel direct.
ui.lastSelection = 'Jean Tremblay';
let viaPointEntree = null;
ui.anonymizeSelectedPassage = txt => { viaPointEntree = txt; return true; };
ui.toast = () => {};
fire(anonSelBubbleBtn, 'click');
assert(viaPointEntree === 'Jean Tremblay', "round 143 : le bouton de la bulle appelle anonymizeSelectedPassage()");

// Et sans sélection, la bulle avertit au lieu d'annoncer un succès.
ui.lastSelection = '';
ui.anonymizeSelectedPassage = () => false;
const avertissements = [];
ui.toast = (msg, kind) => avertissements.push(kind);
fire(anonSelBubbleBtn, 'click');
assert(avertissements.includes('warning'), "sans sélection, la bulle avertit au lieu de féliciter");
assert(!avertissements.includes('success'), "aucun « Passage anonymisé » quand rien n'a été traité");

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
