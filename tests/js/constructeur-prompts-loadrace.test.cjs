// tests/js/constructeur-prompts-loadrace.test.cjs
// Garde-fou de non-regression (round 41, 2026-07-27) : _loadCustomCards() charge les cartes
// personnalisees deja sauvegardees cote serveur de facon asynchrone, fire-and-forget, sans aucun
// flag de chargement. Si addCustomCard() etait declenche AVANT que ce GET ne resolve (ou si le GET
// echouait silencieusement), customCards restait a [] (sa valeur initiale) - la nouvelle carte
// etait poussee dans ce tableau vide, et persistCustomCards() envoyait ce tableau en REMPLACEMENT
// COMPLET cote serveur (necessaire pour que la suppression de carte fonctionne, donc pas une
// fusion), ecrasant silencieusement toutes les cartes personnalisees deja sauvegardees. Le fix
// ajoute un flag customCardsLoaded (false par defaut, mis a true une fois le GET resolu ou en
// echec) et bloque addCustomCard() tant que ce flag est false (bouton aussi desactive en Blade).
// Execute : node tests/js/constructeur-prompts-loadrace.test.cjs (ou npm run test:js)
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
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 : addCustomCard() AVANT résolution du GET initial -> refusé, aucune perte ---
    {
        const calls = [];
        let resolveGet;
        const component = loadPromptBuilder(function (url, opts) {
            if (!opts || !opts.method) {
                // GET de chargement initial (_loadCustomCards) - reste en vol volontairement.
                return new Promise(function (resolve) { resolveGet = resolve; });
            }
            calls.push(JSON.parse(opts.body));
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: JSON.parse(opts.body).value } }) });
        });

        component.init();
        await flush();

        assert(component.customCardsLoaded === false, 'round 41 : customCardsLoaded reste false tant que le GET initial est en vol');

        component.addCustomCard();
        await flush();

        assert(calls.length === 0, 'round 41 : addCustomCard() est REFUSÉ tant que le chargement initial n\'est pas terminé (aucun POST envoyé)');
        assert(component.customCards.length === 0, 'round 41 : aucune carte locale ajoutée tant que le chargement est en cours');

        // Le GET resout enfin avec 3 cartes déjà sauvegardées côté serveur.
        resolveGet({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [
            { id: 'a', title: 'Carte A', icon: '⭐', query_template: '', hidden: false },
            { id: 'b', title: 'Carte B', icon: '⭐', query_template: '', hidden: false },
            { id: 'c', title: 'Carte C', icon: '⭐', query_template: '', hidden: false },
        ] } }) });
        await flush();

        assert(component.customCardsLoaded === true, 'round 41 : customCardsLoaded passe à true une fois le GET résolu');
        assert(component.customCards.length === 3, 'round 41 : les 3 cartes déjà sauvegardées sont bien chargées après résolution');

        component.addCustomCard();
        await flush();

        assert(calls.length === 1, 'round 41 : addCustomCard() fonctionne normalement une fois le chargement terminé');
        assert(calls[0].value.length === 4, 'round 41 : le POST envoie les 3 cartes existantes + la nouvelle (aucune perte)');
    }

    // --- Test 2 : le GET initial échoue -> le garde-fou TIENT, aucune écriture destructrice ---
    //
    // Round 118 (2026-07-27, passe adversariale) : ce test affirmait l'inverse - « un échec réseau
    // débloque quand même customCardsLoaded (pas de bouton figé) ». Le round 41 voulait éviter une
    // interface morte, intention légitime, mais ce déblocage ouvrait une PERTE DE DONNÉES réelle :
    // customCards retombait à [] et la première carte ajoutée ensuite était POSTée seule, or le
    // serveur REMPLACE la clé custom_cards (array_merge par clé, pas une fusion élément par
    // élément) - toutes les cartes déjà enregistrées étaient écrasées.
    //
    // Le round 118 tranche : bloquer vaut mieux qu'écraser. L'intention du round 41 reste honorée
    // autrement - l'interface n'est plus « figée sans recours » mais explicitement expliquée
    // (avertissement persistant role="alert") et réparable (bouton « Réessayer »), ce que le test
    // de reprise ci-dessous vérifie réellement.
    {
        const calls = [];
        let failGet = true;
        const component = loadPromptBuilder(function (url, opts) {
            if (!opts || !opts.method) {
                if (failGet) { return Promise.reject(new Error('network down')); }
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [
                    { id: 'a', title: 'Carte A', icon: '⭐', query_template: '', hidden: false },
                    { id: 'b', title: 'Carte B', icon: '⭐', query_template: '', hidden: false },
                ] } }) });
            }
            calls.push(JSON.parse(opts.body));
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });

        component.init();
        await flush();

        assert(component.customCardsLoaded === false, 'round 118 : un échec du GET initial laisse customCardsLoaded à false (le garde-fou tient)');
        assert(component.customCardsLoadFailed === true, 'round 118 : l\'échec est signalé (customCardsLoadFailed) au lieu d\'être silencieux');

        component.addCustomCard();
        await flush();

        assert(calls.length === 0, 'round 118 : aucune écriture envoyée après un échec de chargement (pas d\'écrasement des cartes serveur)');

        // Reprise réelle : le réseau revient, l'utilisateur clique « Réessayer ».
        failGet = false;
        component.retryLoadCustomCards();
        await flush();

        assert(component.customCardsLoadFailed === false, 'round 118 : le réessai efface l\'état d\'erreur');
        assert(component.customCardsLoaded === true, 'round 118 : le réessai débloque réellement l\'ajout');
        assert(component.customCards.length === 2, 'round 118 : le réessai récupère bien les cartes serveur (aucune perte)');
    }

    // --- Test 3 : non-régression - invité (non authentifié), chargement synchrone, jamais bloqué ---
    {
        const component = loadPromptBuilder(function () {
            return Promise.reject(new Error('ne devrait jamais être appelé pour un invité'));
        });
        component.isAuthenticated = false;

        component.init();
        await flush();

        assert(component.customCardsLoaded === true, 'round 41 : un invité (chargement localStorage synchrone) a customCardsLoaded=true immédiatement');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
