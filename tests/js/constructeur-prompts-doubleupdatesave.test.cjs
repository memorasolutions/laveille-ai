// tests/js/constructeur-prompts-doubleupdatesave.test.cjs
// Garde-fou de non-regression (round 98, 2026-07-27, passe adversariale) : apres une mise a jour
// reussie d'un prompt existant (?edit=ID -> PUT), addToHistory() remettait self._editingId a null.
// Le libelle du bouton redevient alors "Sauvegarder" (Blade ligne 37, pilote par _editingId). Si
// l'utilisateur reste sur la meme page et clique de nouveau sur le bouton de sauvegarde (sans
// recharger, sans revenir a ?edit=ID), addToHistory() fait cette fois un POST /api/prompts au lieu
// d'un PUT /api/prompts/{id} - un VRAI doublon du prompt est cree en base au lieu de mettre a jour
// l'enregistrement deja edite. Fix : _editingId reste sur l'echo serveur (pid) apres un update
// reussi, au lieu d'etre efface - le mode "mise a jour" persiste tant que l'utilisateur reste sur
// cette session d'edition.
// Execute : node tests/js/constructeur-prompts-doubleupdatesave.test.cjs (ou npm run test:js)
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
        toast: () => {},
    };
    global.navigator = global.navigator || {};
    global.navigator.clipboard = { writeText: () => Promise.resolve() };
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
    component.historyLoaded = true;
    component.isAuthenticated = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 (round 98) : 2e sauvegarde après une mise à jour réussie reste un PUT, jamais un POST ---
    {
        const calls = [];
        const fetchImpl = function (url, opts) {
            calls.push({ url: url, method: opts.method });
            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ public_id: 'prompt-1', id: 'prompt-1', prompt_text: 'Prompt modifié', name: 'Test', updated_at: '2026-07-27T10:00:00Z', params: {} }),
            });
        };
        const component = loadPromptBuilder(fetchImpl);
        component._editingId = 'prompt-1';
        component.history = [{ id: 'prompt-1', prompt: 'Prompt original', name: 'Test', date: '', params: {} }];
        component.saveName = 'Test';
        component.prompt = 'Prompt modifié';
        component.wizardParams = {};

        component.addToHistory();
        await flush();

        assert(calls.length === 1, 'round 98 : la 1re sauvegarde déclenche bien 1 requête');
        assert(calls[0].method === 'PUT' && calls[0].url === '/api/prompts/prompt-1', 'round 98 : la 1re sauvegarde (édition) est bien un PUT sur /api/prompts/prompt-1');
        assert(component._editingId === 'prompt-1', 'round 98 : _editingId reste à "prompt-1" après une mise à jour réussie (pas remis à null)');

        // 2e clic sur "Sauvegarder" sans recharger la page - simule le bug round 98.
        component.saving = false;
        component.prompt = 'Prompt modifié encore';
        component.addToHistory();
        await flush();

        assert(calls.length === 2, 'round 98 : la 2e sauvegarde déclenche bien 1 nouvelle requête (total 2)');
        assert(calls[1].method === 'PUT' && calls[1].url === '/api/prompts/prompt-1', 'round 98 : la 2e sauvegarde reste un PUT sur le MÊME prompt (pas un POST créant un doublon)');
        assert(component.history.length === 1, 'round 98 : aucun doublon n\'apparaît dans l\'historique côté client (toujours 1 seule entrée)');
    }

    // --- Test 2 (non-régression) : une sauvegarde SANS _editingId (nouveau prompt) reste un POST ---
    {
        const calls = [];
        const fetchImpl = function (url, opts) {
            calls.push({ url: url, method: opts.method });
            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ public_id: 'prompt-2', id: 'prompt-2', prompt_text: 'Nouveau prompt', name: 'Nouveau', created_at: '2026-07-27T10:00:00Z', params: {} }),
            });
        };
        const component = loadPromptBuilder(fetchImpl);
        component.history = [];
        component.saveName = 'Nouveau';
        component.prompt = 'Nouveau prompt';
        component.wizardParams = {};

        component.addToHistory();
        await flush();

        assert(calls.length === 1 && calls[0].method === 'POST' && calls[0].url === '/api/prompts', 'round 98 (non-régression) : une sauvegarde d\'un nouveau prompt (sans _editingId) reste un POST');
        assert(component._editingId === null, 'round 98 (non-régression) : _editingId reste null après la création d\'un nouveau prompt (comportement inchangé)');
        assert(component.history.length === 1, 'round 98 (non-régression) : le nouveau prompt est bien ajouté à l\'historique');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
