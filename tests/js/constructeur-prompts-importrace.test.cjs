// tests/js/constructeur-prompts-importrace.test.cjs
// Garde-fou de non-regression (round 62, 2026-07-27) : importLocalCustomCards() appliquait l'echo
// serveur (`self.customCards = data.preferences.custom_cards`) SANS aucune verification de
// fraicheur, contrairement a persistCustomCards() qui compare un snapshot avant/apres depuis le
// round 61. Scenario reel : les boutons "Importer mes cartes locales" et "Ajouter une carte" sont
// tous deux visibles quand un compte vient de se connecter avec des cartes locales invite en
// attente et 0 carte serveur. Si l'utilisateur clique "Importer" PUIS "Ajouter une carte" (mutation
// SYNCHRONE et immediate de customCards) PENDANT que le POST d'import est en vol, la reponse de
// l'import remplacait integralement customCards par l'echo (qui ne contient jamais la carte
// ajoutee, calculee AVANT) - la carte fraichement creee disparaissait silencieusement de l'ecran,
// puis le persistCustomCards() suivant (deja en file d'attente, round 37) re-persistait cet etat
// deja ampute cote serveur : perte definitive, sans erreur ni toast d'avertissement. Le fix compare
// un snapshot JSON de customCards pris au moment de l'ENVOI de l'import a l'etat courant au moment
// de la REPONSE : l'echo serveur n'est applique QUE si rien n'a change localement entre-temps (le
// reste du traitement - troncature/localStorage/toast - continue dans tous les cas, l'import ayant
// reellement reussi cote serveur).
// Execute : node tests/js/constructeur-prompts-importrace.test.cjs (ou npm run test:js)
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
    component.customCardsLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 (round 62) : addCustomCard() pendant un import en vol -> la carte ajoutée survit ---
    {
        const calls = [];
        let resolveImport;
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            if (calls.length === 1) {
                return new Promise(function (resolve) { resolveImport = resolve; });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: body.value } }) });
        });
        component._localCardsToImport = [{ id: 'local1', title: 'Carte importee', icon: '⭐', query_template: '', hidden: false }];

        component.importLocalCustomCards();
        await flush();
        assert(calls.length === 1, 'round 62 : importLocalCustomCards() déclenche bien un POST');
        assert(calls[0].value.length === 1, 'round 62 : le POST d\'import envoie bien 1 carte (la locale seule, avant tout ajout concurrent)');

        // Simule le clic "Ajouter une carte" PENDANT que le POST d'import est en vol (mutation
        // synchrone immédiate, comme le vrai addCustomCard()). customCards est encore [] à ce
        // stade (le .then() de l'import n'a pas encore résolu), donc la longueur passe à 1.
        component.customCards.push({ id: 'new1', title: 'Nouvelle carte', icon: '⭐', query_template: '', hidden: false });
        assert(component.customCards.length === 1, 'round 62 : la carte ajoutée est bien visible immédiatement (état local)');

        // Le serveur répond avec l'écho de l'import seul (envoyé AVANT l'ajout).
        resolveImport({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: calls[0].value } }) });
        await flush();

        assert(component.customCards.length === 1, 'round 62 : la carte ajoutée pendant le vol N\'A PAS été écrasée par l\'écho de l\'import');
        assert(component.customCards.some(function (c) { return c.id === 'new1'; }), 'round 62 : la carte "Nouvelle carte" est toujours présente après la réponse de l\'import');
    }

    // --- Test 2 (round 62) : non-régression - import isolé (sans mutation concurrente) fonctionne toujours ---
    {
        const calls = [];
        const component = loadPromptBuilder(function (url, opts) {
            const body = JSON.parse(opts.body);
            calls.push(body);
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: body.value } }) });
        });
        component._localCardsToImport = [{ id: 'local1', title: 'Carte importee', icon: '⭐', query_template: '', hidden: false }];

        component.importLocalCustomCards();
        await flush();

        assert(component.customCards.length === 1, 'round 62 : sans mutation concurrente, l\'écho serveur de l\'import est bien appliqué');
        assert(component.customCards[0].id === 'local1', 'round 62 : la carte importée est bien celle attendue');
        assert(component.customCardsImportAvailable === false, 'round 62 : la bannière d\'import est bien masquée après un import complet sans troncature');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
