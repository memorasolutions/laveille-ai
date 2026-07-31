// tests/js/constructeur-prompts-cardsqueue.test.cjs
// Garde-fou de non-regression (round 37, 2026-07-27) : persistCustomCards() n'etait pas serialise
// - 2 mutations rapprochees des cartes de demarrage personnalisees (Option D) declenchaient 2 POST
// /api/tool-preferences/constructeur-prompts concurrents. Le serveur fait un lecture-modification-
// ecriture non atomique (ToolPreferenceController::update()) sur la colonne JSON tool_preferences :
// si la reponse de la 2e requete etait traitee apres la 1re alors que la 1re avait ete envoyee avec
// un etat plus ancien, la mutation de la 1re requete pouvait etre silencieusement ecrasee (perte de
// donnee reelle, pas juste un doublon visible comme aux rounds 34-36). Le fix serialise tous les
// appels a persistCustomCards() sur une file d'attente de promesses (_cardsPersistQueue) : un seul
// POST en vol a la fois, chacun capturant l'etat customCards le plus recent au moment de son envoi.
// Execute : node tests/js/constructeur-prompts-cardsqueue.test.cjs (ou npm run test:js)
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
    component.$nextTick = function (cb) { cb(); };
    // Round 41 (2026-07-27) : addCustomCard() exige désormais customCardsLoaded=true (garde contre
    // la course avec _loadCustomCards()) - ces tests exercent directement persistCustomCards() sans
    // passer par init(), donc on simule un chargement déjà terminé.
    component.customCardsLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 : 2 addCustomCard() rapprochés -> les 2 POST partent en SÉRIE, jamais concurrents ---
    {
        const calls = [];
        let resolveFirst;
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            if (calls.length === 1) {
                return new Promise(function (resolve) { resolveFirst = resolve; });
            }
            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ preferences: { custom_cards: body.value } }),
            });
        });

        component.addCustomCard();
        component.addCustomCard();
        await flush();

        assert(calls.length === 1, 'round 37 : le 2e POST n\'est PAS envoyé tant que le 1er est en vol (sérialisation)');

        resolveFirst({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: calls[0].value } }) });
        await flush();

        assert(calls.length === 2, 'round 37 : le 2e POST part bien une fois le 1er résolu');
        assert(calls[1].value.length === 2, 'round 37 : le 2e POST envoie l\'état CUMULÉ (2 cartes), aucune mutation perdue');
    }

    // --- Test 2 : non-régression - une seule mutation continue de fonctionner normalement ---
    {
        const calls = [];
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: body.value } }) });
        });

        component.addCustomCard();
        await flush();

        assert(calls.length === 1, 'round 37 : une mutation isolée envoie toujours un seul POST');
        assert(component.customCards.length === 1, 'la carte ajoutée est bien reflétée dans l\'état local');
    }

    // --- Test 3 (round 38) : addCustomCard() + importLocalCustomCards() concurrents -> SÉRIALISÉS
    // et FUSIONNÉS (la carte ajoutée ET les cartes importées survivent toutes deux) ---
    {
        const calls = [];
        let resolveFirst;
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            if (calls.length === 1) {
                return new Promise(function (resolve) { resolveFirst = resolve; });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: body.value } }) });
        });
        component._localCardsToImport = [{ id: 'local1', title: 'Carte locale', icon: '⭐', query_template: '', hidden: false }];

        component.addCustomCard();
        component.importLocalCustomCards();
        await flush();

        assert(calls.length === 1, 'round 38 : importLocalCustomCards() n\'envoie PAS son POST tant que celui d\'addCustomCard() est en vol (sérialisation)');

        resolveFirst({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: calls[0].value } }) });
        await flush();

        assert(calls.length === 2, 'round 38 : le POST d\'import part bien une fois celui d\'ajout résolu');
        assert(calls[1].value.length === 2, 'round 38 : le POST d\'import envoie l\'état FUSIONNÉ (carte ajoutée + carte importée), aucune perte');
        assert(calls[1].value.some(function (c) { return c.id === 'local1'; }), 'round 38 : la carte importée est bien présente dans le payload fusionné');
    }

    // --- Test 4 (round 38) : non-régression - un import isolé (sans mutation concurrente) fonctionne toujours ---
    {
        const calls = [];
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: body.value } }) });
        });
        component._localCardsToImport = [{ id: 'local1', title: 'Carte locale', icon: '⭐', query_template: '', hidden: false }];

        component.importLocalCustomCards();
        await flush();

        assert(calls.length === 1, 'round 38 : un import isolé envoie toujours un seul POST');
        assert(component.customCards.length === 1, 'la carte importée est bien reflétée dans l\'état local');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
