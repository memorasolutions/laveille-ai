// tests/js/constructeur-prompts-editrace.test.cjs
// Garde-fou de non-regression (round 64, 2026-07-27) : le bloc ?edit=ID de init() (chargement
// d'un prompt existant via GET /api/prompts/{id}) n'avait AUCUNE garde contre une mutation
// utilisateur concurrente - un QUATRIEME site touche par la meme classe de bug que rounds
// 41/61/62/63 (etat charge de facon async au demarrage vs mutation optimiste avant resolution),
// mais plus grave : selectTask() restait pleinement interactif (etape 1) pendant que ce fetch
// etait en vol, et sa reponse tardive ecrasait silencieusement personaType/personaPreset/
// verbType/verb/taskObject/... avec les valeurs de l'ANCIEN prompt - SAUF selectedTask, qui
// restait celui du clic utilisateur (self.selectedTask = self.selectedTask || 'autre').
// Resultat : le badge « Objectif choisi » affichait la carte fraichement cliquee, mais les
// champs reels du formulaire (et donc le prompt genere) revenaient silencieusement a l'ancien
// prompt edite. Le fix introduit editLoading (meme pattern que historyLoaded/customCardsLoaded) :
// vrai des le demarrage si ?edit=ID est present (avant meme que init() ne lance le fetch),
// bloque selectTask() (no-op silencieux) et desactive les 2 boutons de carte en blade tant que
// le chargement n'a pas resolu (succes ou echec).
// Execute : node tests/js/constructeur-prompts-editrace.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(fetchImpl, editIdParam) {
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
        location: { search: editIdParam ? ('?edit=' + editIdParam) : '' },
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
    component.historyLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 (round 64) : editLoading vrai dès le démarrage si ?edit=ID est présent ---
    {
        const component = loadPromptBuilder(function () { return new Promise(function () {}); }, '42');
        assert(component.editLoading === true, 'round 64 : editLoading démarre à true dès que ?edit=ID est dans l\'URL (avant même init())');
    }
    {
        const component = loadPromptBuilder(function () { return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) }); }, null);
        assert(component.editLoading === false, 'round 64 : editLoading démarre à false sans ?edit=ID');
    }

    // --- Test 2 (round 64) : clic sur une carte PENDANT le chargement ?edit=ID -> no-op, pas d'écrasement tardif ---
    {
        var resolveEdit;
        var component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts/42') === 0) {
                return new Promise(function (resolve) { resolveEdit = resolve; });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
        }, '42');

        component.init();
        await flush();
        assert(component.editLoading === true, 'round 64 : editLoading reste true pendant que le GET /api/prompts/42 est en vol');
        assert(component.step === 1, 'round 64 : le wizard reste à l\'étape 1 tant que le prompt existant n\'a pas fini de charger');

        // Tentative de clic sur une carte d'objectif PENDANT le chargement (round 64 : bug d'origine).
        component.selectTask({ id: 'redaction', personaValue: 'redacteur_web', verb: 'Rédige', query_template: 'un courriel professionnel...' });
        await flush();

        assert(component.selectedTask === '', 'round 64 : selectTask() est un no-op tant que editLoading=true (aucune mutation)');
        assert(component.step === 1, 'round 64 : le wizard n\'avance PAS à l\'étape 2 suite au clic bloqué');

        // Le GET /api/prompts/42 résout enfin avec l'ANCIEN prompt (persona=analyste, verb=Résume).
        resolveEdit({
            ok: true,
            json: () => Promise.resolve({
                public_id: '42',
                name: 'Ancien prompt',
                params: { personaType: 'preset', personaPreset: 'analyste', verbType: 'preset', verb: 'Résume', taskObject: 'ce rapport financier trimestriel de 40 pages' },
            }),
        });
        await flush();

        assert(component.editLoading === false, 'round 64 : editLoading repasse à false une fois le chargement résolu');
        assert(component.step === 2, 'round 64 : le wizard avance bien à l\'étape 2 une fois le prompt existant chargé');
        assert(component.personaPreset === 'analyste', 'round 64 : les champs de l\'ancien prompt sont bien appliqués (aucun clic n\'a interféré, il était bloqué)');
    }

    // --- Test 3 (round 64) : non-régression - clic APRÈS résolution fonctionne normalement ---
    {
        var component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts/42') === 0) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ public_id: '42', name: 'Ancien prompt', params: {} }) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
        }, '42');

        component.init();
        await flush();
        assert(component.editLoading === false, 'round 64 (non-régression) : editLoading passe bien à false après résolution normale');

        component.selectTask({ id: 'redaction', personaValue: 'redacteur_web', verb: 'Rédige' });
        assert(component.selectedTask === 'redaction', 'round 64 (non-régression) : selectTask() fonctionne normalement une fois editLoading=false');
    }

    // --- Test 4 (round 64) : échec du chargement ?edit=ID débloque aussi editLoading ---
    {
        var component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts/999') === 0) {
                return Promise.resolve({ ok: false, status: 404 });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
        }, '999');

        component.init();
        await flush();
        assert(component.editLoading === false, 'round 64 : un échec du chargement ?edit=ID (404/réseau) débloque aussi editLoading (pas de blocage permanent)');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
