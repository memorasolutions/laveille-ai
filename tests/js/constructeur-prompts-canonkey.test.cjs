// tests/js/constructeur-prompts-canonkey.test.cjs
// Garde-fou de non-régression - couche 2 « espaces à remplir » (tâches 1660-1665, boucle 5 oracles
// 2026-08-09, spec TR15 unicode.org). Couvre le _canonKey()/_canonSearchText() de
// constructeur-prompts-core.js :
//   1. _canonSearchText() : remplacement 1:1 en longueur (apostrophe courbe/modificative,
//      espace insécable/insécable étroit) - RIEN d'autre (accents/casse préservés).
//   2. _canonKey() : égalité de dictionnaire avec NFC (NFD vs NFC).
//   3. Le texte tapé par la personne n'est JAMAIS modifié par la normalisation (RÈGLE D'OR) -
//      dédoublonnage/recherche/renommage/fusion à travers des formes Unicode différentes du MÊME
//      texte, mais le prompt copié reste BYTE POUR BYTE identique à ce qui a été tapé.
//   4. Migration ADDITIVE des collisions dans cpSpaceLastValues_v1 (localStorage).
// Exécute : node tests/js/constructeur-prompts-canonkey.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

const CURLY_APOSTROPHE = '’'; // '
const MODIFIER_APOSTROPHE = 'ʼ'; // ʼ
const NBSP = ' ';
const NNBSP = ' ';

function loadPromptBuilder(sharedStore) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;
    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    let lastToast = null;
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: false, i18n: {} },
        dispatchEvent: () => {},
        toast: (msg, variant) => { lastToast = { msg, variant }; },
        matchMedia: () => ({ matches: false }),
    };
    global.navigator = global.navigator || {};
    let lastCopied = null;
    global.navigator.clipboard = { writeText: (text) => { lastCopied = text; return Promise.resolve(); } };
    global.window.copyToClipboard = function (text) { lastCopied = text; return navigator.clipboard.writeText(text).then(() => true); };
    global.window.open = function () {};
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.URLSearchParams = require('url').URLSearchParams;
    const store = sharedStore || {};
    global.localStorage = {
        _store: store,
        getItem(k) { return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { this._store[k] = String(v); },
        removeItem(k) { delete this._store[k]; },
    };
    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    let lastDispatch = null;
    component.$dispatch = function (name, detail) { lastDispatch = { name, detail }; };
    return { component, getLastCopied: () => lastCopied, getLastToast: () => lastToast, getLastDispatch: () => lastDispatch, store };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

function fillBaseFields(component) {
    component.personaPreset = 'redacteur_web';
    component.personas = [{ value: 'redacteur_web', label: 'Rédacteur web' }];
    component.verbType = 'preset';
    component.verb = 'Rédige';
}

// 1. _canonSearchText() : remplacement 1:1 en LONGUEUR, apostrophes courbe/modificative et espaces
//    insécables/insécables étroits uniquement - rien d'autre (accents et casse préservés).
{
    const { component } = loadPromptBuilder();
    const raw = "l" + CURLY_APOSTROPHE + "école" + NBSP + "de" + NNBSP + "Québec";
    const canon = component._canonSearchText(raw);
    assert(canon === "l'école de Québec", "apostrophe courbe -> droite, insécable/insécable étroit -> espace simple");
    assert(canon.length === raw.length, "remplacement 1:1 en LONGUEUR (indices valides sur le texte brut)");
    assert(component._canonSearchText("MAJUSCULE") === "MAJUSCULE", "la casse n'est jamais touchée");
    assert(component._canonSearchText("éàè") === "éàè", "les accents précomposés ne sont jamais touchés");
    assert(component._canonSearchText(MODIFIER_APOSTROPHE + "test") === "'test", "apostrophe modificative (U+02BC) normalisée aussi");
}

// 2. _canonKey() : égalité de dictionnaire, NFC appliqué (NFD vs NFC), apostrophes/insécables
//    normalisés en plus.
{
    const { component } = loadPromptBuilder();
    const nfc = "café"; // é précomposé (NFC)
    const nfd = "café"; // e + accent combinant (NFD)
    assert(nfc !== nfd, 'préalable : les 2 formes sont bien des chaînes JS différentes (test valide)');
    assert(component._canonKey(nfc) === component._canonKey(nfd), '_canonKey() : NFD et NFC du même mot donnent la même clé (normalize NFC)');
    assert(component._canonKey("l" + CURLY_APOSTROPHE + "école") === component._canonKey("l'école"), "_canonKey() : apostrophe courbe et droite -> même clé");
    assert(component._canonKey("mot" + NBSP + "composé") === component._canonKey("mot composé"), "_canonKey() : espace insécable et espace simple -> même clé");
}

// 3. Dédoublonnage à la création (createSpaceFromSelection/_findSpaceByText) à travers des formes
//    Unicode différentes du MÊME texte - NFD vs NFC ET apostrophe courbe vs droite.
{
    const { component, getLastToast } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = "Parle-moi de café et de restaurants.";
    component.spaceBubble = { show: true, text: 'café', fieldId: 'cpTaskObject' }; // NFC
    component.createSpaceFromSelection();
    assert(component.spaces.length === 1, 'un espace "café" (NFC) créé');

    // Re-sélection avec la forme NFD (décomposée) du même mot : dédoublonnage attendu.
    component.spaceBubble = { show: true, text: 'café', fieldId: 'cpTaskObject' }; // NFD
    component.createSpaceFromSelection();
    assert(component.spaces.length === 1, 'dédoublonnage NFD vs NFC : aucune 2e pastille créée');
    assert(getLastToast() && getLastToast().variant === 'info', 'toast "existe déjà" affiché pour la variante NFD');
}

// 4. _hasBoundedOccurrence()/_refreshSpaceMissing() : une occurrence collée avec une apostrophe
//    courbe ou un espace insécable dans le texte de la personne est retrouvée, même si l'espace a
//    été créé avec l'apostrophe droite / l'espace simple - la RÈGLE D'OR (positions calculées sur
//    le texte BRUT) ne casse jamais le statut "non retrouvé".
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = "Parle-moi d" + CURLY_APOSTROPHE + "aujourd" + CURLY_APOSTROPHE + "hui.";
    component.spaceBubble = { show: true, text: "aujourd'hui", fieldId: 'cpTaskObject' }; // apostrophe droite
    component.createSpaceFromSelection();
    assert(component.spaces.length === 1, 'espace "aujourd\'hui" (apostrophe droite) créé');
    assert(component.spaceMissing(component.spaces[0]) === false, "retrouvé dans le texte malgré l'apostrophe courbe collée par la personne");

    // Idem avec un espace insécable à l'intérieur du texte de l'espace créé.
    const { component: c2 } = loadPromptBuilder();
    fillBaseFields(c2);
    c2.taskObject = "Parle-moi de la" + NBSP + "France en détail.";
    c2.spaceBubble = { show: true, text: 'la France', fieldId: 'cpTaskObject' }; // espace simple
    c2.createSpaceFromSelection();
    assert(c2.spaces.length === 1, 'espace "la France" (espace simple) créé');
    assert(c2.spaceMissing(c2.spaces[0]) === false, "retrouvé malgré l'espace insécable collé par la personne (ex: correcteur orthographique)");
}

