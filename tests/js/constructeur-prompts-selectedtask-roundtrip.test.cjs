// tests/js/constructeur-prompts-selectedtask-roundtrip.test.cjs
// Garde-fou de non-regression (round 101, 2026-07-27, passe adversariale) : selectedTask (id de la
// carte d'objectif choisie a l'etape 1) n'etait jamais inclus dans wizardParams(), donc jamais
// persiste en base ni restaure a la reouverture d'un prompt sauvegarde (?edit=ID). Resultat : pour
// TOUT prompt existant rouvert en edition, le badge « Objectif choisi » et la mise en surbrillance
// de la carte a l'etape 1 affichaient systematiquement « Autre chose », quelle que soit la carte
// reellement utilisee a la creation. Fix : selectedTask ajoute a wizardParams() (sauvegarde) et
// restaure depuis p.selectedTask AVANT le repli 'autre' dans le bloc ?edit=ID de init().
// Execute : node tests/js/constructeur-prompts-selectedtask-roundtrip.test.cjs (ou npm run test:js)
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
    // --- Test 1 (round 101) : wizardParams inclut bien selectedTask ---
    {
        const component = loadPromptBuilder(function () { return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) }); }, null);
        component.selectedTask = 'redaction';

        assert(component.wizardParams.selectedTask === 'redaction', 'round 101 : wizardParams() inclut désormais selectedTask (la carte d\'objectif choisie)');
    }

    // --- Test 2 (round 101) : ?edit=ID restaure la vraie carte d'objectif, pas le repli 'autre' ---
    {
        const component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts/42') === 0) {
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({
                        public_id: '42',
                        name: 'Ancien prompt',
                        params: { selectedTask: 'redaction', personaType: 'preset', personaPreset: 'redacteur_web', verbType: 'preset', verb: 'Rédige', taskObject: 'un courriel professionnel' },
                    }),
                });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
        }, '42');

        component.init();
        await flush();

        assert(component.selectedTask === 'redaction', 'round 101 : la restauration ?edit=ID assigne bien selectedTask depuis params.selectedTask (pas de repli sur \'autre\')');
    }

    // --- Test 3 (non-régression) : un prompt sauvegardé AVANT ce fix (sans selectedTask en base) retombe toujours sur 'autre' ---
    {
        const component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts/99') === 0) {
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({
                        public_id: '99',
                        name: 'Vieux prompt (pré-round-101)',
                        params: { personaType: 'preset', personaPreset: 'analyste', verbType: 'preset', verb: 'Résume', taskObject: 'ce rapport' },
                    }),
                });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
        }, '99');

        component.init();
        await flush();

        assert(component.selectedTask === 'autre', 'round 101 (non-régression) : un prompt legacy sans selectedTask en base retombe toujours sur le repli \'autre\' (comportement round 64 inchangé)');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
