// tests/js/constructeur-prompts-historyrace.test.cjs
// Garde-fou de non-regression (round 63, 2026-07-27) : init() ecrase self.history sans garde des
// que le GET initial /api/prompts resout (`self.history = (data.data || []).map(...)`) - meme
// classe de bug que les rounds 61 (persistCustomCards) et 62 (importLocalCustomCards), mais sur
// une TROISIEME variable d'etat (history) sans le flag de fraicheur equivalent a
// customCardsLoaded (round 41). Scenario reel : ?edit=ID place le wizard directement a l'etape 2
// (self.step=2), ou le bouton "Enregistrer" est immediatement cliquable, PENDANT que le GET
// /api/prompts (liste complete) est encore en vol - si addToHistory()/importLocalStorage()
// resout avant ce GET, l'echo tardif du GET (snapshot antérieur à la mutation) remplace
// integralement self.history et efface silencieusement le prompt pourtant confirme sauvegarde.
// Le fix introduit historyLoaded (meme pattern que customCardsLoaded) : addToHistory(),
// deletePrompt() et importLocalStorage() sont des no-op tant que le GET initial n'a pas resolu.
// Execute : node tests/js/constructeur-prompts-historyrace.test.cjs (ou npm run test:js)
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
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
        dispatchEvent: () => {},
        toast: () => {},
        location: { search: '' },
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
    global.URLSearchParams = require('url').URLSearchParams;

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
    // --- Test 1 (round 63) : addToHistory() AVANT que le GET initial résolve -> no-op, jamais perdu ---
    {
        var resolveInitial;
        var initialCalls = 0;
        var component = loadPromptBuilder(function (url) {
            if (url === '/api/prompts') {
                initialCalls++;
                return new Promise(function (resolve) { resolveInitial = resolve; });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });

        component.init();
        await flush();
        assert(initialCalls === 1, 'round 63 : init() déclenche bien le GET /api/prompts');
        assert(component.historyLoaded === false, 'round 63 : historyLoaded est false tant que le GET initial est en vol');

        // Tentative de sauvegarde AVANT que le GET initial ne résolve (ex. ?edit=ID, étape 2 immédiate).
        component.isValid = true;
        component.saveName = 'Mon prompt';
        component.prompt = 'Texte du prompt';
        component.wizardParams = {};
        component.addToHistory();
        await flush();

        assert(component.saving === false, 'round 63 : addToHistory() est un no-op tant que historyLoaded=false (aucun POST déclenché)');
        assert(component.history.length === 0, 'round 63 : history reste vide (rien à écraser) tant que le GET initial est en vol');

        // Le GET initial résout enfin (liste serveur vide, cas réaliste d\'un nouveau compte).
        resolveInitial({ ok: true, json: () => Promise.resolve({ data: [] }) });
        await flush();

        assert(component.historyLoaded === true, 'round 63 : historyLoaded passe à true une fois le GET initial résolu');
    }

    // --- Test 2 (round 63) : après historyLoaded=true, addToHistory() fonctionne normalement ---
    {
        var component = loadPromptBuilder(function (url, opts) {
            if (url === '/api/prompts' && (!opts || opts.method === undefined)) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
            }
            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ public_id: 'abc123', prompt_text: 'Texte', name: 'Mon prompt', created_at: '2026-07-27T00:00:00Z', params: {} }),
            });
        });

        component.init();
        await flush();
        assert(component.historyLoaded === true, 'round 63 : historyLoaded=true après résolution normale du GET initial');

        component.isValid = true;
        component.saveName = 'Mon prompt';
        component.prompt = 'Texte du prompt';
        component.wizardParams = {};
        component.addToHistory();
        await flush();

        assert(component.history.length === 1, 'round 63 (non-régression) : addToHistory() fonctionne normalement une fois historyLoaded=true');
        assert(component.history[0].id === 'abc123', 'round 63 (non-régression) : le prompt sauvegardé est bien celui attendu');
    }

    // --- Test 3 (round 63) : importLocalStorage() AVANT le GET initial -> no-op ---
    {
        var resolveInitial;
        var importCalls = 0;
        var component = loadPromptBuilder(function (url, opts) {
            // Le GET initial (init()) et le POST d'import (importLocalStorage()) visent la MÊME
            // URL '/api/prompts' - distinguer par méthode. init() appelle aussi _loadCustomCards()
            // (fetch vers /api/tool-preferences/...) : seul un vrai POST /api/prompts compte comme
            // une tentative d'import.
            if (url === '/api/prompts' && (!opts || opts.method === undefined)) {
                return new Promise(function (resolve) { resolveInitial = resolve; });
            }
            if (url === '/api/prompts' && opts && opts.method === 'POST') {
                importCalls++;
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });
        component.init();
        await flush();

        localStorage.setItem('pb_history', JSON.stringify([{ name: 'Local', prompt: 'Texte' }]));
        component.importLocalStorage();
        await flush();

        assert(importCalls === 0, 'round 63 : importLocalStorage() est un no-op tant que historyLoaded=false (aucun POST déclenché)');

        resolveInitial({ ok: true, json: () => Promise.resolve({ data: [] }) });
        await flush();
        assert(component.historyLoaded === true, 'round 63 : historyLoaded passe bien à true après résolution');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
