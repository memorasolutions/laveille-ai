// tests/js/constructeur-prompts-espaces.test.cjs
// Garde-fou de non-régression (tâches 1660-1665, « Espaces à remplir », design panel multi-IA
// 5 rounds approuvé par le fondateur, 2026-08-07). Couvre le §Tests de la spec :
//   1. création par sélection (état)
//   2. refus mot-outil / < 3 caractères
//   3. insertion pending + suffixe 2
//   4. renommage remplace les occurrences
//   5. missing au blur
//   6. remplacement global dans promptFilled (2 occurrences, comportement « mail merge »)
//   7. vide → repli mot d'origine
//   8. coexistence {{}} + espaces
//   9. sauvegarde params.spaces + restauration
//   10. mémoire lastValues (cpSpaceLastValues_v1)
// Exécute : node tests/js/constructeur-prompts-espaces.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

// `sharedStore` : réutilise le MÊME objet de stockage localStorage entre 2 appels (simule 2
// sessions successives dans le même navigateur, ex. test 10 « mémoire lastValues ») - par défaut
// chaque appel repart d'un stockage vierge (simule un navigateur/contexte différent).
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
    return { component, getLastCopied: () => lastCopied, getLastToast: () => lastToast, store };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

function fillBaseFields(component) {
    component.personaPreset = 'redacteur_web';
    component.personas = [{ value: 'redacteur_web', label: 'Rédacteur web' }];
    component.verbType = 'preset';
    component.verb = 'Rédige';
}

// 1. Création par sélection (état) : createSpaceFromSelection() ajoute { text, pending:false }.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Rédige un texte sur les fractions pour mes élèves.';
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    assert(component.spaces.length === 1, 'un espace créé');
    assert(component.spaces[0].text === 'fractions' && component.spaces[0].pending === false, 'espace créé { text: "fractions", pending: false }');
    assert(component.spaceBubble.show === false, 'la bulle se referme après création');
    assert(component.taskObject === 'Rédige un texte sur les fractions pour mes élèves.', 'LE TEXTE DU CHAMP NE CHANGE PAS à la création par sélection');

    // Dédoublonnage : re-sélectionner le même texte ne crée pas de 2e entrée.
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    assert(component.spaces.length === 1, 'dédoublonnage : le même texte ne crée pas une 2e pastille');
}

// 2. Refus mot-outil / < 3 caractères : action refusée, toast doux, aucun espace créé.
{
    const { component, getLastToast } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Rédige un texte sur de bons sujets.';
    component.spaceBubble = { show: true, text: 'de', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    assert(component.spaces.length === 0, 'mot-outil "de" refusé, aucun espace créé');
    assert(getLastToast() && getLastToast().variant === 'warning', 'toast doux affiché pour le refus mot-outil');

    component.spaceBubble = { show: true, text: 'ab', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    assert(component.spaces.length === 0, 'sélection de 2 caractères refusée (< 3 caractères)');

    component.spaceBubble = { show: true, text: 'abc', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    assert(component.spaces.length === 1, 'sélection de 3 caractères (non mot-outil) acceptée');
}

// 3. Insertion pending + suffixe 2 : addSpaceAtCursor() crée un espace pending, la 2e insertion
//    se suffixe automatiquement.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Rédige un texte.';
    component.addSpaceAtCursor();
    assert(component.spaces.length === 1, 'un espace pending créé par le bouton +');
    assert(component.spaces[0].pending === true, 'le nouvel espace est pending');
    assert(component.spaces[0].text === 'information à préciser', 'texte par défaut inséré');
    assert(component.taskObject.indexOf('information à préciser') !== -1, 'le texte du champ contient bien le fragment inséré (repli sans DOM)');

    component.addSpaceAtCursor();
    assert(component.spaces.length === 2, 'un 2e espace pending coexiste avec le 1er');
    assert(component.spaces[1].text === 'information à préciser 2', 'la 2e insertion est suffixée "2"');
}

// 4. Renommage remplace les occurrences (dans les 2 textareas, mail merge).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Rédige un texte sur fractions. Reviens ensuite sur fractions en détail.';
    component.contextInfo = "On a déjà essayé d'expliquer fractions autrement.";
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    component.startRenameSpace(0);
    assert(component.spaceEditingIndex === 0 && component.spaceEditingText === 'fractions', 'renommage inline ouvert avec le texte courant pré-rempli');
    component.spaceEditingText = 'les nombres décimaux';
    component.commitRenameSpace(0);
    assert(component.spaces[0].text === 'les nombres décimaux', 'le nom de l\'espace est mis à jour');
    assert(component.taskObject.indexOf('fractions') === -1, 'ancien texte disparu de taskObject');
    assert((component.taskObject.match(/les nombres décimaux/g) || []).length === 2, 'TOUTES les occurrences de taskObject remplacées (mail merge)');
    assert(component.contextInfo.indexOf('les nombres décimaux') !== -1 && component.contextInfo.indexOf('fractions') === -1, 'occurrence dans contextInfo aussi remplacée');
    assert(component.spaceEditingIndex === null, 'le mode édition se referme après validation');
}

// 4b. Échap/blur vide = annule (aucun changement).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Un texte sur fractions.';
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    component.startRenameSpace(0);
    component.spaceEditingText = '';
    component.commitRenameSpace(0);
    assert(component.spaces[0].text === 'fractions', 'un renommage vide annule (le nom original est conservé)');
    component.cancelRenameSpace();
    assert(component.spaceEditingIndex === null, 'cancelRenameSpace() referme le mode édition');
}

