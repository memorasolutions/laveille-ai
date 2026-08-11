// tests/js/constructeur-prompts-brouillon.test.cjs
// Garde-fou de non-régression (2026-08-11) : brouillon local du wizard (cpDraft_v1).
//   1. Écriture localStorage anti-rebond (~600 ms) après un changement significatif, via
//      _scheduleDraftSave()/_saveDraftNow() - jamais d'écriture avant l'échéance, et des appels
//      répétés repoussent bien l'échéance (une seule écriture au final, pas une par appel).
//   2. Formulaire vierge : _saveDraftNow() n'écrit RIEN (et purge un brouillon déjà présent si le
//      formulaire redevient vierge après avoir été rempli). Depuis le correctif du 2026-08-11,
//      "significatif" = tout écart entre wizardParams et l'instantané du formulaire vierge
//      (_draftDefaultSnapshot, pris au tout début d'init()) - ça inclut désormais les champs à
//      SÉLECTION (personaPreset, tone, technique, formats...), pas seulement le texte libre :
//      régression prod corrigée, un rôle choisi au menu déroulant sans texte tapé était perdu.
//   3. init() restaure le brouillon (champs + étape) quand ni ?edit= ni ?remix= n'est présent.
//   4. ?edit=ID / ?remix=ID priment toujours sur le brouillon local (_loadDraft() se retire).
//   5. resetAll() purge cpDraft_v1 AVANT le rechargement - sinon le bouton "Recommencer"
//      réafficherait le brouillon qu'il est censé effacer.
//   6. Expiration à 24h : un brouillon plus vieux est purgé et ignoré.
//   7. Contenu corrompu (JSON invalide, version inconnue, params absents) : jamais de plantage,
//      le brouillon est ignoré et purgé.
// Exécute : node tests/js/constructeur-prompts-brouillon.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(opts) {
    opts = opts || {};
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;

    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
        getElementById: () => null,
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: !!opts.isAuthenticated, i18n: {} },
        location: { search: opts.search || '', hash: '', pathname: '/outils/constructeur-prompts' },
        dispatchEvent: () => {},
    };
    global.history = { replaceState: () => {} };
    global.URLSearchParams = URLSearchParams;
    global.navigator = global.navigator || {};
    global.navigator.clipboard = { writeText: () => Promise.resolve() };
    global.CustomEvent = class { constructor(type, o) { this.type = type; this.detail = o && o.detail; } };
    global.localStorage = {
        _store: {},
        getItem(k) { return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { this._store[k] = String(v); },
        removeItem(k) { delete this._store[k]; },
    };
    global.fetch = opts.fetchImpl || function () { return Promise.resolve({ ok: true, json: () => Promise.resolve({ data: [] }) }); };

    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    component.customCardsLoaded = true;
    // Correctif régression prod (2026-08-11) : _hasSignificantDraftContent() compare désormais
    // wizardParams à un instantané du formulaire vierge (_draftDefaultSnapshot), capturé en
    // production tout au début d'init() - AVANT toute mutation de champ. Ce harnais de test
    // instancie le composant SANS appeler init() dans la plupart des cas (pour rester ciblé sur
    // _scheduleDraftSave()/_saveDraftNow()/_loadDraft() sans déclencher les fetch de init()) : on
    // reproduit donc ici la même capture, au même moment logique (juste après la création du
    // composant, avant que le test ne touche à un champ) - identique à ce que ferait init().
    component._draftDefaultSnapshot = JSON.stringify(component.wizardParams);
    return component;
}

