// tests/js/constructeur-prompts-selecttask-pii-guard.test.cjs
// Garde-fou de non-regression (round 100, 2026-07-27, passe adversariale) : le garde-fou
// anti-donnees-personnelles (prompt-anon-panel.js, checkEntities()) ne se declenche QUE via les
// ecouteurs DOM natifs 'input'/'blur' poses sur #cpTaskObject. Quand taskObject est rempli
// PROGRAMMATIQUEMENT par Alpine (selectTask() avec une carte a gabarit, ou restauration ?edit=ID),
// aucun evenement natif n'est jamais declenche - le garde-fou ne s'execute donc jamais meme si le
// contenu affiche a l'ecran contient un nom/courriel/telephone. Fix : dispatch d'un evenement
// 'input' natif sur #cpTaskObject (via $nextTick) apres chaque assignation programmatique de
// taskObject, pour que le listener 'input' de prompt-anon-panel.js se declenche normalement.
// Execute : node tests/js/constructeur-prompts-selecttask-pii-guard.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(taskFieldEl) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;

    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
        getElementById: (id) => (id === 'cpTaskObject' ? taskFieldEl : null),
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: true, i18n: {} },
        dispatchEvent: () => {},
    };
    global.navigator = global.navigator || {};
    global.navigator.clipboard = { writeText: () => Promise.resolve() };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.Event = class { constructor(type, opts) { this.type = type; this.bubbles = opts && opts.bubbles; } };
    global.localStorage = {
        _store: {},
        getItem(k) { return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { this._store[k] = String(v); },
        removeItem(k) { delete this._store[k]; },
    };
    global.fetch = function () { return Promise.resolve({ ok: true, json: () => Promise.resolve({ history: [] }) }); };

    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    component.editLoading = false;
    return component;
}

function makeTaskField() {
    const listeners = {};
    return {
        value: '',
        addEventListener(type, cb) { (listeners[type] = listeners[type] || []).push(cb); },
        dispatchEvent(evt) { (listeners[evt.type] || []).forEach(function (cb) { cb(evt); }); },
        _listenerCount(type) { return (listeners[type] || []).length; },
    };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

(async function run() {
    // --- Test 1 (round 100) : selectTask() avec un gabarit déclenche bien un événement 'input' natif ---
    {
        const taskField = makeTaskField();
        let inputEventFired = false;
        taskField.addEventListener('input', function () { inputEventFired = true; });

        const component = loadPromptBuilder(taskField);
        component.selectedTask = null;
        component.step = 1;

        component.selectTask({ id: 'card-1', query_template: 'Rédige un courriel pour Jean Dupont, jean.dupont@exemple.com' });

        assert(component.taskObject === 'Rédige un courriel pour Jean Dupont, jean.dupont@exemple.com', 'round 100 : selectTask() assigne bien taskObject depuis card.query_template');
        assert(inputEventFired === true, 'round 100 : selectTask() déclenche un événement \'input\' natif sur #cpTaskObject après avoir rempli taskObject par gabarit (le garde-fou anti-données-personnelles peut alors s\'exécuter)');
    }

    // --- Test 2 (non-régression) : selectTask() sans gabarit (persona/verbe classiques) ne casse rien ---
    {
        const taskField = makeTaskField();
        let inputEventFired = false;
        taskField.addEventListener('input', function () { inputEventFired = true; });

        const component = loadPromptBuilder(taskField);
        component.selectedTask = null;
        component.step = 1;

        component.selectTask({ id: 'card-2', personaValue: 'teacher', verb: 'expliquer' });

        assert(component.taskObject === '', 'round 100 (non-régression) : une carte sans query_template ne modifie pas taskObject');
        assert(inputEventFired === false, 'round 100 (non-régression) : aucun événement \'input\' superflu n\'est déclenché quand il n\'y a pas de gabarit à vérifier');
        assert(component.selectedTask === 'card-2', 'round 100 (non-régression) : selectTask() continue d\'assigner selectedTask normalement');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
