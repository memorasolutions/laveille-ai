// tests/js/constructeur-prompts-guesthistory.test.cjs
// Garde-fou de non-régression (#1580, 2026-08-07) : rétention locale invités.
//   1. cpGuestHistory_v1 (clé versionnée, DISTINCTE de pb_history) reçoit une entrée
//      {date, title, state} à la copie/génération, pour un visiteur NON connecté seulement.
//   2. Un utilisateur CONNECTÉ n'écrit jamais dans cpGuestHistory_v1 (il a "Mes prompts" en base).
//   3. Anti-doublon consécutif : deux copies successives du même prompt n'ajoutent qu'une entrée.
//   4. Plafond de 10 entrées, la plus récente en tête.
//   5. loadGuestHistoryEntry() restaure l'état (désérialisation), y compris contextInfo.
//   6. deleteGuestHistoryEntry()/clearGuestHistory() persistent bien dans localStorage.
// Note : chaque test recrée ses propres globals (document/window/localStorage) via
// loadPromptBuilder() - les tests s'exécutent SÉQUENTIELLEMENT (await de bout en bout) dans un
// seul runner async, jamais en IIFE concurrentes, pour ne jamais mélanger deux localStorage mock.
// Exécute : node tests/js/constructeur-prompts-guesthistory.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(isAuthenticated) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;
    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: !!isAuthenticated, i18n: {} },
        dispatchEvent: () => {},
        toast: () => {},
        open: () => {},
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
    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }
async function flush() { for (let i = 0; i < 6; i++) await Promise.resolve(); }

function fillValid(component, taskObject) {
    component.personaPreset = 'redacteur_web';
    component.personas = [{ value: 'redacteur_web', label: 'Rédacteur web' }];
    component.verbType = 'preset';
    component.verb = 'Rédige';
    component.taskObject = taskObject || 'un courriel de bienvenue';
}

(async function run() {
    // 1. Copie d'un prompt valide (invité) -> une entrée apparaît dans cpGuestHistory_v1.
    {
        const component = loadPromptBuilder(false);
        fillValid(component);
        component.copy();
        await flush();
        const raw = localStorage.getItem('cpGuestHistory_v1');
        assert(raw !== null, 'cpGuestHistory_v1 contient une entrée après copy() (invité)');
        const list = JSON.parse(raw || '[]');
        assert(Array.isArray(list) && list.length === 1, 'exactement une entrée après une copie');
        assert(list[0].title === 'un courriel de bienvenue', 'le titre auto vient des 60 premiers caractères de la tâche');
        assert(typeof list[0].date === 'string' && !isNaN(Date.parse(list[0].date)), 'la date est une chaîne ISO valide');
        assert(list[0].state && list[0].state.taskObject === 'un courriel de bienvenue', 'state contient bien le wizardParams sérialisé');
    }

    // 2. Utilisateur CONNECTÉ : jamais d'écriture dans cpGuestHistory_v1.
    {
        const component = loadPromptBuilder(true);
        fillValid(component);
        component.copy();
        await flush();
        assert(localStorage.getItem('cpGuestHistory_v1') === null, 'aucune écriture dans cpGuestHistory_v1 pour un utilisateur connecté');
    }

    // 3. Anti-doublon consécutif : deux copies du même prompt = une seule entrée.
    {
        const component = loadPromptBuilder(false);
        fillValid(component, 'toujours la même tâche');
        component.copy();
        await flush();
        component.copy();
        await flush();
        const list = JSON.parse(localStorage.getItem('cpGuestHistory_v1') || '[]');
        assert(list.length === 1, 'deux copies consécutives du même prompt ne créent qu\'une seule entrée (anti-doublon), trouvé : ' + list.length);
    }

    // 4. Plafond de 10 entrées, la plus récente en tête.
    {
        const component = loadPromptBuilder(false);
        for (let i = 0; i < 12; i++) {
            fillValid(component, 'tâche numéro ' + i);
            component.copy();
            // eslint-disable-next-line no-await-in-loop
            await flush();
        }
        const list = JSON.parse(localStorage.getItem('cpGuestHistory_v1') || '[]');
        assert(list.length === 10, 'jamais plus de 10 entrées conservées, trouvé : ' + list.length);
        assert(list[0].title === 'tâche numéro 11', 'la plus récente entrée est en tête de liste');
    }

    // 5. loadGuestHistoryEntry() restaure l'état, y compris contextInfo (#1593a).
    {
        const component = loadPromptBuilder(false);
        fillValid(component, 'tâche à restaurer');
        component.contextInfo = 'contexte à restaurer aussi';
        component.copy();
        await flush();
        component._loadGuestHistory();
        assert(component.guestHistory.length === 1, 'guestHistory (état réactif) reflète le localStorage après _loadGuestHistory()');
        const savedList = component.guestHistory;

        const fresh = loadPromptBuilder(false);
        // Simule une réouverture de page : localStorage garde l'entrée du composant précédent.
        localStorage.setItem('cpGuestHistory_v1', JSON.stringify(savedList));
        fresh._loadGuestHistory();
        fresh.loadGuestHistoryEntry(0);
        assert(fresh.taskObject === 'tâche à restaurer', 'loadGuestHistoryEntry() restaure taskObject');
        assert(fresh.contextInfo === 'contexte à restaurer aussi', 'loadGuestHistoryEntry() restaure contextInfo');
        assert(fresh.verb === 'Rédige', 'loadGuestHistoryEntry() restaure le verbe');
    }

    // 6. deleteGuestHistoryEntry() / clearGuestHistory() persistent bien dans localStorage.
    {
        const component = loadPromptBuilder(false);
        fillValid(component, 'à supprimer');
        component.copy();
        await flush();
        assert(component.guestHistory.length === 1, 'une entrée avant suppression');
        component.deleteGuestHistoryEntry(0);
        assert(component.guestHistory.length === 0, 'guestHistory vidé après deleteGuestHistoryEntry()');
        assert(JSON.parse(localStorage.getItem('cpGuestHistory_v1') || '[]').length === 0, 'localStorage reflète bien la suppression');

        fillValid(component, 'à effacer');
        component.copy();
        await flush();
        component.clearGuestHistory();
        assert(component.guestHistory.length === 0, 'guestHistory vidé après clearGuestHistory()');
        assert(localStorage.getItem('cpGuestHistory_v1') === null, 'clearGuestHistory() retire complètement la clé du localStorage');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
