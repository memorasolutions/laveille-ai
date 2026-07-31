// tests/js/constructeur-prompts-clipboardpromise.test.cjs
// Garde-fou de non-regression (round 94, 2026-07-27, passe adversariale) : copy() et openIn()
// appelaient navigator.clipboard.writeText() sans jamais gerer la Promise retournee (ni .then()
// ni .catch()) - seul un try/catch SYNCHRONE entourait l'appel, qui n'intercepte jamais un rejet
// ASYNCHRONE. Le bouton "Copier le prompt" passait a "Copie !" et un toast succes s'affichait
// INCONDITIONNELLEMENT, meme quand l'ecriture presse-papiers echouait reellement (permission
// refusee, contexte non securise, politique navigateur). Fix : les deux fonctions delegent
// desormais a window.copyToClipboard() (deja etabli, master.blade.php) qui attend la Promise
// reelle. openIn() garde window.open() SYNCHRONE (meme pile d'appel que le clic, jamais dans un
// .then()) pour ne jamais risquer un blocage popup - seul le toast attend la resolution.
// Execute : node tests/js/constructeur-prompts-clipboardpromise.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(clipboardWriteTextImpl) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;

    const toastCalls = [];
    const openCalls = [];

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
        toast: function (message, variant, duration) { toastCalls.push({ message: message, variant: variant, duration: duration }); },
        open: function (url) { openCalls.push(url); },
        gtag: undefined,
    };
    // Reproduction fidele de window.copyToClipboard (master.blade.php:510-518) : Promise<boolean>,
    // toast succes/erreur selon la resolution reelle de navigator.clipboard.writeText().
    global.window.copyToClipboard = function (text, successMessage) {
        return navigator.clipboard.writeText(text).then(function () {
            global.window.toast(successMessage || 'Copié dans le presse-papiers', 'success', 2500);
            return true;
        }).catch(function () {
            global.window.toast('Copie impossible - copiez le texte manuellement.', 'error', 4000);
            return false;
        });
    };
    // Node 21+ expose un `navigator` global natif en lecture seule (accesseur sans setter) -
    // une simple affectation `global.navigator = {...}` échoue SILENCIEUSEMENT (mode non strict),
    // laissant `navigator.clipboard` undefined. Object.defineProperty() force le remplacement.
    Object.defineProperty(global, 'navigator', { value: { clipboard: { writeText: clipboardWriteTextImpl } }, writable: true, configurable: true });
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.URLSearchParams = require('url').URLSearchParams;
    global.localStorage = { _store: {}, getItem() { return null; }, setItem() {}, removeItem() {} };
    global.fetch = function () { return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) }); };

    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    component.customCardsLoaded = true;
    component.prompt = 'Contenu de test';
    return { component, toastCalls, openCalls };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- copy() : succès réel -> copied=true + toast succès ---
    {
        const { component, toastCalls } = loadPromptBuilder(function () { return Promise.resolve(); });
        component.copy();
        await flush();
        assert(component.copied === true, 'round 94 : copy() met copied=true seulement après succès réel de writeText()');
        assert(toastCalls.length === 1 && toastCalls[0].variant === 'success', 'round 94 : copy() affiche un toast succès après écriture réussie');
    }

    // --- copy() : échec réel (Promise rejetée) -> copied JAMAIS mis à true + toast erreur ---
    {
        const { component, toastCalls } = loadPromptBuilder(function () { return Promise.reject(new Error('NotAllowedError')); });
        component.copy();
        await flush();
        assert(component.copied === false, 'round 94 : copy() NE met PAS copied=true quand writeText() échoue réellement (faux positif corrigé)');
        assert(toastCalls.length === 1 && toastCalls[0].variant === 'error', 'round 94 : copy() affiche un toast erreur explicite quand la copie échoue');
    }

    // --- openIn() : window.open() reste synchrone même en cas d'échec de copie (pas de blocage popup) ---
    {
        const { component, openCalls, toastCalls } = loadPromptBuilder(function () { return Promise.reject(new Error('NotAllowedError')); });
        component.openIn('chatgpt');
        assert(openCalls.length === 1, "round 94 : openIn() appelle window.open() de façon SYNCHRONE (même pile que le clic), jamais retardé par la Promise de copie");
        await flush();
        assert(toastCalls.length === 1 && toastCalls[0].variant === 'error', 'round 94 : openIn() affiche un toast erreur (pas "Prompt copié" mensonger) quand la copie échoue réellement');
    }

    // --- openIn() : succès réel -> toast contextuel "openInGeneric" (pas le toast générique du helper) ---
    {
        const { component, toastCalls } = loadPromptBuilder(function () { return Promise.resolve(); });
        component.openIn('chatgpt');
        await flush();
        assert(toastCalls.length === 1 && toastCalls[0].variant === 'success', 'round 94 : openIn() affiche le toast succès seulement après résolution réelle de la copie');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
