// tests/js/constructeur-prompts-profilerace.test.cjs
// Garde-fou de non-regression (round 43, 2026-07-27) : course entre le pre-chargement asynchrone
// du profil utilisateur ("Mon profil") dans init() et la selection d'une carte d'objectif a l'etape
// 1 (selectTask()). La garde du pre-remplissage du profil ne verifiait que personaCustom==='' et
// personaType==='preset' - or selectTask() met AUSSI personaType='preset' (avec personaPreset
// rempli) quand la carte cliquee est associee a une persona preset. Si le fetch du profil resolvait
// APRES ce clic, il ecrasait silencieusement le choix explicite de l'utilisateur (personaType passe
// a 'custom', personaPreset reste orphelin non reinitialise). Le fix ajoute la condition
// personaPreset==='' a la garde.
// Execute : node tests/js/constructeur-prompts-profilerace.test.cjs (ou npm run test:js)
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
        location: { search: '' },
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
    // --- Test 1 : selectTask() (persona preset) AVANT résolution du profil -> ne doit PAS être écrasé ---
    {
        // ATTENTION : init() appelle DEUX fois /api/tool-preferences/constructeur-prompts - une fois
        // pour le pré-remplissage du profil (round 40) et une fois via _loadCustomCards() (round 41),
        // qui interroge le MÊME endpoint. Il faut distinguer le 1er appel (profil, qu'on garde en vol
        // volontairement) du 2e (cartes, qu'on résout immédiatement) sous peine de résoudre par erreur
        // la mauvaise promesse et de ne jamais exercer le code du round 43.
        let resolveProfile;
        let toolPrefsCallCount = 0;
        const component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts') === 0) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
            }
            if (url === '/api/tool-preferences/constructeur-prompts') {
                toolPrefsCallCount++;
                if (toolPrefsCallCount === 1) {
                    return new Promise(function (resolve) { resolveProfile = resolve; });
                }
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [] } }) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });

        component.init();
        await flush();

        component.selectTask({ id: 'redaction', personaValue: 'redacteur', verb: 'Rédiger' });
        await flush();

        assert(component.personaType === 'preset', 'round 43 : après selectTask(), personaType reste \'preset\' (avant résolution du profil)');
        assert(component.personaPreset === 'redacteur', 'round 43 : après selectTask(), personaPreset est bien \'redacteur\'');

        resolveProfile({ ok: true, json: () => Promise.resolve({ preferences: { prompt_profile: { profile_role: 'Enseignant de 5e année' } } }) });
        await flush();

        assert(component.personaType === 'preset', 'round 43 : le profil résolu APRÈS ne doit PAS écraser le choix explicite personaType');
        assert(component.personaPreset === 'redacteur', 'round 43 : personaPreset reste \'redacteur\' - pas orphelin, pas écrasé');
        assert(component.personaCustom === '', 'round 43 : personaCustom reste vide - le profil n\'a pas pris le dessus sur le choix explicite');
    }

    // --- Test 2 : non-régression - sans selectTask() préalable, le profil se pré-remplit normalement ---
    {
        const component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts') === 0) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
            }
            if (url === '/api/tool-preferences/constructeur-prompts') {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { prompt_profile: { profile_role: 'Enseignant de 5e année' } } }) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });

        component.init();
        await flush();

        assert(component.personaType === 'custom', 'round 43 : sans choix explicite préalable, le profil se pré-remplit toujours (non-régression round 40)');
        assert(component.personaCustom === 'Enseignant de 5e année', 'round 43 : personaCustom bien pré-rempli depuis le profil (non-régression round 40)');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