// 5. Missing au blur : un espace confirmé dont le texte disparaît du champ devient "missing"
//    UNIQUEMENT après handleSpaceFieldBlur() (jamais entre-temps, pas de recalcul à la frappe).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Un texte sur fractions.';
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    assert(component.spaceMissing(component.spaces[0]) === false, 'espace retrouvé juste après création');

    component.taskObject = 'Un texte sur les nombres.'; // "fractions" disparaît du texte
    assert(component.spaceMissing(component.spaces[0]) === false, 'PAS de recalcul avant le blur (évite le clignotement pendant la frappe)');

    component.handleSpaceFieldBlur();
    assert(component.spaceMissing(component.spaces[0]) === true, 'devient "non retrouvé" après le blur');
    assert(component.spaces.length === 1, 'un espace CONFIRMÉ manquant n\'est jamais retiré automatiquement (retrait = geste explicite du ×)');
    assert(component.fillableSpaces.length === 0, 'un espace "non retrouvé" est exclu du bloc de remplissage');
}

// 5b. Un espace encore PENDING dont le texte disparaît (sans avoir été renommé) est retiré
//     silencieusement au blur - règle simple retenue par le panel (spec §UI - création, point B).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Un texte.';
    component.addSpaceAtCursor();
    assert(component.spaces.length === 1 && component.spaces[0].pending === true, 'espace pending créé');

    // L'utilisateur tape directement par-dessus le fragment pré-sélectionné (repli sans DOM ici :
    // on simule le résultat en retirant le placeholder du texte, comme le ferait x-model réel).
    component.taskObject = component.taskObject.replace('information à préciser', 'les fractions');
    component.handleSpaceFieldBlur();
    assert(component.spaces.length === 0, 'espace pending non renommé et introuvable = retiré silencieusement au blur');
}

// 6. Remplacement global dans promptFilled (2 occurrences dans la tâche, comportement mail merge)
//    - jamais 2 valeurs différentes pour la même chaîne (hors périmètre documenté). Le prompt
//    généré répète aussi la tâche dans la phrase de clôture « Produis maintenant : ... » (fonction
//    préexistante, non liée aux espaces) - on vérifie donc que le NOMBRE d'occurrences se conserve
//    à l'identique entre le mot d'origine (prompt brut) et sa valeur de remplacement (promptFilled),
//    plutôt qu'un compte fixe qui dépendrait de cette autre fonctionnalité.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Rédige un courriel sur fractions puis un résumé sur fractions.';
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    const originalCount = (component.prompt.match(/fractions/g) || []).length;
    assert(originalCount >= 2, 'le mot "fractions" apparaît bien au moins 2 fois dans le prompt brut (2 occurrences dans la tâche)');
    component.spaceValues['fractions'] = 'les décimales';
    const filled = component.promptFilled;
    assert((filled.match(/les décimales/g) || []).length === originalCount, 'la valeur remplace TOUTES les occurrences exactes dans promptFilled, aucune de moins');
    assert(filled.indexOf('fractions') === -1, 'plus aucune trace du mot original une fois rempli');
    assert(component.prompt.indexOf('fractions') !== -1, 'le prompt technique (get prompt()) reste identique au texte d\'origine - INCHANGÉ');
}

