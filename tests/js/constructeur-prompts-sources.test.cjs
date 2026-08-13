// tests/js/constructeur-prompts-sources.test.cjs
// Garde-fou (tâche 1786, 2026-08-12) : un verbe de recherche mentionnant Internet demande
// désormais les sources, AUTOMATIQUEMENT et sans nouvelle option à l'écran.
// Trois invariants, chacun issu d'un piège réel :
//   1. La consigne apparaît avec les verbes « Internet » et n'exige PAS une citation par phrase
//      (une contrainte de citation stricte sans issue licite augmente les références invalides -
//      étude 2026, proceedings.mlr.press/v318/davis26a).
//   2. L'aveu d'absence de source est explicitement autorisé (Mistral et certains modes n'ont
//      aucun accès web et inventeraient des liens sans cette porte de sortie).
//   3. Aucune fuite hors des verbes de recherche datés : un prompt ordinaire reste inchangé.
// Exécute : node tests/js/constructeur-prompts-sources.test.cjs
const fs = require('fs');
const path = require('path');

const VERB_WEB = 'Recherche sur Internet, en priorisant les sites officiels et pertinents';
const VERB_DEEP = 'Recherche en profondeur, Internet inclus';
const VERB_PLAIN = 'Recherche';

function loadPromptBuilder() {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;
    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: {
            personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {},
            today: { long: '12 août 2026', iso: '2026-08-12' },
        },
        dispatchEvent: () => {},
        toast: () => {},
    };
    global.navigator = global.navigator || {};
    global.navigator.clipboard = { writeText: () => Promise.resolve() };
    global.window.copyToClipboard = function (text) { return navigator.clipboard.writeText(text).then(() => true); };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.URLSearchParams = require('url').URLSearchParams;
    global.localStorage = {
        _store: {},
        getItem(k) { return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { this._store[k] = String(v); },
        removeItem(k) { delete this._store[k]; },
    };
    global.fetch = () => Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    return component;
}

function promptFor(verb) {
    const component = loadPromptBuilder();
    component.personaPreset = 'redacteur_web';
    component.verbType = 'preset';
    component.verb = verb;
    component.taskObject = 'les subventions disponibles pour les PME';
    return component.prompt;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

// 1. Verbe « Internet » : la consigne de sources est présente, groupée en fin de réponse.
{
    const prompt = promptFor(VERB_WEB);
    assert(prompt.includes('une courte liste des sources utilisées (titre et lien)'), 'les sources sont demandées en une courte liste');
    assert(prompt.includes('limitée à celles que ta recherche t\'a fournies'), 'les sources sont ancrées sur ce que la recherche a fourni');
    assert(
        !prompt.includes('réellement consultée'),
        'la formulation anthropomorphique « réellement consultée » est écartée'
    );
}

// 2. L'aveu d'absence de source est explicitement autorisé (anti-invention de liens).
{
    const prompt = promptFor(VERB_WEB);
    assert(prompt.includes('N\'invente aucune référence'), 'l\'invention de référence est interdite explicitement');
    assert(
        prompt.includes('si tu n\'as eu accès à aucune source, écris-le clairement à la place'),
        'une issue licite est offerte au modèle sans accès web'
    );
}

// 3. Le verbe de recherche approfondie (Internet inclus) est couvert lui aussi.
{
    assert(promptFor(VERB_DEEP).includes('une courte liste des sources utilisées'), 'la recherche en profondeur demande aussi les sources');
}

// 4. Aucune fuite : le verbe « Recherche » simple (sans Internet) et un verbe ordinaire n'ajoutent rien.
{
    assert(!promptFor(VERB_PLAIN).includes('courte liste des sources'), 'le verbe « Recherche » simple n\'ajoute pas la consigne de sources');
    assert(!promptFor('Rédige').includes('courte liste des sources'), 'un verbe ordinaire n\'ajoute pas la consigne de sources');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail === 0 ? 0 : 1);
