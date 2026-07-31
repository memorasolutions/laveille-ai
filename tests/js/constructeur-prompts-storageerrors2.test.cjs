// tests/js/constructeur-prompts-storageerrors2.test.cjs
// Garde-fou de non-regression (round 66, 2026-07-27) : 2 sites `localStorage` supplémentaires,
// manqués au round 65, appelaient localStorage.setItem/removeItem SANS try/catch :
//   1. deletePrompt() branche invité (else) : localStorage.setItem('pb_history', ...) non protégé
//      levait une exception non interceptée APRÈS que l'item ait déjà disparu visuellement
//      (this.history.splice(index,1) mute l'état réactif avant l'appel storage), sans jamais
//      persister réellement la suppression si le storage était indisponible.
//   2. clearHistory() : localStorage.removeItem('pb_history') non protégé, même risque - the
//      history est déjà vidé visuellement (this.history = []) avant l'appel storage.
// Execute : node tests/js/constructeur-prompts-storageerrors2.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(fetchImpl, storageThrows) {
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
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: false, i18n: {} },
        dispatchEvent: () => {},
        toast: () => {},
    };
    global.navigator.clipboard = { writeText: () => {} };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.URLSearchParams = require('url').URLSearchParams;
    var throwingKeys = storageThrows || [];
    global.localStorage = {
        _store: {},
        getItem(k) { if (throwingKeys.indexOf('get:' + k) !== -1) throw new Error('storage_blocked'); return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { if (throwingKeys.indexOf('set:' + k) !== -1) throw new Error('storage_blocked'); this._store[k] = String(v); },
        removeItem(k) { if (throwingKeys.indexOf('remove:' + k) !== -1) throw new Error('storage_blocked'); delete this._store[k]; },
    };
    global.fetch = fetchImpl;

    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    component.customCardsLoaded = true;
    component.historyLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

(function run() {
    // --- Test 1 (round 66) : deletePrompt() branche invité, localStorage.setItem lève ---
    {
        const component = loadPromptBuilder(function () { return Promise.resolve({ ok: false, status: 500 }); }, ['set:pb_history']);
        component.isAuthenticated = false;
        component.history = [{ id: 'local1', prompt: 'x', name: 'x', date: '', params: {} }];

        var threw = false;
        try {
            component.deletePrompt('local1', 0);
        } catch (e) {
            threw = true;
        }

        assert(threw === false, 'round 66 : deletePrompt() (invité) ne propage pas l\'exception localStorage.setItem vers l\'appelant');
        assert(component.history.length === 0, 'round 66 : l\'item est bien retiré de l\'état réactif malgré l\'exception localStorage');
    }

    // --- Test 2 (round 66) : non-régression - deletePrompt() invité persiste normalement sans exception ---
    {
        const component = loadPromptBuilder(function () { return Promise.resolve({ ok: false, status: 500 }); });
        component.isAuthenticated = false;
        component.history = [{ id: 'local1', prompt: 'x', name: 'x', date: '', params: {} }];

        component.deletePrompt('local1', 0);

        assert(component.history.length === 0, 'round 66 (non-régression) : suppression invité fonctionne normalement');
        assert(JSON.parse(global.localStorage._store['pb_history']).length === 0, 'round 66 (non-régression) : localStorage bien mis à jour (persisté)');
    }

    // --- Test 3 (round 66) : clearHistory(), localStorage.removeItem lève ---
    {
        const component = loadPromptBuilder(function () { return Promise.resolve({ ok: false, status: 500 }); }, ['remove:pb_history']);
        component.isAuthenticated = false;
        component.history = [{ id: 'local1', prompt: 'x', name: 'x', date: '', params: {} }];

        var threw = false;
        try {
            component.clearHistory();
        } catch (e) {
            threw = true;
        }

        assert(threw === false, 'round 66 : clearHistory() ne propage pas l\'exception localStorage.removeItem vers l\'appelant');
        assert(component.history.length === 0, 'round 66 : history bien vidé dans l\'état réactif malgré l\'exception localStorage');
    }

    // --- Test 4 (round 66) : non-régression - clearHistory() vide normalement le localStorage ---
    {
        const component = loadPromptBuilder(function () { return Promise.resolve({ ok: false, status: 500 }); });
        component.isAuthenticated = false;
        component.history = [{ id: 'local1', prompt: 'x', name: 'x', date: '', params: {} }];
        global.localStorage._store['pb_history'] = JSON.stringify(component.history);

        component.clearHistory();

        assert(component.history.length === 0, 'round 66 (non-régression) : clearHistory() vide bien history');
        assert(Object.prototype.hasOwnProperty.call(global.localStorage._store, 'pb_history') === false, 'round 66 (non-régression) : localStorage bien nettoyé (removeItem effectif)');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
