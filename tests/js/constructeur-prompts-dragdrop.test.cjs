// tests/js/constructeur-prompts-dragdrop.test.cjs
// Garde-fou de non-regression (round 53, 2026-07-27) : l'ancienne implementation gardait l'id de
// la carte glissee dans un etat JS mutable (_draggedCardId). Un self-drop (carte deposee sur
// elle-meme) ou un drag annule ne le reinitialisait JAMAIS (early-return avant le reset, aucun
// handler dragend). N'IMPORTE QUEL drag-and-drop natif SANS RAPPORT depose plus tard sur une
// carte (texte selectionne sur la page, image, lien) declenchait alors un reordonnancement
// silencieux du tableau customCards base sur cet id perime, persiste immediatement au serveur.
// Le fix elimine tout etat JS entre le debut et la fin du drag : dataTransfer (avec un type MIME
// propre a l'app, jamais pose par un drag natif non lie) est desormais la SEULE source de verite,
// lue directement dans dropOnCustomCard() - donc un drag etranger (sans ce type MIME) est ignore
// d'office, et il n'y a plus de variable a "oublier" de reinitialiser.
// Execute : node tests/js/constructeur-prompts-dragdrop.test.cjs (ou npm run test:js)
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
    component.customCardsLoaded = true;
    return component;
}

function makeCard(id) {
    return { id: id, title: 'Carte ' + id, icon: '⭐', query_template: '', hidden: false };
}

// Simule un vrai HTML5 DataTransfer minimal (store clé/valeur, types() dérivé des clés définies).
function makeDataTransfer() {
    const store = {};
    return {
        setData(type, value) { store[type] = String(value); },
        getData(type) { return Object.prototype.hasOwnProperty.call(store, type) ? store[type] : ''; },
        get types() { return Object.keys(store); },
    };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

const noFetch = function () { return Promise.reject(new Error('persistCustomCards should not be reachable in these tests without an explicit fetch stub')); };

(async function run() {
    // --- Test 1 : self-drop (carte déposée sur elle-même) ne doit rien réordonner ni persister ---
    {
        let persistCalls = 0;
        const okFetch = function () { persistCalls++; return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [] } }) }); };
        const component = loadPromptBuilder(okFetch);
        const a = makeCard('a'), b = makeCard('b'), c = makeCard('c');
        component.customCards.push(a, b, c);

        const dt = makeDataTransfer();
        component.dragStartCustomCard({ dataTransfer: dt }, a);
        component.dropOnCustomCard({ preventDefault: () => {}, dataTransfer: dt }, a);

        assert(component.customCards.map(x => x.id).join(',') === 'a,b,c', 'round 53 : self-drop ne change pas l\'ordre des cartes');
        assert(persistCalls === 0, 'round 53 : self-drop ne déclenche aucun appel réseau (aucun réordonnancement réel)');
    }

    // --- Test 2 (LE bug round 53) : un drop natif SANS RAPPORT (sans passer par dragStartCustomCard)
    //     sur une carte ne doit JAMAIS réordonner ni persister, même après un self-drop précédent ---
    {
        let persistCalls = 0;
        const okFetch = function () { persistCalls++; return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [] } }) }); };
        const component = loadPromptBuilder(okFetch);
        const a = makeCard('a'), b = makeCard('b'), c = makeCard('c');
        component.customCards.push(a, b, c);

        // 1) self-drop sur A (comme test 1) - avec l'ancien bug, ceci laissait _draggedCardId = 'a' périmé.
        const dtSelf = makeDataTransfer();
        component.dragStartCustomCard({ dataTransfer: dtSelf }, a);
        component.dropOnCustomCard({ preventDefault: () => {}, dataTransfer: dtSelf }, a);

        // 2) drag-and-drop natif totalement indépendant (texte sélectionné ailleurs sur la page,
        //    PAS de dragStartCustomCard appelé) déposé sur la carte C - un dataTransfer "étranger"
        //    qui ne contient PAS le type MIME propre à l'app.
        const dtForeign = { setData() {}, getData() { return ''; }, types: ['text/plain'] };
        component.dropOnCustomCard({ preventDefault: () => {}, dataTransfer: dtForeign }, c);
        await flush();

        assert(component.customCards.map(x => x.id).join(',') === 'a,b,c', 'round 53 : un drop natif étranger (sans dragStartCustomCard préalable) ne réordonne PAS les cartes, même après un self-drop précédent');
        assert(persistCalls === 0, 'round 53 : un drop natif étranger ne déclenche AUCUN appel réseau (pas de persistance silencieuse basée sur un id périmé)');
    }

    // --- Test 3 (non-régression) : un vrai drag-and-drop légitime (A vers C) réordonne bien et persiste ---
    {
        let persistCalls = 0;
        let lastBody = null;
        const okFetch = function (url, opts) { persistCalls++; lastBody = JSON.parse(opts.body); return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: lastBody.value } }) }); };
        const component = loadPromptBuilder(okFetch);
        const a = makeCard('a'), b = makeCard('b'), c = makeCard('c');
        component.customCards.push(a, b, c);

        const dt = makeDataTransfer();
        component.dragStartCustomCard({ dataTransfer: dt }, a);
        component.dropOnCustomCard({ preventDefault: () => {}, dataTransfer: dt }, c);
        await flush();

        assert(component.customCards.map(x => x.id).join(',') === 'b,c,a', 'round 53 (non-régression) : un drag légitime A→C réordonne correctement (a déplacée après c)');
        assert(persistCalls === 1, 'round 53 (non-régression) : un drag légitime déclenche bien la persistance');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
