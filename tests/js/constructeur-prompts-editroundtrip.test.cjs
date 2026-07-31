// tests/js/constructeur-prompts-editroundtrip.test.cjs
// Garde-fou de non-regression (round 42, 2026-07-27) : le getter wizardParams (envoye au serveur a
// la sauvegarde) omettait 6 champs qui influencent pourtant le texte final genere (get prompt()) :
// constraintTypo, constraintChainOfThought, constraintAskIfUnclear, constraintCustom, useDelimiters,
// examples. Consequence : quand un utilisateur cliquait "Reutiliser" sur un prompt sauvegarde
// (?edit=ID), init() relisait found.params mais ne reassignait jamais ces 6 champs - le wizard
// rouvrait avec ces options silencieusement reinitialisees. Un "Enregistrer" ulterieur ecrasait la
// version en base avec ces champs perdus (perte de donnee permanente et silencieuse). Le fix ajoute
// les 6 champs a wizardParams ET a la restauration du bloc ?edit=ID.
// Execute : node tests/js/constructeur-prompts-editroundtrip.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(fetchImpl, search) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;

    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
        getElementById: () => null,
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
        location: { search: search || '' },
        dispatchEvent: () => {},
    };
    global.URLSearchParams = URLSearchParams;
    global.navigator.clipboard = { writeText: () => {} };
    global.CustomEvent = class { constructor(type, o) { this.type = type; this.detail = o && o.detail; } };
    global.localStorage = {
        _store: {},
        getItem(k) { return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { this._store[k] = String(v); },
        removeItem(k) { delete this._store[k]; },
    };
    global.fetch = fetchImpl;

    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    component.customCardsLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 : wizardParams inclut bien les 6 champs auparavant omis ---
    {
        const component = loadPromptBuilder(function () {
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
        });
        component.constraintTypo = true;
        component.constraintChainOfThought = true;
        component.constraintAskIfUnclear = true;
        component.constraintCustom = 'Toujours répondre en tableau';
        component.useDelimiters = true;
        component.examples = 'Exemple 1 : ...\nExemple 2 : ...';

        var p = component.wizardParams;
        assert(p.constraintTypo === true, 'round 42 : wizardParams inclut constraintTypo');
        assert(p.constraintChainOfThought === true, 'round 42 : wizardParams inclut constraintChainOfThought');
        assert(p.constraintAskIfUnclear === true, 'round 42 : wizardParams inclut constraintAskIfUnclear');
        assert(p.constraintCustom === 'Toujours répondre en tableau', 'round 42 : wizardParams inclut constraintCustom');
        assert(p.useDelimiters === true, 'round 42 : wizardParams inclut useDelimiters');
        assert(p.examples === 'Exemple 1 : ...\nExemple 2 : ...', 'round 42 : wizardParams inclut examples');
    }

    // --- Test 2 : round-trip complet - ?edit=ID restaure bien les 6 champs depuis found.params ---
    {
        const savedParams = {
            personaType: 'preset', personaPreset: 'expert', verbType: 'preset', verb: 'rédiger',
            taskObject: 'un rapport', audienceType: 'preset', format: 'liste', length: 'moyen',
            tone: 'professionnel', language: 'fr', technique: 'few-shot',
            constraintAntiAI: true, constraintCanvas: false, canvasAI: 'chatgpt', formatMode: 'preset',
            constraintTypo: true,
            constraintChainOfThought: true,
            constraintAskIfUnclear: true,
            constraintCustom: 'Toujours citer une source',
            useDelimiters: true,
            examples: 'Exemple A\nExemple B',
        };
        const component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts/') === 0) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ public_id: 'abc123', name: 'Mon prompt', params: savedParams }) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
        }, '?edit=abc123');

        component.init();
        await flush();

        assert(component.constraintTypo === true, 'round 42 : ?edit=ID restaure constraintTypo');
        assert(component.constraintChainOfThought === true, 'round 42 : ?edit=ID restaure constraintChainOfThought');
        assert(component.constraintAskIfUnclear === true, 'round 42 : ?edit=ID restaure constraintAskIfUnclear');
        assert(component.constraintCustom === 'Toujours citer une source', 'round 42 : ?edit=ID restaure constraintCustom');
        assert(component.useDelimiters === true, 'round 42 : ?edit=ID restaure useDelimiters');
        assert(component.examples === 'Exemple A\nExemple B', 'round 42 : ?edit=ID restaure examples');

        // Ré-enregistrer (PUT implicite via wizardParams) ne doit PAS perdre ces champs.
        var reSaved = component.wizardParams;
        assert(reSaved.constraintCustom === 'Toujours citer une source', 'round 42 : un ré-enregistrement après édition conserve constraintCustom (pas de perte au round-trip complet)');
        assert(reSaved.examples === 'Exemple A\nExemple B', 'round 42 : un ré-enregistrement après édition conserve examples');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
