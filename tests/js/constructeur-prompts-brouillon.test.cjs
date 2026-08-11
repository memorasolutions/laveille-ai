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
        location: { search: opts.search || '', hash: '', pathname: '/outils/constructeur-prompts', reload: () => {} },
        history: { replaceState: () => {} },
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

    // --- Test 12 : propagation RÉELLE au <select> après restauration (2e défaut de production,
    //     2026-08-11). Les tests 5/9/11 ci-dessus ne vérifient QUE l'état interne Alpine
    //     (component.personaPreset === ...) - insuffisant : c'est exactement ce qui restait correct
    //     en production pendant que le <select> à l'écran restait bloqué sur
    //     "-- Sélectionnez un rôle --". Un <select x-model="personaPreset"> dont les <option> sont
    //     peuplées par <template x-for="p in personas"> (constructeur-prompts.blade.php ~L373-377)
    //     n'accepte une valeur que si l'<option> correspondante existe DÉJÀ dans le DOM - Alpine
    //     initialise x-model sur un noeud AVANT de descendre dans ses enfants, donc AVANT que x-for
    //     ait inséré ses <option>. Ce test rejoue cet ordre avec un <select> natif minimal (fakeSelect,
    //     n'accepte une valeur que si elle est dans `options`) et un effet x-model qui ne se
    //     redéclenche QUE si personaPreset change RÉELLEMENT depuis le dernier passage (comme la
    //     réactivité Alpine/Vue - un set sans changement ne redéclenche rien). Le harnais par défaut
    //     (loadPromptBuilder(), $nextTick synchrone ligne ~54) est volontairement remplacé ici par une
    //     file d'attente : c'est la seule façon de distinguer "affecté avant la fin du walk DOM" (bug)
    //     de "affecté après" (correctif), donc de représenter la propagation plutôt que le seul état
    //     interne. Contre l'ANCIEN code (affectation synchrone dans _loadDraft(), sans $nextTick), ce
    //     test échoue sur fakeSelect.value - preuve qu'il détecte bien le défaut signalé. ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });

        // <select> natif minimal : n'accepte une valeur que si une <option> correspondante existe
        // déjà (comme le vrai DOM), sinon l'ignore silencieusement et garde sa valeur précédente.
        const fakeSelect = { value: '', options: [] };
        function xModelEffect() {
            var v = component.personaPreset;
            if (v === '' || fakeSelect.options.indexOf(v) !== -1) fakeSelect.value = v;
        }
        var lastSeenValue;
        function registerXModel() { lastSeenValue = component.personaPreset; xModelEffect(); }
        function rerunIfChanged() {
            if (component.personaPreset !== lastSeenValue) { lastSeenValue = component.personaPreset; xModelEffect(); }
        }

        let nextTickQueue = [];
        component.$nextTick = function (cb) { nextTickQueue.push(cb); };

        const draft = { v: 1, savedAt: Date.now(), step: 1, params: { personaType: 'preset', personaPreset: 'expert-marketing' } };
        localStorage.setItem('cpDraft_v1', JSON.stringify(draft));

        // (1) init() : c'est ICI, dans le vrai Alpine, que _loadDraft() tourne - avant tout walk DOM.
        component.init();

        // (2) walk Alpine simulé du <select> : x-model s'initialise AVANT que x-for peuple les
        //     <option> (ordre réel confirmé dans le code source Alpine - les directives d'un noeud
        //     s'exécutent avant la descente dans ses enfants).
        registerXModel();
        fakeSelect.options.push('expert-marketing'); // x-for peuple les <option>, juste après

        // (3) $nextTick : le walk initial complet (x-for compris) est terminé.
        nextTickQueue.forEach(function (cb) { cb(); });
        rerunIfChanged(); // ne fait rien si personaPreset n'a pas changé depuis (2)

        assert(component.personaPreset === 'expert-marketing', 'l\'état interne Alpine contient bien la valeur restaurée');
        assert(fakeSelect.value === 'expert-marketing', 'le <select> DOM affiche RÉELLEMENT la valeur restaurée (pas seulement l\'état interne) - détecte le 2e défaut de production');
    }

    // --- Test 13 : « Recommencer » doit VRAIMENT remettre a zero (3e defaut de production) ---
    // Le clic purgeait la cle, mais (a) l'anti-rebond la reecrivait aussitot et (b) l'ancienne
    // navigation ne rechargeait pas la page quand l'URL portait un fragment #etape-N.
    {
        const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
        const resetBody = src.slice(src.indexOf('resetAll: function'), src.indexOf('addToHistory: function'));

        assert(/_draftDisabled\s*=\s*true/.test(resetBody), 'resetAll() arme le verrou _draftDisabled (empeche toute reecriture avant le rechargement)');
        assert(/clearTimeout\(this\._draftSaveTimer\)/.test(resetBody), 'resetAll() desarme le minuteur anti-rebond en attente');
        assert(/removeItem\(this\._draftKey\)/.test(resetBody), 'resetAll() purge bien la cle du brouillon');
        assert(/window\.location\.reload\(\)/.test(resetBody), 'resetAll() force un VRAI rechargement (reload), jamais une simple reaffectation de href');
        assert(!/location\.href\s*=\s*window\.location\.pathname/.test(resetBody), 'resetAll() n\'utilise plus location.href = pathname (inoperant depuis un fragment #etape-N)');

        const saveNow = src.slice(src.indexOf('_saveDraftNow: function'), src.indexOf('_loadDraft: function'));
        const schedule = src.slice(src.indexOf('_scheduleDraftSave: function'), src.indexOf('_saveDraftNow: function'));
        assert(/if\s*\(this\._draftDisabled\)\s*return;/.test(saveNow), '_saveDraftNow() honore le verrou');
        assert(/if\s*\(this\._draftDisabled\)\s*return;/.test(schedule), '_scheduleDraftSave() honore le verrou');
    }

    // --- Test 14 : #etape-N survit au rafraichissement quand un brouillon le permet ---
    // Regression v1.164.2 -> v1.164.4 signalee par Stephane : sur .../#etape-2, rafraichir
    // renvoyait a l'etape 1. Cause : _applyStepFromHash() s'appuie sur canGoToStep(), qui lit
    // personaText - or depuis le report en $nextTick, les champs du brouillon n'etaient pas
    // encore appliques au moment de l'appel synchrone d'init(). Ce test rejoue la sequence
    // reelle (appel synchrone AVANT les champs, puis $nextTick).
    {
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });
        // canGoToStep(2) exige personaText, un getter qui resout personaPreset via la liste
        // `personas` (vide dans ce harnais par defaut) - on la peuple donc comme en production,
        // sinon le test echouerait pour une raison etrangere a ce qu'il mesure.
        component.personas = [{ value: 'enseignant', label: 'Enseignant pedagogue' }];
        global.window.location.hash = '#etape-2';

        let nextTickQueue = [];
        component.$nextTick = function (cb) { nextTickQueue.push(cb); };

        const draft = { v: 1, savedAt: Date.now(), step: 1, params: { personaType: 'preset', personaPreset: 'enseignant' } };
        localStorage.setItem('cpDraft_v1', JSON.stringify(draft));

        component._loadDraft();
        component._applyStepFromHash();   // appel synchrone d'init() : champs encore vierges

        assert(component.step === 1, 'avant le $nextTick, l\'etape ne peut pas encore etre appliquee (formulaire vierge) - etat intermediaire attendu');

        nextTickQueue.forEach(function (cb) { cb(); }); // champs restaures PUIS 2e tentative

        assert(component.personaPreset === 'enseignant', 'les champs du brouillon sont bien restaures');
        assert(component.step === 2, '#etape-2 est applique APRES la restauration des champs (sans ce rattrapage, retour a l\'etape 1 a chaque rafraichissement)');
        global.window.location.hash = '';
    }

    // --- Test 15 : le fragment ne permet jamais un saut d'etape non merite ---
    {
        const component = loadPromptBuilder({ isAuthenticated: false, search: '' });
        global.window.location.hash = '#etape-4';
        component.$nextTick = function (cb) { cb(); };
        component._loadDraft();          // aucun brouillon : rien a restaurer
        component._applyStepFromHash();
        assert(component.step === 1, 'formulaire vierge + #etape-4 : reste a l\'etape 1 (aucun saut arbitraire, regle de la tache #1699 preservee)');
        global.window.location.hash = '';
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