// 5. RÈGLE D'OR (byte pour byte) : le prompt COPIÉ reproduit EXACTEMENT ce que la personne a tapé
//    (l'apostrophe courbe reste courbe dans le prompt technique), seule la VALEUR DE REMPLISSAGE
//    (promptFilled) substitue le segment - jamais la forme canonique du dictionnaire.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    const rawText = "Parle-moi d" + CURLY_APOSTROPHE + "aujourd" + CURLY_APOSTROPHE + "hui.";
    component.taskObject = rawText;
    component.spaceBubble = { show: true, text: "aujourd'hui", fieldId: 'cpTaskObject' }; // apostrophe droite au dictionnaire
    component.createSpaceFromSelection();
    assert(component.taskObject === rawText, "LE TEXTE DU CHAMP NE CHANGE PAS à la création (apostrophe courbe intacte)");
    assert(component.prompt.indexOf(CURLY_APOSTROPHE + "aujourd" + CURLY_APOSTROPHE) !== -1, "get prompt() reproduit BYTE POUR BYTE l'apostrophe courbe tapée par la personne");
    assert(component.prompt.indexOf("aujourd'hui") === -1, "get prompt() ne contient JAMAIS la forme canonique du dictionnaire (apostrophe droite) à la place du texte tapé");

    // Remplissage : la valeur remplace bien le segment repéré via l'apostrophe courbe.
    component.spaceValues["aujourd'hui"] = 'demain';
    assert(component.promptFilled.indexOf('demain') !== -1, "promptFilled substitue bien la valeur malgré la forme Unicode différente dans le texte");
    assert(component.promptFilled.indexOf(CURLY_APOSTROPHE + "aujourd") === -1, "plus aucune trace du texte d'origine (apostrophe courbe) une fois rempli");
}

