// tests/js/constructeur-prompts-hasLocalDataStale.test.cjs
// Garde-fou de non-regression (round 90, 2026-07-27) : init() testait uniquement la PRESENCE de
// la cle localStorage 'pb_history' (`if (localStorage.getItem('pb_history')) self.hasLocalData =
// true;`), jamais son contenu reel. deletePrompt() (branche invite) ecrit litteralement la chaine
// '[]' quand le dernier item local est supprime - non-vide donc truthy en JS - ce qui laissait
// hasLocalData bloque a true (banniere + bouton "Importer" affiches en permanence, meme apres
// connexion, sans jamais rien a importer). Fix : parser le JSON et verifier la longueur du
// tableau. Defense en profondeur additionnelle : importLocalStorage() reinitialise desormais
// hasLocalData a false sur son retour anticipe (local.length === 0).
// Execute : node tests/js/constructeur-prompts-hasLocalDataStale.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(fetchImpl, initialStorage) {
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
        toast: () => {},
    };
    global.navigator = { clipboard: { writeText: () => {} } };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.URLSearchParams = require('url').URLSearchParams;
    global.localStorage = {
        _store: Object.assign({}, initialStorage || {}),
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
    // --- Test 1 (round 90) : pb_history = '[]' (dernier item local supprimé) -> hasLocalData reste false ---
    {
        const component = loadPromptBuilder(
            function () { return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) }); },
            { pb_history: '[]' }
        );
        component.init();
        await flush();

        assert(component.hasLocalData === false, "round 90 : hasLocalData reste false quand pb_history='[]' (chaîne truthy mais tableau vide)");
        assert(component.historyLoaded === true, "round 90 : historyLoaded passe bien à true");
    }

    // --- Test 2 (round 90, non-régression) : pb_history contient réellement des items -> hasLocalData devient true ---
    {
        const component = loadPromptBuilder(
            function () { return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) }); },
            { pb_history: JSON.stringify([{ id: 'local1', prompt: 'x', name: 'x', date: '', params: {} }]) }
        );
        component.init();
        await flush();

        assert(component.hasLocalData === true, "round 90 (non-régression) : hasLocalData devient true avec des items locaux réels");
    }

    // --- Test 3 (round 90) : importLocalStorage() en défense - retour anticipé remet hasLocalData à false ---
    {
        const component = loadPromptBuilder(
            function () { return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) }); },
            { pb_history: '[]' }
        );
        component.historyLoaded = true;
        component.hasLocalData = true; // état incohérent simulé (ex. legacy avant le fix)

        component.importLocalStorage();

        assert(component.hasLocalData === false, "round 90 : importLocalStorage() réinitialise hasLocalData sur son retour anticipé (local.length === 0)");
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
