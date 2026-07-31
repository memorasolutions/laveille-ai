// tests/js/constructeur-prompts-cardtyperace.test.cjs
// Garde-fou de non-regression (round 61, 2026-07-27) : persistCustomCards() remplacait
// INTEGRALEMENT customCards par l'echo serveur (`self.customCards = data.preferences.custom_cards;`)
// des la reponse du POST, sans verifier si l'utilisateur avait continue de taper (x-model direct sur
// c.title / c.query_template) PENDANT que ce POST etait en vol. Scenario reel le plus courant :
// addCustomCard() cree une carte "Nouvelle carte", declenche persistCustomCards() ET focus
// immediatement le champ titre pour que l'utilisateur tape le vrai nom tout de suite - si la reponse
// du serveur arrive pendant la frappe, elle ecrasait silencieusement le texte tape par l'ancienne
// valeur echoee (envoyee AVANT la frappe), sans erreur ni avertissement. Le fix compare un snapshot
// JSON de customCards pris au moment de l'ENVOI a l'etat courant au moment de la REPONSE : l'echo
// serveur n'est applique que si rien n'a change localement entre-temps.
// Execute : node tests/js/constructeur-prompts-cardtyperace.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(fetchImpl) {
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
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
        dispatchEvent: () => {},
    };
    global.navigator.clipboard = { writeText: () => {} };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
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
    // --- Test 1 (round 61) : frappe pendant un POST en vol -> NE DOIT PAS être écrasée par l'écho ---
    {
        const calls = [];
        let resolveFirst;
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            return new Promise(function (resolve) { resolveFirst = resolve; });
        });

        component.addCustomCard();
        await flush();
        assert(calls.length === 1, 'round 61 : addCustomCard() déclenche bien un POST');

        // Simule la frappe utilisateur (x-model direct) PENDANT que le POST est en vol.
        component.customCards[0].title = 'Rédiger un courriel';

        // Le serveur répond avec l'écho de ce qui a été envoyé (titre encore "Nouvelle carte").
        resolveFirst({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: calls[0].value } }) });
        await flush();

        assert(component.customCards[0].title === 'Rédiger un courriel', 'round 61 : la frappe en cours survit à la réponse serveur en vol (pas écrasée)');
    }

    // --- Test 2 (round 61) : non-régression - aucune frappe pendant le vol -> l'écho s'applique normalement ---
    {
        const calls = [];
        let resolveFirst;
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            return new Promise(function (resolve) { resolveFirst = resolve; });
        });

        component.addCustomCard();
        await flush();

        resolveFirst({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: calls[0].value } }) });
        await flush();

        assert(component.customCards[0].title === 'Nouvelle carte', 'round 61 : sans frappe concurrente, l\'écho serveur reste appliqué normalement');
    }

    // --- Test 3 (round 61) : même risque sur query_template (textarea du panneau d'édition) ---
    {
        const calls = [];
        let resolveFirst;
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            return new Promise(function (resolve) { resolveFirst = resolve; });
        });

        component.addCustomCard();
        await flush();

        component.customCards[0].query_template = 'Corrige les fautes de ce texte : ';
        resolveFirst({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: calls[0].value } }) });
        await flush();

        assert(component.customCards[0].query_template === 'Corrige les fautes de ce texte : ', 'round 61 : la frappe dans query_template survit aussi à la réponse serveur en vol');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
