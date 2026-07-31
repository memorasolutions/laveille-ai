// tests/js/constructeur-prompts-cardpanel-cancel.test.cjs
// Garde-fou de non-regression (round 46, 2026-07-27) : meme risque que le titre (round 45), mais
// pour le textarea query_template du panneau d'edition d'une carte personnalisee (bouton crayon).
// x-model="c.query_template" est un lien direct sur l'objet partage du tableau customCards,
// persiste au blur - sans AUCUN snapshot ni Escape, impossible de renoncer a une saisie non
// desiree : elle etait systematiquement persistee des la fermeture du panneau (le seul moyen de
// fermer etant de recliquer le crayon, ce qui declenche blur AVANT la fermeture). Le fix capture
// un snapshot du query_template a l'OUVERTURE du panneau (toggleCardPanel) et cancelEditCardPanel()
// restaure ce snapshot au lieu de laisser le brouillon en memoire.
// Execute : node tests/js/constructeur-prompts-cardpanel-cancel.test.cjs (ou npm run test:js)
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
    // --- Test 1 : Escape restaure le query_template d'origine, ne le laisse PAS en mémoire ---
    {
        const component = loadPromptBuilder(okFetch);
        const card = { id: 'card-1', title: 'Carte A', icon: '⭐', query_template: 'Gabarit original', hidden: false };
        component.customCards.push(card);

        component.toggleCardPanel(card);
        assert(component.editingCardPanelId === 'card-1', 'round 46 : toggleCardPanel() ouvre bien le panneau');

        card.query_template = 'brouillon tapé puis abandonné';
        component.cancelEditCardPanel(card);
        await flush();

        assert(card.query_template === 'Gabarit original', 'round 46 : Escape restaure le query_template d\'origine (pas le brouillon)');
        assert(component.editingCardPanelId === null, 'round 46 : Escape ferme bien le panneau (editingCardPanelId = null)');
    }

    // --- Test 2 : le query_template restauré n'est PAS envoyé au serveur à la prochaine mutation non liée ---
    {
        let lastPersistedTemplates = null;
        const fetchImpl = function (url, opts) {
            if (url === '/api/tool-preferences/constructeur-prompts' && opts && opts.method === 'POST') {
                const body = JSON.parse(opts.body);
                if (body.key === 'custom_cards') {
                    lastPersistedTemplates = body.value.map(function (c) { return c.query_template; });
                }
            }
            return okFetch();
        };
        const component = loadPromptBuilder(fetchImpl);
        const card = { id: 'card-1', title: 'Carte A', icon: '⭐', query_template: 'Gabarit original', hidden: false };
        const otherCard = { id: 'card-2', title: 'Carte B', icon: '🎯', query_template: '', hidden: false };
        component.customCards.push(card, otherCard);

        component.toggleCardPanel(card);
        card.query_template = 'brouillon tapé puis abandonné';
        component.cancelEditCardPanel(card);
        await flush();

        // Mutation NON liée sur une AUTRE carte - persistCustomCards() envoie le tableau COMPLET.
        component.setCardIcon(otherCard, '🚀');
        await flush();

        assert(lastPersistedTemplates !== null, 'round 46 : la mutation sur otherCard a bien déclenché un persist');
        assert(lastPersistedTemplates && lastPersistedTemplates[0] === 'Gabarit original', 'round 46 : le query_template annulé n\'a JAMAIS été envoyé au serveur (pas de fuite du brouillon)');
    }

    // --- Test 3 : non-régression - fermer/rouvrir le panneau sans Escape garde le snapshot à jour ---
    {
        const component = loadPromptBuilder(okFetch);
        const card = { id: 'card-1', title: 'Carte A', icon: '⭐', query_template: 'Version 1', hidden: false };
        component.customCards.push(card);

        // Ouvre, tape, ferme via blur normal (persistCustomCards appelé par @blur, pas testé ici) -
        // simule le commit réel en modifiant directement query_template comme le ferait x-model.
        component.toggleCardPanel(card);
        card.query_template = 'Version 2 validée';
        component.toggleCardPanel(card); // referme (toggle) sans passer par cancelEditCardPanel
        await flush();

        assert(card.query_template === 'Version 2 validée', 'round 46 (non-régression) : fermer sans Escape conserve la nouvelle valeur');
        assert(component.editingCardPanelId === null, 'round 46 (non-régression) : le 2e toggle ferme bien le panneau');

        // Réouvrir doit capturer un NOUVEAU snapshot (la valeur validée, pas l'ancienne "Version 1")
        component.toggleCardPanel(card);
        card.query_template = 'brouillon 2';
        component.cancelEditCardPanel(card);
        await flush();

        assert(card.query_template === 'Version 2 validée', 'round 46 (non-régression) : le snapshot se met à jour à chaque nouvelle ouverture du panneau');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