// 6. Fusion au renommage à travers 2 formes Unicode du même texte : la valeur déjà saisie sous la
//    forme littérale tapée dans le champ de renommage doit être retrouvable par la pastille
//    survivante, même si sa propre forme .text est Unicode-différente (clé canonique commune).
(function () {
    const { component, getLastDispatch } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = "Compare le chat et le chien" + CURLY_APOSTROPHE + "s.";
    component.spaceBubble = { show: true, text: 'chat', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    component.spaceBubble = { show: true, text: "chien" + CURLY_APOSTROPHE + "s", fieldId: 'cpTaskObject' }; // espace B, apostrophe courbe dans son propre texte
    component.createSpaceFromSelection();
    // setSpaceValue() est le chemin RÉEL emprunté par le champ de remplissage (:value/@input dans
    // le Blade) - un accès brut spaceValues[sp.text] contournerait la canonicalisation et fausserait
    // ce test.
    component.setSpaceValue(component.spaces[1], 'les loups');

    component.spaceEditingIndex = 0;
    component.spaceEditingText = "chien's"; // apostrophe DROITE tapée dans le champ de renommage (forme différente de space B)
    component.commitRenameSpace(0);
    const dispatch = getLastDispatch();
    assert(!!dispatch, 'une confirmation de fusion est demandée (les 2 formes sont canoniquement égales)');
    dispatch.detail.callback();

    assert(component.spaces.length === 1, "fusion : un seul espace survit malgré les 2 formes Unicode d'apostrophe");
    assert(component.spaceValueFor(component.spaces[0]) === 'les loups', "la valeur déjà saisie sous l'espace survivant (space B) reste accessible via spaceValueFor() après fusion, malgré la clé littérale différente utilisée pour renommer");
}());

// 7. Migration collision cpSpaceLastValues_v1 (localStorage) : 2 clés RAW canoniquement égales
//    (apostrophe courbe une fois, droite une autre) doivent fusionner en UNE seule entrée
//    canonique à la lecture, sans jamais perdre de valeur (jamais une clé pleine écrasée par une
//    vide) - la variante encore présente littéralement dans le texte courant l'emporte.
(function () {
    const curlyKey = "l" + CURLY_APOSTROPHE + "école";
    const straightKey = "l'école";
    const migratedStore = {
        cpSpaceLastValues_v1: JSON.stringify({
            [straightKey]: ['ancienne valeur'],
            [curlyKey]: ['valeur récente'],
        }),
    };
    const { component } = loadPromptBuilder(migratedStore);
    fillBaseFields(component);
    // Le texte courant contient la forme à apostrophe DROITE - elle doit l'emporter même si elle a
    // été itérée en premier dans l'objet JSON d'origine.
    component.taskObject = "Parle-moi de " + straightKey + ".";
    component._loadSpaceLastValues();
    const canonKey = component._canonKey(straightKey);
    const keys = Object.keys(component.spaceLastValues);
    assert(keys.length === 1, 'migration ADDITIVE : les 2 clés RAW canoniquement égales fusionnent en UNE seule entrée');
    assert(Array.isArray(component.spaceLastValues[canonKey]), "l'entrée fusionnée est bien accessible sous la clé canonique");
    assert(component.spaceLastValues[canonKey][0] === 'ancienne valeur', "collision : la variante dont la forme LITTÉRALE existe encore dans le texte courant (apostrophe droite) l'emporte");

    // Persisté sous forme canonique (une seule clé) pour la prochaine session.
    const persisted = JSON.parse(global.localStorage.getItem('cpSpaceLastValues_v1'));
    assert(Object.keys(persisted).length === 1, 'la ré-écriture localStorage ne conserve qu\'une seule clé (migration persistée)');
}());

// 7b. Collision sans aucune des 2 formes présente dans le texte courant : la plus récente
//     (dernière rencontrée dans l'objet d'origine) l'emporte - jamais une perte silencieuse.
(function () {
    const curlyKey = "l" + CURLY_APOSTROPHE + "école";
    const straightKey = "l'école";
    const migratedStore = {
        cpSpaceLastValues_v1: JSON.stringify({
            [straightKey]: ['plus ancienne'],
            [curlyKey]: ['plus récente'],
        }),
    };
    const { component } = loadPromptBuilder(migratedStore);
    fillBaseFields(component);
    component.taskObject = 'Un texte sans rapport.';
    component._loadSpaceLastValues();
    const canonKey = component._canonKey(straightKey);
    assert(Object.keys(component.spaceLastValues).length === 1, 'fusion en 1 seule entrée même sans littéral présent');
    assert(component.spaceLastValues[canonKey][0] === 'plus récente', "aucune des 2 formes présente : la plus récente (dernière itérée) l'emporte, jamais une perte de donnée silencieuse");
}());

// 8. Une clé pleine n'est JAMAIS écrasée par une clé vide, même en collision.
(function () {
    const curlyKey = "l" + CURLY_APOSTROPHE + "école";
    const straightKey = "l'école";
    const migratedStore = {
        cpSpaceLastValues_v1: JSON.stringify({
            [straightKey]: ['valeur réelle'],
            [curlyKey]: [], // entrée vide (ex: données corrompues/anciennes)
        }),
    };
    const { component } = loadPromptBuilder(migratedStore);
    fillBaseFields(component);
    component.taskObject = 'Texte neutre.';
    component._loadSpaceLastValues();
    const canonKey = component._canonKey(straightKey);
    assert(component.spaceLastValues[canonKey].length === 1 && component.spaceLastValues[canonKey][0] === 'valeur réelle', 'la clé PLEINE (avec une valeur réelle) survit face à la clé VIDE, peu importe l\'ordre d\'itération');
}());

setTimeout(function () {
    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
}, 50);
