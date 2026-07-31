// tests/js/constructeur-prompts-doublesubmit.test.cjs
// Garde-fou de non-regression (round 36, 2026-07-27) : importLocalStorage() et deletePrompt()
// n'avaient aucun garde anti double-soumission (double-clic/double-tap), contrairement a
// addToHistory() (`if (this.saving) return;`, round 34/35). Sans garde : importLocalStorage()
// poste chaque item de localStorage.pb_history en double (duplication reelle en base, aucune
// contrainte d'unicite cote serveur) ; deletePrompt() envoie 2 DELETE, le 2e tombant sur un id
// deja supprime (404) et affichant une erreur trompeuse pour une suppression qui a pourtant
// pleinement reussi.
// Execute : node tests/js/constructeur-prompts-doublesubmit.test.cjs (ou npm run test:js)
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
    // Round 63 (2026-07-27) : importLocalStorage()/deletePrompt() exigent désormais
    // historyLoaded=true (garde contre la course avec le GET initial de init(), jamais appelé ici
    // puisqu'on teste ces fonctions isolément) - même convention que customCardsLoaded.
    component.historyLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 : double-clic sur "Importer" avec 1 seul item en localStorage -> 1 seul POST ---
    {
        let postCount = 0;
        const component = loadPromptBuilder(function (url, opts) {
            if (url === '/api/prompts' && opts && opts.method === 'POST') {
                postCount++;
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ id: 'p1', prompt_text: 'x', name: 'x', created_at: '2026-01-01', params: {} }) });
            }
            return Promise.resolve({ ok: false, status: 500, json: () => Promise.resolve({}) });
        });
        global.localStorage.setItem('pb_history', JSON.stringify([{ name: 'Local prompt', prompt: 'Un prompt local' }]));
        component.hasLocalData = true;

        component.importLocalStorage();
        component.importLocalStorage(); // double-clic avant résolution de la 1re requête
        await flush();

        assert(postCount === 1, 'round 36 : double-clic sur Importer ne poste qu\'une seule fois (pas de duplication en base)');
        assert(component.importing === false, 'le flag importing retombe à false une fois l\'import terminé');
    }

    // --- Test 2 : double-clic sur "✕" (suppression) du même prompt -> 1 seul DELETE ---
    {
        let deleteCount = 0;
        const component = loadPromptBuilder(function (url, opts) {
            if (opts && opts.method === 'DELETE') {
                deleteCount++;
                return Promise.resolve({ ok: true });
            }
            return Promise.resolve({ ok: false, status: 500 });
        });
        component.history = [{ id: 'p42', prompt: 'x', name: 'x', date: '', params: {} }];

        component.deletePrompt('p42', 0);
        component.deletePrompt('p42', 0); // double-clic avant résolution de la 1re requête
        await flush();

        assert(deleteCount === 1, 'round 36 : double-clic sur ✕ n\'envoie qu\'un seul DELETE (pas de 404 trompeur sur le 2e)');
        assert(component._deletingIds.length === 0, 'l\'id retiré de _deletingIds une fois la suppression résolue');
    }

    // --- Test 3 : non-régression - 2 suppressions de prompts DIFFÉRENTS restent toutes 2 traitées ---
    {
        let deleteCount = 0;
        const component = loadPromptBuilder(function (url, opts) {
            if (opts && opts.method === 'DELETE') {
                deleteCount++;
                return Promise.resolve({ ok: true });
            }
            return Promise.resolve({ ok: false, status: 500 });
        });
        component.history = [
            { id: 'a', prompt: 'x', name: 'x', date: '', params: {} },
            { id: 'b', prompt: 'y', name: 'y', date: '', params: {} },
        ];

        component.deletePrompt('a', 0);
        component.deletePrompt('b', 1);
        await flush();

        assert(deleteCount === 2, 'round 36 : la garde ne bloque PAS la suppression de 2 prompts différents en parallèle');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