// 7. Vide → repli mot d'origine (jamais bloquant, prompt reste grammatical).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Rédige un texte sur fractions.';
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    // spaceValues['fractions'] jamais renseigné : promptFilled doit garder le mot de départ.
    assert(component.promptFilled.indexOf('fractions') !== -1, 'espace laissé vide : le mot d\'origine part dans le prompt copié');
    assert(component.unfilledSpacesCount === 1, 'unfilledSpacesCount compte bien cet espace non rempli');
    assert(component.unfilledSpacesMessage.indexOf('1') !== -1, 'le message mentionne le compte (singulier)');
}

// 8. Coexistence {{...}} et espaces : les 2 mécanismes cohabitent, zéro interférence.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Rédige un texte sur fractions pour {{public}}.';
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    assert(component.promptVariables.length === 1 && component.promptVariables[0] === 'public', 'la variable {{public}} est toujours détectée à côté d\'un espace');
    component.spaceValues['fractions'] = 'les statistiques';
    component.varValues['public'] = 'mes élèves de 5e année';
    const filled = component.promptFilled;
    assert(filled.indexOf('les statistiques') !== -1, 'la valeur de l\'espace est substituée');
    assert(filled.indexOf('mes élèves de 5e année') !== -1, 'la valeur de la variable {{}} est AUSSI substituée (coexistence)');
    assert(filled.indexOf('{{public}}') === -1 && filled.indexOf('fractions') === -1, 'plus aucune trace des 2 gabarits une fois remplis');
}

// 9. Sauvegarde params.spaces + restauration (wizardParams, loadGuestHistoryEntry, ?edit=/?remix=
//    déjà couverts par la même liste de champs - voir constructeur-prompts-editroundtrip.test.cjs
//    pour le pattern général, ici on vérifie le contrat spécifique aux espaces).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Rédige un texte sur fractions.';
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    component.spaceValues['fractions'] = 'ne doit jamais être sérialisé';
    const params = component.wizardParams;
    assert(Array.isArray(params.spaces) && params.spaces.length === 1, 'wizardParams.spaces contient bien l\'espace');
    assert(params.spaces[0].text === 'fractions', 'wizardParams.spaces[0].text = la chaîne ancrée');
    assert(Object.keys(params.spaces[0]).length === 1, 'wizardParams.spaces ne sérialise QUE {text} - jamais pending ni la valeur de remplissage');
}
{
    // Restauration via loadGuestHistoryEntry() - même mécanisme que ?edit=/?remix= (spec).
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.guestHistory = [{
        date: new Date().toISOString(),
        title: 'Un prompt',
        state: { taskObject: 'Rédige un texte sur fractions.', spaces: [{ text: 'fractions' }] },
    }];
    component.loadGuestHistoryEntry(0);
    assert(component.spaces.length === 1 && component.spaces[0].text === 'fractions', 'espace restauré depuis l\'historique');
    assert(component.spaces[0].pending === false, 'un espace restauré n\'est jamais pending (personnalisation déjà faite lors de la sauvegarde)');
    assert(component.spaceMissing(component.spaces[0]) === false, '_refreshSpaceMissing() a bien tourné après restauration (espace retrouvé)');
}

