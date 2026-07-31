// tests/js/constructeur-prompts-importtruncation.test.cjs
// Garde-fou de non-regression (round 49, 2026-07-27) : importLocalCustomCards() affichait un toast
// de succes base sur local.length (nombre de cartes locales A IMPORTER), jamais le nombre REELLEMENT
// conserve apres troncature au plafond de 10 cartes (merged.slice(0, 10)). Si customCards n'est pas
// vide au moment de l'import (carte ajoutee en attente dans _cardsPersistQueue, round 38), certaines
// cartes locales etaient tronquees ET localStorage etait vide inconditionnellement - perte
// irrecuperable avec un message affirmant un succes complet incorrect. Le fix calcule le nombre reel
// importe AVANT l'appel reseau, garde en attente (localStorage + _localCardsToImport) les cartes
// tronquees au lieu de les effacer, et affiche un message honnete (warning) en cas de troncature.
// Execute : node tests/js/constructeur-prompts-importtruncation.test.cjs (ou npm run test:js)
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
    const toastCalls = [];
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
        dispatchEvent: () => {},
        toast: function (msg, type, duration) { toastCalls.push({ msg: msg, type: type, duration: duration }); },
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
    component._toastCalls = toastCalls;
    return component;
}

function makeCard(id) {
    return { id: id, title: 'Carte ' + id, icon: '⭐', query_template: '', hidden: false };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

const okFetch = function (url, opts) {
    const body = JSON.parse(opts.body);
    return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: body.value } }) });
};

(async function run() {
    // --- Test 1 : import SANS troncature (8 existantes + 2 locales = 10) -> message exact, localStorage vidé ---
    {
        const component = loadPromptBuilder(okFetch);
        for (let i = 1; i <= 8; i++) component.customCards.push(makeCard('existing' + i));
        component._localCardsToImport = [makeCard('local1'), makeCard('local2')];
        component.customCardsImportAvailable = true;
        // Round 54 : cp_custom_cards est TOUJOURS stocké sous la forme enveloppée
        // {version:1, cards:[...]} en production (_saveLocalCustomCards/_readLocalCustomCards) -
        // jamais un tableau brut. Seeder avec la forme réelle pour ne pas masquer de régression.
        localStorage.setItem('cp_custom_cards', JSON.stringify({ version: 1, cards: component._localCardsToImport }));

        component.importLocalCustomCards();
        await flush();

        assert(component.customCards.length === 10, 'round 49 : les 10 cartes (8 existantes + 2 importées) sont bien conservées, aucune troncature');
        assert(component._toastCalls.length === 1, 'round 49 : un seul toast affiché');
        assert(component._toastCalls[0].type === 'success', 'round 49 : type de toast "success" quand aucune troncature');
        assert(component._toastCalls[0].msg.indexOf('2') !== -1, 'round 49 : le message annonce bien 2 cartes importées (pas de troncature)');
        assert(localStorage.getItem('cp_custom_cards') === null, 'round 49 : localStorage vidé quand tout a été importé sans perte');
        assert(component.customCardsImportAvailable === false, 'round 49 : bannière d\'import masquée, plus rien en attente');
    }

    // --- Test 2 : import AVEC troncature (9 existantes + 3 locales = 12, plafond 10) -> message honnête, pas de perte silencieuse ---
    {
        const component = loadPromptBuilder(okFetch);
        for (let i = 1; i <= 9; i++) component.customCards.push(makeCard('existing' + i));
        const local = [makeCard('local1'), makeCard('local2'), makeCard('local3')];
        component._localCardsToImport = local;
        component.customCardsImportAvailable = true;
        localStorage.setItem('cp_custom_cards', JSON.stringify({ version: 1, cards: local }));

        component.importLocalCustomCards();
        await flush();

        assert(component.customCards.length === 10, 'round 49 : le tableau final ne dépasse jamais le plafond de 10');
        assert(component._toastCalls.length === 1, 'round 49 : un seul toast affiché même en cas de troncature');
        assert(component._toastCalls[0].type === 'warning', 'round 49 : type de toast "warning" (pas "success") quand une troncature a eu lieu - message honnête');
        assert(component._toastCalls[0].msg.indexOf('1') !== -1, 'round 49 : le message reflète le nombre RÉEL importé (1 seule des 3 locales avait de la place)');

        // Round 49 : la carte tronquée ne doit JAMAIS disparaître silencieusement - elle doit rester
        // récupérable (en attente d'un futur import), ni dans le tableau final ni perdue.
        assert(component._localCardsToImport.length === 2, 'round 49 : les 2 cartes locales non importées restent en attente (_localCardsToImport), pas perdues');
        assert(component.customCardsImportAvailable === true, 'round 49 : la bannière d\'import reste visible tant qu\'il reste des cartes en attente');
        // Round 54 : cp_custom_cards est stocké sous la forme enveloppée {version:1, cards:[...]} -
        // lire .cards, pas le JSON brut (piège qui masquait le vrai bug round 54 dans ce test).
        const remainingWrapped = JSON.parse(localStorage.getItem('cp_custom_cards') || '{"cards":[]}');
        assert(Array.isArray(remainingWrapped.cards) && remainingWrapped.cards.length === 2, 'round 49/54 : localStorage (forme enveloppée réelle) conserve les 2 cartes tronquées (pas de perte irrécupérable) au lieu d\'être vidé inconditionnellement');
    }

    // --- Test 3 : import où AUCUNE carte locale ne trouve de place (10 existantes déjà pleines) ---
    {
        const component = loadPromptBuilder(okFetch);
        for (let i = 1; i <= 10; i++) component.customCards.push(makeCard('existing' + i));
        const local = [makeCard('local1')];
        component._localCardsToImport = local;
        component.customCardsImportAvailable = true;
        localStorage.setItem('cp_custom_cards', JSON.stringify({ version: 1, cards: local }));

        component.importLocalCustomCards();
        await flush();

        assert(component._toastCalls[0].type === 'warning', 'round 49 : type "warning" quand 0 carte importée (plafond déjà atteint)');
        assert(component._localCardsToImport.length === 1, 'round 49 : la carte locale reste intégralement en attente si rien n\'a pu être importé');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
