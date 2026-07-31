// tests/js/constructeur-prompts-storageerrors.test.cjs
// Garde-fou de non-regression (round 65, 2026-07-27) : 3 sites de constructeur-prompts-core.js
// appelaient localStorage.getItem/setItem/removeItem SANS try/catch, alors que localStorage peut
// lever (mode privé Safari, storage désactivé par politique, quota plein) :
//   1. init() (bloc historique authentifié) : localStorage.getItem('pb_history') non protégé
//      faisait rejeter le .then() du GET /api/prompts réussi et tombait dans le .catch(), qui
//      ÉCRASAIT self.history (déjà rempli avec les vraies données serveur) par du contenu périmé.
//   2. importLocalStorage() : localStorage.setItem/removeItem('pb_history') non protégés
//      empêchaient self.importing de repasser à false si le storage levait, bloquant le bouton
//      "Importer" en PERMANENCE même si l'import avait réellement réussi côté serveur.
//   3. importLocalCustomCards() (branche sans troncature) : localStorage.removeItem('cp_custom_cards')
//      non protégé faisait tomber dans le .catch() qui affiche un toast d'ERREUR trompeur alors que
//      l'import avait réellement réussi.
// Execute : node tests/js/constructeur-prompts-storageerrors.test.cjs (ou npm run test:js)
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
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
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
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 (round 65) : localStorage.getItem lève dans init() -> l'historique serveur survit ---
    {
        const component = loadPromptBuilder(function (url) {
            if (url === '/api/prompts') {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [{ public_id: 'srv1', prompt_text: 'x', name: 'Prompt serveur', created_at: '2026-01-01T00:00:00Z', params: {} }] }) });
            }
            return Promise.resolve({ ok: false, status: 500, json: () => Promise.resolve({}) });
        }, ['get:pb_history']);

        component.init();
        await flush();

        assert(component.historyLoaded === true, 'round 65 : historyLoaded passe bien à true malgré l\'exception localStorage');
        assert(component.history.length === 1 && component.history[0].id === 'srv1', 'round 65 : l\'historique SERVEUR (vraies données) n\'est PAS écrasé par l\'exception localStorage.getItem');
    }

    // --- Test 2a (round 65) : tous les items importés avec succès (remaining=[]) -> branche removeItem() lève -> importing repasse quand même à false ---
    {
        const component = loadPromptBuilder(function (url, opts) {
            if (url === '/api/prompts' && opts && opts.method === 'POST') {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ id: 'p1', prompt_text: 'x', name: 'x', created_at: '2026-01-01', params: {} }) });
            }
            return Promise.resolve({ ok: false, status: 500, json: () => Promise.resolve({}) });
        }, ['remove:pb_history']);
        component.historyLoaded = true;
        global.localStorage._store['pb_history'] = JSON.stringify([{ name: 'Local 1', prompt: 'a' }]);

        component.importLocalStorage();
        await flush();

        assert(component.importing === false, 'round 65a : importing repasse bien à false malgré l\'exception localStorage.removeItem (branche remaining=[], pas de lockout permanent du bouton Importer)');
    }

    // --- Test 2b (round 65) : au moins un item échoue (remaining.length>0) -> branche setItem() lève -> importing repasse quand même à false ---
    {
        const component = loadPromptBuilder(function (url, opts) {
            if (url === '/api/prompts' && opts && opts.method === 'POST') {
                return Promise.resolve({ ok: false, status: 500, json: () => Promise.resolve({}) });
            }
            return Promise.resolve({ ok: false, status: 500, json: () => Promise.resolve({}) });
        }, ['set:pb_history']);
        component.historyLoaded = true;
        global.localStorage._store['pb_history'] = JSON.stringify([{ name: 'Local 1', prompt: 'a' }]);

        component.importLocalStorage();
        await flush();

        assert(component.importing === false, 'round 65b : importing repasse bien à false malgré l\'exception localStorage.setItem (branche remaining>0, pas de lockout permanent du bouton Importer)');
    }

    // --- Test 3 (round 65) : localStorage.removeItem lève dans importLocalCustomCards() -> pas de faux toast d'erreur ---
    {
        const component = loadPromptBuilder(function (url, opts) {
            var body = JSON.parse(opts.body);
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: body.value } }) });
        }, ['remove:cp_custom_cards']);
        component._localCardsToImport = [{ id: 'local1', title: 'Carte importee', icon: '⭐', query_template: '', hidden: false }];

        var errorShown = false;
        component._showSaveError = function () { errorShown = true; };

        component.importLocalCustomCards();
        await flush();

        assert(errorShown === false, 'round 65 : aucun toast d\'erreur trompeur affiché malgré l\'exception localStorage.removeItem (l\'import a réellement réussi côté serveur)');
        assert(component.customCardsImportAvailable === false, 'round 65 : la bannière d\'import est bien masquée malgré l\'exception localStorage.removeItem');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
