// tests/js/constructeur-prompts-destinationformat.test.cjs
// Garde-fou de non-regression (round 55, 2026-07-27) : canvasFormat est une liste PREDEFINIE
// propre a chaque IA (canvasFormatMap : chatgpt/claude/gemini/mistral n'ont pas les memes formats).
// L'ancien setter `destination` changeait canvasAI SANS jamais reinitialiser canvasFormat. Si
// l'utilisateur choisissait un format (ex. "SVG" pour Claude) PUIS changeait de destination (ex.
// vers ChatGPT, qui n'a pas "SVG" dans sa liste), canvasFormat restait a l'ancienne valeur
// perimee - le <select> canvasFormat s'affichait VIDE dans l'UI (aucune option ne correspond
// plus), mais le getter `prompt` injectait quand meme silencieusement "Format attendu dans cet
// espace : SVG." dans le texte final envoye a l'IA, une instruction incoherente que l'utilisateur
// ne pouvait pas voir sans deplier la vue technique. Le fix reinitialise canvasFormat des que
// l'IA de destination change reellement.
// Execute : node tests/js/constructeur-prompts-destinationformat.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder() {
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
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: false, i18n: {} },
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
    global.fetch = function () { return Promise.reject(new Error('fetch should not be reachable in these tests')); };

    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    component.customCardsLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

(function run() {
    // --- Test 1 (LE bug round 55) : changer de destination après un format prédéfini choisi
    //     ne doit JAMAIS laisser une valeur canvasFormat périmée fuiter dans le prompt final ---
    {
        const pb = loadPromptBuilder();
        pb.destination = 'claude';
        pb.formatMode = 'preset';
        pb.canvasFormat = 'SVG';
        assert(pb.canvasFormats.indexOf('SVG') !== -1, 'round 55 : "SVG" fait bien partie des formats prédéfinis de Claude (sanity check)');

        pb.destination = 'chatgpt';

        assert(pb.canvasFormats.indexOf('SVG') === -1, 'round 55 : "SVG" ne fait PAS partie des formats prédéfinis de ChatGPT (sanity check)');
        assert(pb.canvasFormat === '', 'round 55 : canvasFormat est réinitialisé après un changement réel de destination (pas de valeur périmée)');
        assert(pb.prompt.indexOf('SVG') === -1, 'round 55 : le prompt final ne contient JAMAIS une instruction de format incohérente avec la nouvelle destination');
        assert(pb.prompt.indexOf('Canvas de ChatGPT') !== -1, 'round 55 (non-régression) : la ligne Destination reflète bien la nouvelle IA choisie');
    }

    // --- Test 2 (non-régression) : re-choisir la MÊME destination ne doit PAS effacer le format ---
    {
        const pb = loadPromptBuilder();
        pb.destination = 'claude';
        pb.formatMode = 'preset';
        pb.canvasFormat = 'Mermaid';

        pb.destination = 'claude'; // même valeur, pas un vrai changement

        assert(pb.canvasFormat === 'Mermaid', 'round 55 (non-régression) : re-sélectionner la même destination ne réinitialise PAS le format déjà choisi');
        assert(pb.prompt.indexOf('Mermaid') !== -1, 'round 55 (non-régression) : le format valide reste bien présent dans le prompt final');
    }

    // --- Test 3 (non-régression) : revenir à "Conversation standard" (valeur vide) désactive
    //     toujours constraintCanvas comme avant le fix ---
    {
        const pb = loadPromptBuilder();
        pb.destination = 'gemini';
        pb.formatMode = 'preset';
        pb.canvasFormat = 'Google Docs';

        pb.destination = '';

        assert(pb.constraintCanvas === false, 'round 55 (non-régression) : revenir à "Conversation standard" désactive bien constraintCanvas');
        assert(pb.destination === '', 'round 55 (non-régression) : le getter destination reflète bien la désactivation');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
