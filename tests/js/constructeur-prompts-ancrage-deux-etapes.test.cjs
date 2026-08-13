// tests/js/constructeur-prompts-ancrage-deux-etapes.test.cjs
// Garde-fou de non-régression (signalement fondateur, 2026-08-12) : quand la tâche est définie en
// DEUX ÉTAPES, l'ancrage final « Produis maintenant : … » ne reprenait que l'étape 1. C'est la
// DERNIÈRE phrase du prompt, celle que le modèle suit le plus fidèlement : il livrait la recherche
// et sautait l'explication demandée en étape 2.
//   1. En mode deux étapes, l'ancrage renvoie à la séquence complète, jamais au seul verbe 1.
//   2. Le mode à UNE seule tâche reste strictement inchangé (le correctif ne doit rien élargir).
// Exécute : node tests/js/constructeur-prompts-ancrage-deux-etapes.test.cjs
const fs = require('fs');
const path = require('path');

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
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
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

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

// 1. Deux étapes : l'ancrage final couvre la SÉQUENCE, pas seulement l'étape 1.
{
    const component = loadPromptBuilder();
    component.personaPreset = 'enseignant';
    component.verbType = 'preset';
    component.verb = 'Recherche sur Internet, en priorisant les sites officiels et pertinents';
    component.taskObject = 'les meilleures pratiques de rédaction de prompts';
    component.secondTaskEnabled = true;
    component.verbType2 = 'preset';
    component.verb2 = 'Explique';
    const prompt = component.prompt;

    const ancrage = prompt.slice(prompt.lastIndexOf('Produis maintenant'));
    assert(prompt.includes('Ta tâche comporte deux étapes'), 'la tâche est bien annoncée en deux étapes');
    assert(ancrage.includes('les deux étapes ci-dessus, dans l\'ordre'), 'l\'ancrage final renvoie à la séquence complète');
    assert(
        !ancrage.includes('les meilleures pratiques de rédaction de prompts'),
        'l\'ancrage ne réduit plus la demande au seul objet de l\'étape 1'
    );
}

// 2. Une seule tâche : comportement d'origine INCHANGÉ (le correctif ne doit rien élargir).
{
    const component = loadPromptBuilder();
    component.personaPreset = 'redacteur_web';
    component.verbType = 'preset';
    component.verb = 'Rédige';
    component.taskObject = 'un courriel de bienvenue';
    const prompt = component.prompt;

    const ancrage = prompt.slice(prompt.lastIndexOf('Produis maintenant'));
    assert(!prompt.includes('Ta tâche comporte deux étapes'), 'aucune séquence à deux étapes sans 2e verbe');
    assert(ancrage.includes('rédige un courriel de bienvenue'), 'l\'ancrage nomme toujours le livrable en mode simple');
    assert(!ancrage.includes('les deux étapes ci-dessus'), 'la formule des deux étapes ne fuit pas en mode simple');
}

// 3. Deux étapes activées mais 2e verbe absent : on retombe sur le comportement simple.
{
    const component = loadPromptBuilder();
    component.personaPreset = 'redacteur_web';
    component.verbType = 'preset';
    component.verb = 'Rédige';
    component.taskObject = 'un courriel de bienvenue';
    component.secondTaskEnabled = true;
    component.verbType2 = 'preset';
    component.verb2 = '';
    const prompt = component.prompt;

    const ancrage = prompt.slice(prompt.lastIndexOf('Produis maintenant'));
    assert(!ancrage.includes('les deux étapes ci-dessus'), 'la case cochée sans 2e verbe ne déclenche pas la formule de séquence');
    assert(ancrage.includes('rédige un courriel de bienvenue'), 'le livrable simple est conservé dans ce cas');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail === 0 ? 0 : 1);
