// tests/js/constructeur-prompts-promptprofile.test.cjs
// Garde-fou de non-regression (round 40, 2026-07-27) : "Mon profil" (/user/prompts) sauvegardait
// profile_role/profile_style/profile_constraints avec un message de succes annoncant que ca
// "pre-remplit vos futurs prompts", mais le wizard (constructeur-prompts-core.js) ne lisait
// JAMAIS cette cle - promesse UI non tenue. Le fix appelle GET /api/tool-preferences/constructeur-prompts
// dans init() (uniquement pour un NOUVEAU prompt, pas ?edit=ID) et pre-remplit personaCustom/
// constraintCustom SEULEMENT si ces champs sont encore vierges - ne doit jamais ecraser un choix
// deja fait par l'utilisateur (ex. reload d'un prompt en edition).
// Execute : node tests/js/constructeur-prompts-promptprofile.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(fetchImpl, opts) {
    opts = opts || {};
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
        location: { search: opts.search || '' },
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
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 : nouveau prompt (pas ?edit=ID) -> pre-remplit personaCustom + constraintCustom ---
    {
        const component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts') === 0) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
            }
            if (url === '/api/tool-preferences/constructeur-prompts') {
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({ preferences: { prompt_profile: {
                        profile_role: 'Enseignant de 5e annee',
                        profile_style: 'Simple et direct',
                        profile_constraints: 'Toujours donner un exemple concret',
                    } } }),
                });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });

        component.init();
        await flush();

        assert(component.personaType === 'custom', 'round 40 : personaType bascule sur \'custom\' quand un profile_role existe');
        assert(component.personaCustom === 'Enseignant de 5e annee', 'round 40 : personaCustom pre-rempli depuis profile_role');
        assert(component.constraintCustom.indexOf('Simple et direct') !== -1, 'round 40 : constraintCustom contient le style d\'ecriture prefere');
        assert(component.constraintCustom.indexOf('Toujours donner un exemple concret') !== -1, 'round 40 : constraintCustom contient les contraintes recurrentes');
    }

    // --- Test 2 : non-regression - aucun profil enregistre -> champs restent vierges ---
    {
        const component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts') === 0) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
            }
            if (url === '/api/tool-preferences/constructeur-prompts') {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: {} }) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });

        component.init();
        await flush();

        assert(component.personaType === 'preset', 'round 40 : sans profil enregistre, personaType reste \'preset\'');
        assert(component.personaCustom === '', 'round 40 : sans profil enregistre, personaCustom reste vide');
        assert(component.constraintCustom === '', 'round 40 : sans profil enregistre, constraintCustom reste vide');
    }

    // --- Test 3 : un prompt en edition (?edit=ID) n'est JAMAIS ecrase par le profil ---
    {
        const component = loadPromptBuilder(function (url) {
            if (url.indexOf('/api/prompts/') === 0) {
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({
                        public_id: 'abc123',
                        name: 'Mon prompt sauvegarde',
                        params: { personaType: 'custom', personaCustom: 'Choix explicite de l\'utilisateur' },
                    }),
                });
            }
            if (url.indexOf('/api/prompts') === 0) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) });
            }
            if (url === '/api/tool-preferences/constructeur-prompts') {
                // Ne doit meme pas etre appele en mode edition - si ca l'etait, ce mock renverrait
                // un profil qui ecraserait a tort le choix explicite ci-dessus.
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({ preferences: { prompt_profile: { profile_role: 'NE DOIT JAMAIS APPARAITRE' } } }),
                });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        }, { search: '?edit=abc123' });

        component.init();
        await flush();

        assert(component.personaCustom === 'Choix explicite de l\'utilisateur', 'round 40 : en mode edition (?edit=ID), le profil ne remplace jamais les valeurs du prompt charge');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
