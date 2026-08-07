// tests/js/constructeur-prompts-contextinfo.test.cjs
// Garde-fou de non-régression (#1593a, 2026-08-07) : champ « Contexte additionnel », distinct de
// la tâche (taskObject), sur le modèle exact du champ « examples » existant.
//   1. Le prompt final injecte le contexte sous « Contexte : » quand il est renseigné, jamais
//      mélangé au bloc « Ta tâche : ».
//   2. wizardParams (l'objet de sérialisation, "getState") inclut contextInfo.
//   3. La désérialisation ?edit=ID restaure contextInfo (même mécanisme que examples).
// Exécute : node tests/js/constructeur-prompts-contextinfo.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(fetchImpl) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;
    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
        dispatchEvent: () => {},
        toast: () => {},
    };
    global.navigator = global.navigator || {};
    global.navigator.clipboard = { writeText: () => Promise.resolve() };
    global.window.copyToClipboard = function (text) { return navigator.clipboard.writeText(text).then(() => true); };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.URLSearchParams = require('url').URLSearchParams;
    global.localStorage = {
        _store: {},
        getItem(k) { return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { this._store[k] = String(v); },
        removeItem(k) { delete this._store[k]; },
    };
    global.fetch = fetchImpl || (() => Promise.resolve({ ok: true, json: () => Promise.resolve({}) }));
    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

// 1. Le contexte additionnel apparaît dans le prompt final sous "Contexte : ", séparé de la tâche.
{
    const component = loadPromptBuilder();
    component.personaPreset = 'redacteur_web';
    component.personas = [{ value: 'redacteur_web', label: 'Rédacteur web' }];
    component.verbType = 'preset';
    component.verb = 'Rédige';
    component.taskObject = 'un courriel de bienvenue';
    component.contextInfo = 'On a déjà essayé une version plus formelle qui n\'a pas fonctionné.';
    const prompt = component.prompt;
    assert(prompt.includes('Contexte : On a déjà essayé une version plus formelle'), 'le prompt contient "Contexte : " suivi du texte saisi');
    assert(prompt.indexOf('Ta tâche :') < prompt.indexOf('Contexte :'), 'le bloc Contexte apparaît après le bloc Tâche');
    assert(!prompt.includes('Ta tâche : Rédige un courriel de bienvenue.On a déjà essayé'), 'le contexte n\'est jamais mélangé au texte de la tâche');
}

// 2. Vide par défaut : aucune section "Contexte :" n'apparaît si le champ est vide.
{
    const component = loadPromptBuilder();
    component.personaPreset = 'redacteur_web';
    component.personas = [{ value: 'redacteur_web', label: 'Rédacteur web' }];
    component.verbType = 'preset';
    component.verb = 'Rédige';
    component.taskObject = 'un courriel';
    assert(!component.prompt.includes('Contexte :'), 'aucune section Contexte quand le champ est vide');
}

// 3. wizardParams (objet de sérialisation) inclut contextInfo.
{
    const component = loadPromptBuilder();
    component.contextInfo = 'Budget limité à 500$.';
    assert(component.wizardParams.contextInfo === 'Budget limité à 500$.', 'wizardParams contient contextInfo');
}

// 4. Désérialisation ?edit=ID : contextInfo restauré comme examples.
(async function () {
    const fetchImpl = (url) => {
        if (String(url).indexOf('/api/prompts/') === 0) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve({
                id: 42, public_id: 'abc123', name: 'Test',
                params: { personaPreset: 'redacteur_web', verb: 'Rédige', taskObject: 'un texte', contextInfo: 'Contexte restauré depuis la base.' },
            }) });
        }
        return Promise.resolve({ ok: true, json: () => Promise.resolve({ history: [] }) });
    };
    const component = loadPromptBuilder(fetchImpl);
    global.window.location.search = '?edit=abc123';
    component.init();
    for (let i = 0; i < 10; i++) await Promise.resolve();
    assert(component.contextInfo === 'Contexte restauré depuis la base.', 'contextInfo restauré après ?edit=ID (comme examples)');
})().then(() => {
    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
});
