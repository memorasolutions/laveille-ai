// tests/js/constructeur-prompts-cardtitle-cancel.test.cjs
// Garde-fou de non-regression (round 45, 2026-07-27) : x-model="c.title" est un lien direct (live
// two-way binding) sur l'objet partage du tableau customCards - sans snapshot, Escape ne pouvait
// que fermer l'input (editingCardId = null) sans jamais restaurer la valeur pre-edition. Le titre
// partiel/corrompu tape restait donc en memoire et etait silencieusement persiste au serveur a la
// PROCHAINE mutation quelconque (reordonner, icone, ajout/suppression d'une AUTRE carte) via
// persistCustomCards() (remplacement integral du tableau). Le fix capture un snapshot du titre
// avant edition (startEditCardTitle/addCustomCard) et cancelEditCardTitle() restaure ce snapshot
// au lieu de se contenter d'effacer editingCardId.
// Execute : node tests/js/constructeur-prompts-cardtitle-cancel.test.cjs (ou npm run test:js)
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

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

const okFetch = function () {
    return Promise.resolve({ ok: true, json: () => Promise.resolve({ preferences: { custom_cards: [] } }) });
};

(async function run() {
    // --- Test 1 : Escape restaure le titre d'origine, ne le laisse PAS en mémoire ---
    {
        const component = loadPromptBuilder(okFetch);
        const card = { id: 'card-1', title: 'Titre original', icon: '⭐', query_template: '', hidden: false };
        component.customCards.push(card);

        component.startEditCardTitle(card);
        card.title = 'brouillon tapé puis abandonné';
        component.cancelEditCardTitle(card);
        await flush();

        assert(card.title === 'Titre original', 'round 45 : Escape restaure le titre d\'origine (pas le brouillon)');
        assert(component.editingCardId === null, 'round 45 : Escape ferme bien l\'édition (editingCardId = null)');
    }

    // --- Test 2 : le titre restauré n'est PAS envoyé au serveur à la prochaine mutation non liée ---
    {
        let lastPersistedTitles = null;
        const fetchImpl = function (url, opts) {
            if (url === '/api/tool-preferences/constructeur-prompts' && opts && opts.method === 'POST') {
                const body = JSON.parse(opts.body);
                if (body.key === 'custom_cards') {
                    lastPersistedTitles = body.value.map(function (c) { return c.title; });
                }
            }
            return okFetch();
        };
        const component = loadPromptBuilder(fetchImpl);
        const card = { id: 'card-1', title: 'Titre original', icon: '⭐', query_template: '', hidden: false };
        const otherCard = { id: 'card-2', title: 'Autre carte', icon: '🎯', query_template: '', hidden: false };
        component.customCards.push(card, otherCard);

        component.startEditCardTitle(card);
        card.title = 'brouillon tapé puis abandonné';
        component.cancelEditCardTitle(card);
        await flush();

        // Mutation NON liée sur une AUTRE carte (ex. changement d'icône) - persistCustomCards() est
        // appelé et envoie le tableau COMPLET (remplacement intégral, pas un merge).
        component.setCardIcon(otherCard, '🚀');
        await flush();

        assert(lastPersistedTitles !== null, 'round 45 : la mutation sur otherCard a bien déclenché un persist');
        assert(lastPersistedTitles && lastPersistedTitles[0] === 'Titre original', 'round 45 : le titre annulé n\'a JAMAIS été envoyé au serveur (pas de fuite du brouillon)');
    }

    // --- Test 3 : non-régression - commitCardTitle() (Enter/blur) persiste toujours normalement ---
    {
        let persistedTitle = null;
        const fetchImpl = function (url, opts) {
            if (url === '/api/tool-preferences/constructeur-prompts' && opts && opts.method === 'POST') {
                const body = JSON.parse(opts.body);
                if (body.key === 'custom_cards') persistedTitle = body.value[0].title;
            }
            return okFetch();
        };
        const component = loadPromptBuilder(fetchImpl);
        const card = { id: 'card-1', title: 'Titre original', icon: '⭐', query_template: '', hidden: false };
        component.customCards.push(card);

        component.startEditCardTitle(card);
        card.title = 'Nouveau titre validé';
        component.commitCardTitle(card);
        await flush();

        assert(card.title === 'Nouveau titre validé', 'round 45 (non-régression) : commitCardTitle() applique bien le nouveau titre');
        assert(persistedTitle === 'Nouveau titre validé', 'round 45 (non-régression) : commitCardTitle() persiste bien le nouveau titre');
        assert(component.editingCardId === null, 'round 45 (non-régression) : commitCardTitle() ferme bien l\'édition');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
