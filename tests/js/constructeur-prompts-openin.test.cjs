// tests/js/constructeur-prompts-openin.test.cjs
// Garde-fou de non-régression pour les 4 boutons « Ouvrir dans » du Constructeur de prompts
// (ChatGPT / Claude / Perplexity / Gemini) — Phase 3 audit perf 2026-07-26.
// Vérifie que openIn() construit une URL bien formée et correctement encodée pour chaque
// destination IA, sans dépendre d'un vrai navigateur (mêmes stubs minimaux que
// anonymizer-detect.test.cjs pour document/Alpine/window/navigator).
// Exécuter : node tests/js/constructeur-prompts-openin.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder() {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    const openCalls = [];
    const dispatched = [];
    const clipboardWrites = [];
    let factory = null;

    global.document = { addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); } };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: false, i18n: {} },
        dispatchEvent: (evt) => dispatched.push(evt),
        open: (...args) => openCalls.push(args),
    };
    // Node 21+ expose un `navigator` global natif en lecture seule (pas de setter) : on ne peut
    // pas remplacer `global.navigator` entièrement, mais `navigator.clipboard` est assignable.
    global.navigator.clipboard = { writeText: (text) => clipboardWrites.push(text) };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };

    new Function(src)();
    const component = factory();
    return { component, openCalls, dispatched, clipboardWrites };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

// --- Test 1 : les 4 destinations génèrent une URL valide, avec le prompt correctement encodé ---
const destinations = [
    { target: 'chatgpt', base: 'https://chatgpt.com/?q=', hasPrompt: true },
    { target: 'claude', base: 'https://claude.ai/new?q=', hasPrompt: true },
    { target: 'perplexity', base: 'https://www.perplexity.ai/search?q=', hasPrompt: true },
    { target: 'gemini', base: 'https://gemini.google.com/app', hasPrompt: false },
];

destinations.forEach(function (dest) {
    const { component, openCalls, clipboardWrites } = loadPromptBuilder();
    component.verbType = 'preset';
    component.verb = 'Rédige';
    component.taskObject = "un texte de test avec accents éàçê & = ? « guillemets » et une esperluette";
    const expectedPrompt = component.prompt;
    assert(expectedPrompt.length > 0, `[${dest.target}] le prompt généré n'est pas vide`);

    component.openIn(dest.target);
    assert(openCalls.length === 1, `[${dest.target}] window.open a été appelé une seule fois`);
    const [url, windowName, features] = openCalls[0] || [];
    assert(typeof url === 'string' && url.indexOf(dest.base) === 0, `[${dest.target}] l'URL commence par ${dest.base}`);
    assert(windowName === '_blank' && features === 'noopener', `[${dest.target}] ouverture en _blank/noopener (pas de vulnérabilité tabnabbing)`);

    if (dest.hasPrompt) {
        const query = url.slice(dest.base.length);
        let decoded = null;
        try { decoded = decodeURIComponent(query); } catch (e) { /* laisse decoded = null */ }
        assert(decoded === expectedPrompt, `[${dest.target}] le prompt décodé depuis l'URL correspond exactement au prompt généré (accents/symboles préservés)`);
    } else {
        assert(url === dest.base, `[${dest.target}] Gemini n'accepte pas de prompt via URL : pas de paramètre ajouté`);
    }

    assert(clipboardWrites.length === 1 && clipboardWrites[0] === expectedPrompt, `[${dest.target}] le prompt est aussi copié dans le presse-papier`);
});

// --- Test 2 : prompt trop long (>4000 caractères encodés) → pas de prompt dans l'URL, pas de crash ---
{
    const { component, openCalls } = loadPromptBuilder();
    component.verbType = 'preset';
    component.verb = 'Rédige';
    component.taskObject = 'x'.repeat(4500);
    component.openIn('chatgpt');
    const [url] = openCalls[0] || [];
    assert(url === 'https://chatgpt.com/?q=', "prompt trop long : l'URL ChatGPT ne contient aucun paramètre q (pas de lien cassé/tronqué)");
}

// --- Test 3 : cible inconnue → aucune ouverture (garde-fou du switch/default) ---
{
    const { component, openCalls } = loadPromptBuilder();
    component.verbType = 'preset';
    component.verb = 'Rédige';
    component.taskObject = 'test';
    component.openIn('mistral-non-supporte');
    assert(openCalls.length === 0, "cible IA non reconnue : aucune fenêtre ouverte");
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
