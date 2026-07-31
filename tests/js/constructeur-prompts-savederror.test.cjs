// tests/js/constructeur-prompts-savederror.test.cjs
// Garde-fou de non-regression (round 35, 2026-07-27) : addToHistory() doit remonter le message
// d'erreur precis renvoye par le serveur (ex. validation "params trop volumineux" du round 34)
// plutot qu'un message generique. Avant ce fix, le fetch throw avant de lire le corps JSON de la
// reponse 422, donc self._showSaveError() etait toujours appele SANS argument -> message generique.
// Execute : node tests/js/constructeur-prompts-savederror.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(fetchImpl) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;

    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
        dispatchEvent: () => {},
    };
    global.navigator.clipboard = { writeText: () => {} };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.fetch = fetchImpl;

    new Function(src)();
    const component = factory();
    // Round 63 (2026-07-27) : addToHistory() exige désormais historyLoaded=true (garde contre la
    // course avec le GET initial de init(), jamais appelé ici puisqu'on teste addToHistory()
    // isolément) - simuler un historique déjà chargé, même convention que customCardsLoaded.
    component.historyLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    // Laisse les .then()/.catch() chaines de addToHistory() se resoudre (plusieurs micro-taches).
    for (let i = 0; i < 5; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 : 422 avec message precis (ex. params trop volumineux, round 34) -> message exact affiche ---
    {
        const component = loadPromptBuilder(function () {
            return Promise.resolve({
                ok: false,
                status: 422,
                json: () => Promise.resolve({ message: 'Les paramètres du prompt sont trop volumineux.' }),
            });
        });
        component.saveName = 'Test';
        component.prompt = 'Un prompt de test';
        component.addToHistory();
        await flush();
        assert(component.saveError === 'Les paramètres du prompt sont trop volumineux.', 'round 35 : message de validation precis (422) remonte tel quel, pas generique');
        assert(component.saving === false, 'saving repasse a false apres l\'echec');
    }

    // --- Test 2 : erreur reseau (fetch rejette, pas de corps JSON) -> repli generique, pas de crash ---
    {
        const component = loadPromptBuilder(function () { return Promise.reject(new Error('network down')); });
        component.saveName = 'Test';
        component.prompt = 'Un prompt de test';
        component.addToHistory();
        await flush();
        assert(component.saveError === 'Erreur de sauvegarde. Réessayez.', 'round 35 : erreur reseau retombe sur le message generique (aucun message exploitable)');
    }

    // --- Test 3 : 500 sans corps JSON exploitable (parse echoue) -> repli generique, pas de crash ---
    {
        const component = loadPromptBuilder(function () {
            return Promise.resolve({
                ok: false,
                status: 500,
                json: () => Promise.reject(new Error('invalid json')),
            });
        });
        component.saveName = 'Test';
        component.prompt = 'Un prompt de test';
        component.addToHistory();
        await flush();
        assert(component.saveError === 'Erreur de sauvegarde. Réessayez.', 'round 35 : 500 sans JSON exploitable retombe sur le message generique (aucun crash)');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