// 11. Frontières de mots (round adversarial DeepSeek 2026-08-07) : un espace « son » ne matche
//     JAMAIS au milieu de « maison » - ni au remplissage, ni au renommage, ni au statut missing.
(function () {
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Analyse le son de la maison.';
    component.spaceBubble = { show: true, text: 'son', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    assert(component.spaces.length === 1 && component.spaces[0].text === 'son', 'espace « son » créé');
    component.spaceValues['son'] = 'bruit';
    assert(component.promptFilled.indexOf('bruit de la maison') !== -1, 'remplissage : « son » isolé remplacé');
    assert(component.promptFilled.indexOf('maison') !== -1 && component.promptFilled.indexOf('maibruit') === -1, 'remplissage : « maison » jamais corrompu');
    component.spaceEditingIndex = 0;
    component.spaceEditingText = 'tempo';
    component.commitRenameSpace(0);
    assert(component.taskObject === 'Analyse le tempo de la maison.', 'renommage : occurrence isolée renommée, « maison » intact');
    // Statut missing cohérent avec les frontières : « son » présent uniquement DANS « maison ».
    component.spaces[0].text = 'son';
    component.taskObject = 'Décris la maison.';
    component._refreshSpaceMissing();
    assert(component.spaceMissing(component.spaces[0]) === true, 'missing : « son » dans « maison » seulement = non retrouvé');
}());

// 12. Fusion au renommage vers le texte d'un AUTRE espace (jamais de pastille en double).
(function () {
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'Compare le chat et le chien.';
    component.spaceBubble = { show: true, text: 'chat', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    component.spaceBubble = { show: true, text: 'chien', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    component.spaceValues['chien'] = 'le loup';
    component.spaceEditingIndex = 0;
    component.spaceEditingText = 'chien';
    component.commitRenameSpace(0);
    assert(component.spaces.length === 1 && component.spaces[0].text === 'chien', 'fusion : un seul espace « chien » après renommage de « chat »');
    assert(component.taskObject === 'Compare le chien et le chien.', 'fusion : les occurrences de « chat » sont devenues « chien »');
    assert(component.spaceValues['chien'] === 'le loup', 'fusion : la valeur déjà saisie sous « chien » est conservée (jamais écrasée)');
}());

// 10. Mémoire lastValues (localStorage cpSpaceLastValues_v1) : mise à jour au moment de
//     copier/ouvrir (valeurs non vides seulement), jamais à chaque frappe.
(async function () {
    const sharedStore = {};
    const { component } = loadPromptBuilder(sharedStore);
    fillBaseFields(component);
    component.taskObject = 'Rédige un texte sur fractions.';
    component.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component.createSpaceFromSelection();
    component.spaceValues['fractions'] = 'les probabilités';
    assert(component.spaceLastValues['fractions'] === undefined, 'aucune mémoire avant la copie/ouverture');
    component.copy();
    await Promise.resolve(); await Promise.resolve();
    assert(component.spaceLastValues['fractions'] === 'les probabilités', 'copy() mémorise la valeur saisie dans spaceLastValues');
    assert(JSON.parse(global.localStorage.getItem('cpSpaceLastValues_v1'))['fractions'] === 'les probabilités', 'persisté dans localStorage sous la clé versionnée cpSpaceLastValues_v1');

    // Une 2e session (même stockage navigateur - sharedStore) recharge cette mémoire. Appel direct
    // de _loadSpaceLastValues() (ce que init() ferait automatiquement dans un vrai navigateur -
    // le harnais de test n'exécute pas le cycle de vie Alpine complet, voir les autres .test.cjs
    // du projet qui suivent le même patron).
    const { component: component2 } = loadPromptBuilder(sharedStore);
    fillBaseFields(component2);
    component2._loadSpaceLastValues();
    component2.taskObject = 'Rédige un autre texte sur fractions.';
    component2.spaceBubble = { show: true, text: 'fractions', fieldId: 'cpTaskObject' };
    component2.createSpaceFromSelection();
    assert(component2.spaceLastValues['fractions'] === 'les probabilités', '_loadSpaceLastValues() recharge la mémoire au démarrage d\'une nouvelle session');
})();

setTimeout(function () {
    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
}, 50);
