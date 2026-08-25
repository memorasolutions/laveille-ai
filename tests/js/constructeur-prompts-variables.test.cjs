// tests/js/constructeur-prompts-variables.test.cjs
// Garde-fou de non-régression (#1593b, 2026-08-07) : variables réutilisables {{nom}}.
//   1. get promptVariables() détecte les motifs {{nom}}, dédupliqués, ordre d'apparition conservé.
//   2. get promptFilled() substitue les valeurs saisies (varValues) et laisse intactes les
//      variables non remplies.
//   3. copy() et openIn() utilisent promptFilled (pas le prompt brut) pour la copie/le lien.
//   4. Aucune modification du schéma DB : wizardParams garde les {{...}} tels quels.
// Exécute : node tests/js/constructeur-prompts-variables.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder() {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;
    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: false, i18n: {} },
        dispatchEvent: () => {},
        toast: () => {},
    };
    global.navigator = global.navigator || {};
    let lastCopied = null;
    global.navigator.clipboard = { writeText: (text) => { lastCopied = text; return Promise.resolve(); } };
    global.window.copyToClipboard = function (text) { lastCopied = text; return navigator.clipboard.writeText(text).then(() => true); };
    global.window.open = function () {};
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.URLSearchParams = require('url').URLSearchParams;
    global.localStorage = {
        _store: {},
        getItem(k) { return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { this._store[k] = String(v); },
        removeItem(k) { delete this._store[k]; },
    };
    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    return { component, getLastCopied: () => lastCopied };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

function fillBaseFields(component) {
    component.personaPreset = 'redacteur_web';
    component.personas = [{ value: 'redacteur_web', label: 'Rédacteur web' }];
    component.verbType = 'preset';
    component.verb = 'Rédige';
}

// 1. Détection : un seul motif {{sujet}}.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}} pour {{public}}';
    const vars = component.promptVariables;
    assert(vars.length === 2 && vars[0] === 'sujet' && vars[1] === 'public', 'les deux variables sont détectées dans l\'ordre d\'apparition : ' + JSON.stringify(vars));
}

// 2. Déduplication : {{sujet}} répété deux fois ne compte qu'une fois.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'parle de {{sujet}} puis reviens sur {{sujet}}';
    assert(component.promptVariables.length === 1 && component.promptVariables[0] === 'sujet', 'une seule occurrence de "sujet" malgré la répétition');
}

// 3. Aucune variable : tableau vide.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte simple sans accolades';
    assert(Array.isArray(component.promptVariables) && component.promptVariables.length === 0, 'aucune variable détectée sans motif {{...}}');
}

// 4. promptFilled substitue la valeur saisie.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}}';
    component.varValues.sujet = 'les changements climatiques';
    assert(component.promptFilled.includes('un texte sur les changements climatiques'), 'la variable remplie est substituée dans promptFilled');
    assert(!component.promptFilled.includes('{{sujet}}'), 'le motif {{sujet}} a disparu du prompt rempli');
    assert(component.prompt.includes('{{sujet}}'), 'le prompt brut (aperçu technique) garde le motif {{sujet}} intact');
}

// 5. Variable non remplie : reste telle quelle (jamais bloquant).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}}';
    assert(component.promptFilled.includes('{{sujet}}'), 'une variable vide reste affichée telle quelle dans promptFilled');
}

// 6. copy() copie le prompt REMPLI (promptFilled), pas le prompt brut.
(async function () {
    const { component, getLastCopied } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}}';
    component.varValues.sujet = 'le climat';
    component.copy();
    await Promise.resolve(); await Promise.resolve();
    assert((getLastCopied() || '').includes('le climat'), 'copy() copie bien la valeur substituée');
    assert(!(getLastCopied() || '').includes('{{sujet}}'), 'copy() ne copie jamais le motif brut quand la variable est remplie');
})();

// 7. openIn() utilise aussi promptFilled par défaut (pas this.prompt).
{
    const { component, getLastCopied } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}}';
    component.varValues.sujet = 'le climat';
    component.openIn('chatgpt');
    assert((getLastCopied() || '').includes('le climat'), 'openIn() copie le prompt REMPLI pour la variable détectée');
}

