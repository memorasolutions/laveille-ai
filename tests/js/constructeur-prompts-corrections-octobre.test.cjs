// tests/js/constructeur-prompts-corrections-octobre.test.cjs
// Garde-fous (2026-10-12) : format « Courriel » classé comme livrable, avertissement doux
// longueur « Concis » + format « Paragraphes détaillés », bouton de fin à l'étape 4.
// Exécute : node tests/js/constructeur-prompts-corrections-octobre.test.cjs
const fs = require('fs');
const path = require('path');

function loadPromptBuilder() {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;
    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
        getElementById: () => null,
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {}, today: { long: '12 octobre 2026', iso: '2026-10-12' } },
        dispatchEvent: () => {},
        toast: () => {},
    };
    global.navigator = global.navigator || {};
    global.navigator.clipboard = { writeText: () => Promise.resolve() };
    global.window.copyToClipboard = function (text) { return navigator.clipboard.writeText(text).then(() => true); };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.URLSearchParams = require('url').URLSearchParams;
    global.localStorage = { _store: {}, getItem(k) { return this._store[k] || null; }, setItem(k, v) { this._store[k] = String(v); }, removeItem(k) { delete this._store[k]; } };
    global.fetch = () => Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

{
    const c = loadPromptBuilder();
    assert(c._formatDeliverableValues.indexOf('Courriel') !== -1, 'Courriel est classé parmi les livrables');
    assert(c._formatStructureValues.indexOf('Courriel') === -1 && c._formatExclusiveValues.indexOf('Courriel') === -1, 'Courriel n\'est ni une structure ni un format exclusif');
    c.formatsSelected = ['Courriel'];
    assert(c.formatBulletText === 'Structure : Courriel', 'seul, Courriel produit la même ligne que les autres livrables');
    c.formatsSelected = ['Courriel', 'Fiche pratique (1 page)'];
    assert(c.formatBulletText.indexOf('1) Courriel') !== -1, 'plusieurs livrables : Courriel entre dans la séquence numérotée');
}
{
    const c = loadPromptBuilder();
    c.formatsSelected = ['Paragraphes détaillés']; c.length = 'Concis (100-200 mots)';
    assert(c.lengthFormatConflict === true, 'Concis + Paragraphes détaillés : avertissement actif');
    assert(c.lengthFormatConflictMessage.indexOf('Concis') !== -1, 'le message d\'avertissement est disponible');
    c.length = 'Modéré (300-500 mots)';
    assert(c.lengthFormatConflict === false, 'une autre longueur : pas d\'avertissement');
    c.length = 'Concis (100-200 mots)'; c.formatsSelected = ['Liste à puces'];
    assert(c.lengthFormatConflict === false, 'un autre format : pas d\'avertissement');
    c.formatsSelected = ['Paragraphes détaillés']; c.taskObject = 'x'; c.personaType = 'custom'; c.personaCustom = 'Expert'; c.verbType = 'custom'; c.verbCustom = 'Rédige';
    assert(c.isValid && c.prompt.length > 0, 'l\'avertissement ne bloque pas la génération du prompt');
}
{
    const c = loadPromptBuilder();
    assert(typeof c.finishAndCopy === 'function', 'finishAndCopy existe pour le bouton de l\'étape 4');
    let copied = 0; c.copy = function () { copied++; };
    c.finishAndCopy();
    assert(copied === 0, 'prompt incomplet : rien n\'est copié');
    c.taskObject = 'x'; c.personaType = 'custom'; c.personaCustom = 'Expert'; c.verbType = 'custom'; c.verbCustom = 'Rédige';
    c.finishAndCopy();
    assert(copied === 1, 'prompt complet : copy() est déclenché');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