// Horloge simulée : évite d'attendre réellement 600 ms par test, et rend le repoussement du
// délai (anti-rebond) vérifiable de façon déterministe.
function installFakeClock() {
    const realSetTimeout = global.setTimeout;
    const realClearTimeout = global.clearTimeout;
    let now = 0;
    let nextId = 1;
    let timers = [];
    global.setTimeout = function (cb, delay) {
        const id = nextId++;
        timers.push({ id, cb, fireAt: now + (delay || 0) });
        return id;
    };
    global.clearTimeout = function (id) {
        timers = timers.filter(function (t) { return t.id !== id; });
    };
    return {
        advance(ms) {
            now += ms;
            const due = timers.filter(function (t) { return t.fireAt <= now; }).sort(function (a, b) { return a.fireAt - b.fireAt; });
            timers = timers.filter(function (t) { return t.fireAt > now; });
            due.forEach(function (t) { t.cb(); });
        },
        restore() {
            global.setTimeout = realSetTimeout;
            global.clearTimeout = realClearTimeout;
        },
    };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

(function run() {
    // --- Test 1 : rien n'est écrit avant l'échéance de l'anti-rebond (~600 ms) ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        const clock = installFakeClock();
        component.taskObject = 'un rapport trimestriel pour la direction';
        component._scheduleDraftSave();
        assert(localStorage.getItem('cpDraft_v1') === null, 'aucune écriture immédiate après _scheduleDraftSave()');
        clock.advance(400);
        assert(localStorage.getItem('cpDraft_v1') === null, 'toujours rien à 400 ms (< 600 ms)');
        clock.advance(200); // total 600 ms
        const raw = localStorage.getItem('cpDraft_v1');
        assert(raw !== null, 'écrit une fois l\'échéance de 600 ms atteinte');
        const draft = JSON.parse(raw || '{}');
        assert(draft.v === 1, 'le brouillon porte la version v:1');
        assert(typeof draft.savedAt === 'number' && draft.savedAt > 0, 'savedAt est un timestamp numérique');
        assert(draft.step === component.step, 'le brouillon inclut l\'étape courante');
        assert(draft.params && draft.params.taskObject === 'un rapport trimestriel pour la direction', 'le brouillon inclut wizardParams (taskObject)');
        clock.restore();
    }

    // --- Test 2 : des appels répétés repoussent l'échéance (anti-rebond réel, pas une écriture
    //     par appel) ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        const clock = installFakeClock();
        let writes = 0;
        const originalSetItem = localStorage.setItem.bind(localStorage);
        localStorage.setItem = function (k, v) { if (k === 'cpDraft_v1') writes++; originalSetItem(k, v); };

        component.taskObject = 'premier brouillon';
        component._scheduleDraftSave();
        clock.advance(300);
        component.taskObject = 'brouillon mis à jour';
        component._scheduleDraftSave(); // repousse l'échéance de 300 ms supplémentaires
        clock.advance(300); // total réel 600 ms depuis le 1er appel, mais seulement 300 depuis le 2e
        assert(writes === 0, 'un 2e appel avant l\'échéance repousse bien le délai (rien écrit à 600 ms cumulés)');
        clock.advance(300); // 600 ms depuis le 2e appel
        assert(writes === 1, 'une seule écriture au final, pas une par appel à _scheduleDraftSave()');
        const draft = JSON.parse(localStorage.getItem('cpDraft_v1') || '{}');
        assert(draft.params.taskObject === 'brouillon mis à jour', 'la valeur écrite est la plus récente');
        clock.restore();
    }

    // --- Test 3 : formulaire vierge -> _saveDraftNow() n'écrit rien ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') === null, 'un formulaire vierge (aucun champ significatif) n\'écrit rien');

        // Re-sauvegarder sans avoir rien changé (mêmes valeurs par défaut) n'écrit toujours rien -
        // seul un ÉCART avec l'instantané du formulaire vierge déclenche une écriture.
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') === null, 'ré-appeler _saveDraftNow() sans changement n\'écrit toujours rien');
    }

    // --- Test 3c : régression prod (2026-08-11) - sélectionner UNIQUEMENT un rôle prédéfini
    //     (personaPreset), sans taper aucun texte, doit déclencher l'écriture du brouillon. Ce cas
    //     ÉCHOUAIT avec l'ancien code (liste de champs "texte libre" codée en dur, qui ignorait tous
    //     les champs à sélection) : un rôle choisi au menu déroulant de l'étape 1 était perdu au
    //     refresh - c'est le bug signalé en production que ce correctif règle. ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component.personaType = 'preset';
        component.personaPreset = 'expert-marketing';
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') !== null, 'personaPreset seul (aucun texte tapé) suffit à déclencher une écriture');
        const draft = JSON.parse(localStorage.getItem('cpDraft_v1'));
        assert(draft.params.personaPreset === 'expert-marketing', 'le brouillon écrit contient bien le personaPreset choisi');
    }

    // --- Test 3d : sélection seule - changement de `tone` (menu déroulant étape 3) ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component.tone = 'Académique';
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') !== null, 'un changement de tone seul (sans texte tapé) suffit à déclencher une écriture');
    }

    // --- Test 3e : sélection seule - changement de `technique` (menu déroulant étape 3) ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component.technique = 'few-shot';
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') !== null, 'un changement de technique seul (sans texte tapé) suffit à déclencher une écriture');
    }

    // --- Test 3f : sélection seule - ajout d'un format dans `formats` (cases à cocher étape 3) ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component.formatsSelected = component.formatsSelected.concat(['Liste à puces']);
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') !== null, 'ajouter un format seul (sans texte tapé) suffit à déclencher une écriture');
    }

    // --- Test 3b : un formulaire rempli PUIS revidé purge le brouillon déjà écrit ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component.taskObject = 'une tâche';
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') !== null, 'précondition : un brouillon existe après un champ rempli');
        component.taskObject = '';
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') === null, 'un formulaire revidé purge le brouillon existant (rien à laisser derrière soi)');
    }

    // --- Test 4 : selectedTask et un espace à remplir comptent aussi comme contenu significatif ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component.selectedTask = 'coder';
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') !== null, 'selectedTask seul suffit à déclencher une écriture');
    }
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component.spaces = [{ text: 'sujet', pending: false }];
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') !== null, 'un espace à remplir avec du texte suffit à déclencher une écriture');
    }

    // --- Test 5 : init() restaure le brouillon (champs + étape) quand aucun ?edit=/?remix= ---
    {
        const draft = {
            v: 1,
            savedAt: Date.now() - 1000,
            step: 3,
            params: { taskObject: 'restaurer ceci', selectedTask: 'ecrire', personaType: 'preset', personaPreset: 'expert', verbType: 'preset', verb: 'Rédige', contextInfo: 'contexte restauré' },
        };
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });
        localStorage.setItem('cpDraft_v1', JSON.stringify(draft));
        component.init();

        assert(component.draftRestored === true, 'draftRestored passe à true quand un brouillon valide est repris');
        assert(component.taskObject === 'restaurer ceci', 'init() restaure taskObject depuis le brouillon');
        assert(component.contextInfo === 'contexte restauré', 'init() restaure contextInfo depuis le brouillon');
        assert(component.step === 3, 'init() restaure l\'étape mémorisée dans le brouillon');
    }

    // --- Test 6 : ?edit=ID prime toujours sur le brouillon local ---
    {
        const draft = { v: 1, savedAt: Date.now(), step: 3, params: { taskObject: 'ne doit PAS apparaître' } };
        const component = loadPromptBuilder({ isAuthenticated: false, search: '?edit=abc123' });
        localStorage.setItem('cpDraft_v1', JSON.stringify(draft));
        component._loadDraft();

        assert(component.draftRestored === false, '?edit=ID présent : le brouillon local n\'est pas appliqué');
        assert(component.taskObject === '', '?edit=ID présent : taskObject reste vide (pas de fuite du brouillon)');
        assert(localStorage.getItem('cpDraft_v1') !== null, '?edit=ID présent : le brouillon local n\'est pas purgé par simple présence de ?edit=');
    }

    // --- Test 7 : ?remix=ID prime aussi sur le brouillon local ---
    {
        const draft = { v: 1, savedAt: Date.now(), step: 3, params: { taskObject: 'ne doit PAS apparaître non plus' } };
        const component = loadPromptBuilder({ isAuthenticated: false, search: '?remix=xyz789' });
        localStorage.setItem('cpDraft_v1', JSON.stringify(draft));
        component._loadDraft();

        assert(component.draftRestored === false, '?remix=ID présent : le brouillon local n\'est pas appliqué');
        assert(component.taskObject === '', '?remix=ID présent : taskObject reste vide');
    }

    // --- Test 8 : resetAll() purge cpDraft_v1 AVANT le rechargement ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false });
        component.taskObject = 'à effacer';
        component._saveDraftNow();
        assert(localStorage.getItem('cpDraft_v1') !== null, 'précondition : un brouillon existe avant resetAll()');
        component.resetAll();
        assert(localStorage.getItem('cpDraft_v1') === null, 'resetAll() purge bien cpDraft_v1 (sinon le brouillon reviendrait au prochain chargement)');
    }

    // --- Test 9 : expiration à 24h - un brouillon trop vieux est purgé et ignoré ---
    {
        const oldDraft = { v: 1, savedAt: Date.now() - (25 * 60 * 60 * 1000), step: 2, params: { taskObject: 'périmé' } };
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });
        localStorage.setItem('cpDraft_v1', JSON.stringify(oldDraft));
        component.init();

        assert(component.draftRestored === false, 'un brouillon de plus de 24h n\'est pas restauré');
        assert(component.taskObject === '', 'les champs restent vierges quand le brouillon est périmé');
        assert(localStorage.getItem('cpDraft_v1') === null, 'un brouillon périmé est purgé de localStorage');
    }
    // ... et un brouillon de moins de 24h (ex. 23h) reste valide.
    {
        const freshDraft = { v: 1, savedAt: Date.now() - (23 * 60 * 60 * 1000), step: 2, params: { taskObject: 'encore valide' } };
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });
        localStorage.setItem('cpDraft_v1', JSON.stringify(freshDraft));
        component.init();

        assert(component.draftRestored === true, 'un brouillon de 23h (< 24h) est toujours restauré');
        assert(component.taskObject === 'encore valide', 'les champs sont bien restaurés pour un brouillon encore valide');
    }

    // --- Test 10 : résistance à un contenu corrompu (JSON invalide) ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });
        localStorage.setItem('cpDraft_v1', '{ceci n\'est pas du JSON valide');
        let threw = false;
        try { component.init(); } catch (e) { threw = true; }

        assert(threw === false, 'un brouillon JSON corrompu ne fait jamais planter init()');
        assert(component.draftRestored === false, 'un brouillon corrompu n\'est pas marqué comme restauré');
        assert(localStorage.getItem('cpDraft_v1') === null, 'un brouillon corrompu est purgé de localStorage');
    }

    // --- Test 11 : version inconnue ou params absents -> ignoré et purgé, sans planter ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });
        localStorage.setItem('cpDraft_v1', JSON.stringify({ v: 2, savedAt: Date.now(), step: 2, params: { taskObject: 'x' } }));
        component.init();
        assert(component.draftRestored === false, 'une version de schéma inconnue (v!==1) est ignorée');
        assert(localStorage.getItem('cpDraft_v1') === null, 'un brouillon de version inconnue est purgé');
    }
    {
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });
        localStorage.setItem('cpDraft_v1', JSON.stringify({ v: 1, savedAt: Date.now(), step: 2 })); // params absent
        component.init();
        assert(component.draftRestored === false, 'un brouillon sans `params` est ignoré');
        assert(localStorage.getItem('cpDraft_v1') === null, 'un brouillon sans `params` est purgé');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
