// tests/js/constructeur-prompts-doubleimportcards.test.cjs
// Garde-fou de non-regression (round 97, 2026-07-27, passe adversariale) : importLocalCustomCards()
// (import des cartes personnalisees invite -> compte) n'avait AUCUNE garde de re-entrance,
// contrairement a importLocalStorage() (garde `this.importing`, round 36). Un double-clic sur le
// bouton "Importer mes X cartes locales" declenchait 2 appels concurrents : le 2e capturait la
// meme liste `local` (fermeture non rafraichie avant resolution du 1er) et la fusionnait a nouveau
// avec l'echo serveur du 1er, creant de VRAIS doublons persistes en base (id different, contenu
// identique - sanitizeCustomCards() genere un nouvel id sur collision au lieu de rejeter). Fix :
// flag `importingCards`, teste/mis a true en tete de importLocalCustomCards() (retour anticipe si
// deja true), remis a false a la resolution (succes ou echec) - meme pattern que `importing`.
// Execute : node tests/js/constructeur-prompts-doubleimportcards.test.cjs (ou npm run test:js)
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
    component.customCardsLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 (round 97) : double-clic synchrone n'envoie qu'UNE seule requête réseau ---
    {
        let fetchCallCount = 0;
        let lastMergedLength = null;
        const fetchImpl = function (url, opts) {
            if (url === '/api/tool-preferences/constructeur-prompts' && opts && opts.method === 'POST') {
                fetchCallCount++;
                const body = JSON.parse(opts.body);
                if (body.key === 'custom_cards') lastMergedLength = body.value.length;
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [] } }) });
        };
        const component = loadPromptBuilder(fetchImpl);
        component.customCards = [];
        component._localCardsToImport = [
            { id: 'local-1', title: 'Carte locale 1', icon: '⭐', query_template: 'A', hidden: false },
            { id: 'local-2', title: 'Carte locale 2', icon: '⭐', query_template: 'B', hidden: false },
        ];

        assert(component.importingCards === false, 'round 97 : importingCards initialisé à false');

        // Double-clic synchrone (avant toute résolution réseau).
        component.importLocalCustomCards();
        assert(component.importingCards === true, 'round 97 : importingCards passe à true dès le 1er appel');
        component.importLocalCustomCards();

        await flush();

        assert(fetchCallCount === 1, 'round 97 : un double-clic synchrone ne déclenche qu\'UNE seule requête réseau (pas de doublon)');
        assert(lastMergedLength === 2, 'round 97 : le payload envoyé contient exactement les 2 cartes locales, pas 4 (pas de fusion en double)');
        assert(component.importingCards === false, 'round 97 : importingCards revient à false après résolution');
    }

    // --- Test 2 (non-régression) : un import normal (1 seul appel) fonctionne toujours ---
    {
        let fetchCallCount = 0;
        const fetchImpl = function (url, opts) {
            if (url === '/api/tool-preferences/constructeur-prompts' && opts && opts.method === 'POST') {
                fetchCallCount++;
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [{ id: 'local-1', title: 'Carte locale 1', icon: '⭐', query_template: 'A', hidden: false }] } }) });
        };
        const component = loadPromptBuilder(fetchImpl);
        component.customCards = [];
        component._localCardsToImport = [{ id: 'local-1', title: 'Carte locale 1', icon: '⭐', query_template: 'A', hidden: false }];

        component.importLocalCustomCards();
        await flush();

        assert(fetchCallCount === 1, 'round 97 (non-régression) : un import simple (1 clic) déclenche bien 1 requête');
        assert(component.importingCards === false, 'round 97 (non-régression) : importingCards retombe à false après un import simple réussi');
    }

    // --- Test 3 (round 97) : un 2e appel APRÈS résolution du 1er est bien autorisé (pas de blocage permanent) ---
    {
        let fetchCallCount = 0;
        const fetchImpl = function () {
            fetchCallCount++;
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [] } }) });
        };
        const component = loadPromptBuilder(fetchImpl);
        component.customCards = [];
        component._localCardsToImport = [{ id: 'local-1', title: 'Carte locale 1', icon: '⭐', query_template: 'A', hidden: false }];

        component.importLocalCustomCards();
        await flush();
        assert(component.importingCards === false, 'round 97 : le flag est bien remis à false après le 1er import');

        component._localCardsToImport = [{ id: 'local-2', title: 'Carte locale 2', icon: '⭐', query_template: 'B', hidden: false }];
        component.importLocalCustomCards();
        await flush();

        assert(fetchCallCount === 2, 'round 97 : un 2e import, APRÈS résolution du 1er, n\'est pas bloqué (2 requêtes distinctes au total)');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
