// tests/js/constructeur-prompts-cardpanel-staleblur.test.cjs
// Garde-fou de non-regression (round 95, 2026-07-27, passe adversariale) : le panneau d'edition du
// gabarit d'une carte personnalisee (round 46) reste OUVERT apres un blur (editingCardPanelId
// n'est remis a null que par toggleCardPanel()/cancelEditCardPanel()). Un clic sur un AUTRE bouton
// de la MEME carte (deplacer/masquer/supprimer) pendant l'edition declenche un blur intermediaire
// qui persistait la valeur courante cote serveur SANS jamais rafraichir editingCardPanelSnapshot
// (capture UNE SEULE FOIS a l'ouverture, round 46). Un Echap ulterieur restaurait alors ce snapshot
// perime - plus ancien que ce qui etait reellement persiste - laissant le client et le serveur
// desynchronises silencieusement. Fix : commitCardPanelBlur() (appele par @blur du textarea a la
// place de persistCustomCards() direct) rafraichit le snapshot a CHAQUE blur, pas seulement a
// l'ouverture.
// Execute : node tests/js/constructeur-prompts-cardpanel-staleblur.test.cjs (ou npm run test:js)
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
    // --- Test 1 (round 95) : blur intermédiaire (ex. clic sur ↑/👁️/🗑️ de la même carte, panneau
    // toujours ouvert) rafraîchit le snapshot - Escape restaure la valeur du blur, pas l'ancienne ---
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
        const card = { id: 'card-1', title: 'Carte A', icon: '⭐', query_template: 'Original', hidden: false };
        component.customCards.push(card);

        component.toggleCardPanel(card);
        assert(component.editingCardPanelId === 'card-1', 'round 95 : le panneau est bien ouvert');

        // Frappe puis blur intermédiaire (ex. clic sur un autre bouton de la carte) - le panneau
        // reste OUVERT (comportement réel : seuls toggleCardPanel/cancelEditCardPanel le ferment).
        card.query_template = 'X (persisté au blur intermédiaire)';
        component.commitCardPanelBlur(card);
        await flush();

        assert(component.editingCardPanelId === 'card-1', 'round 95 : le panneau reste ouvert après le blur intermédiaire (comportement réel)');
        assert(lastPersistedTemplates && lastPersistedTemplates[0] === 'X (persisté au blur intermédiaire)', 'round 95 : le blur intermédiaire a bien persisté "X" côté serveur');

        // Nouvelle frappe, puis Échap.
        card.query_template = 'Y (jamais persisté, abandonné)';
        component.cancelEditCardPanel(card);
        await flush();

        assert(card.query_template === 'X (persisté au blur intermédiaire)', 'round 95 : Échap restaure la DERNIÈRE valeur persistée (celle du blur intermédiaire), pas l\'ancien snapshot d\'ouverture "Original"');
        assert(component.editingCardPanelId === null, 'round 95 : Échap ferme bien le panneau');
    }

    // --- Test 2 (non-régression) : sans blur intermédiaire, le comportement round 46 est inchangé ---
    {
        const component = loadPromptBuilder(okFetch);
        const card = { id: 'card-1', title: 'Carte A', icon: '⭐', query_template: 'Gabarit original', hidden: false };
        component.customCards.push(card);

        component.toggleCardPanel(card);
        card.query_template = 'brouillon tapé puis abandonné';
        component.cancelEditCardPanel(card);
        await flush();

        assert(card.query_template === 'Gabarit original', 'round 95 (non-régression) : sans blur intermédiaire, Échap restaure toujours le snapshot d\'ouverture');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