// 8. wizardParams (sérialisation) garde les {{...}} intacts (aucune modification du schéma DB).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}}';
    component.varValues.sujet = 'le climat';
    assert(component.wizardParams.taskObject === 'un texte sur {{sujet}}', 'wizardParams.taskObject garde {{sujet}} tel quel (jamais substitué)');
}

setTimeout(function () {
    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
}, 50);

// ---------------------------------------------------------------------------
// Signalement fondateur (2026-08-25) : « je me retrouve avec des variables dans
// mon prompt, normal ? ». Le comportement l'était ; son ABSENCE DE SIGNALEMENT
// ne l'était pas. Un espace à remplir vide était annoncé avant la copie, une
// variable vide ne l'était pas, et les repères ⟦DONNEES-...⟧ n'étaient expliqués
// nulle part. Ces cas verrouillent les trois getters ajoutés.
// ---------------------------------------------------------------------------

// 6. Compteur de variables non remplies.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}} pour {{public}}';
    assert(component.unfilledVariablesCount === 2, 'deux variables vides sont comptées : ' + component.unfilledVariablesCount);
    component.varValues = { sujet: 'le climat' };
    assert(component.unfilledVariablesCount === 1, 'une seule reste vide après remplissage partiel');
    component.varValues = { sujet: 'le climat', public: 'des ados' };
    assert(component.unfilledVariablesCount === 0, 'aucune vide une fois les deux remplies');
    assert(component.unfilledVariablesMessage === '', 'aucun message quand tout est rempli');
}

// 7. Une valeur faite d'espaces compte comme non remplie (même règle que les espaces à remplir).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}}';
    component.varValues = { sujet: '   ' };
    assert(component.unfilledVariablesCount === 1, 'une valeur faite d\'espaces ne compte pas comme remplie');
    assert(component.unfilledVariablesMessage.indexOf('1 variable') === 0, 'message au singulier : ' + component.unfilledVariablesMessage);
}

// 8. Message au pluriel avec le compte substitué.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte sur {{sujet}} pour {{public}} avant {{date}}';
    const msg = component.unfilledVariablesMessage;
    assert(msg.indexOf('3') !== -1, 'le compte est substitué dans le message : ' + msg);
    assert(msg.indexOf('{count}') === -1, 'le gabarit {count} ne fuit jamais dans le message affiché');
}

// 9. Repères de données : présents seulement si contexte ou exemples remplis.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte';
    assert(component.hasDataDelimiters === false, 'aucun repère annoncé sans contexte ni exemples');
    assert(component.dataDelimitersMessage === '', 'aucun message sans repère');
    component.contextInfo = 'La recherche doit être à jour.';
    assert(component.hasDataDelimiters === true, 'repères annoncés dès que le contexte est rempli');
    assert(component.dataDelimitersMessage.indexOf('DONNEES') !== -1, 'le message nomme le repère réel');
}

// 10. Les exemples seuls suffisent aussi, et un texte fait d'espaces ne compte pas.
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte';
    component.contextInfo = '   ';
    assert(component.hasDataDelimiters === false, 'un contexte fait d\'espaces ne déclenche pas la mention');
    component.examples = 'Exemple 1 : ...';
    assert(component.hasDataDelimiters === true, 'les exemples seuls déclenchent la mention');
}

// 11. Le prompt copié contient bien le repère réel quand le contexte est rempli
//     (cohérence entre ce que la mention annonce et ce qui part au presse-papiers).
{
    const { component } = loadPromptBuilder();
    fillBaseFields(component);
    component.taskObject = 'un texte';
    component.contextInfo = 'Une donnée de fond.';
    assert(component.promptFilled.indexOf('⟦DONNEES-') !== -1, 'le prompt copié porte le repère avec suffixe');
    assert(component.prompt.indexOf('⟦DONNEES⟧') !== -1, 'l\'aperçu écran porte le repère sans suffixe');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail === 0 ? 0 : 1);
