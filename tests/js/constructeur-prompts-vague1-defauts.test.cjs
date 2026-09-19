// tests/js/constructeur-prompts-vague1-defauts.test.cjs
// Garde-fous de non-régression - vague 1 (2026-09-19, storage/app/constructeur-audit/brief-vague-1.md).
//
// DÉFAUT 1 (le plus toxique) : la complétude était EN CASCADE. Remplir l'étape 3 puis cliquer
// "Suivant" faisait ATTERRIR sur l'étape 4 et la marquait step4Visited=true (donc "complétée")
// AVANT qu'un seul de ses champs n'ait été touché - nextStep()/goToStep()/openDiagnosticSection()/
// _applyStepFromHash() armaient toutes ce drapeau au seul fait de NAVIGUER. Fix : step4Visited
// devient step4Touched, armé UNIQUEMENT par markStep4Touched() (appelée par les @change/@input
// délégués sur le conteneur de l'étape 4, Blade) - jamais par une navigation. stepComplete()/
// stepState() distinguent maintenant vide / partiel / complete pour les 4 étapes.
//
// DÉFAUT 2/4 (le plus insidieux) : `length`/`tone` portaient un défaut non vide ("Modéré
// (300-500 mots)"/"Professionnel") pendant que les <select> affichaient "-- Aucune --"/
// "-- Aucun --" - l'utilisateur croyait ne rien imposer et imposait pourtant une contrainte réelle
// dans get prompt()/promptSummary()/feedbackResultat/feedbackTon. Fix : les deux défauts
// deviennent des chaînes vides ('').
//
// DÉFAUT 3 : sans aucune saisie, get prompt() produisait déjà 832 caractères se terminant par
// "Produis maintenant : la demande ci-dessus." - csr conséquence directe du défaut 2/4 (formats/
// longueur/ton par défaut suffisaient à rendre isValid... non, à rendre prompt non vide, MÊME
// SANS isValid). Vérifié en prod le 2026-09-19 (Playwright) avant correctif : promptLen === 832.
//
// Exécute : node tests/js/constructeur-prompts-vague1-defauts.test.cjs (ou npm run test:js)
'use strict';
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
        location: { search: '', hash: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: false, i18n: {} },
        dispatchEvent: () => {},
    };
    // Node 21+ expose un `navigator` global natif en lecture seule (accesseur sans setter) -
    // Object.defineProperty() force le remplacement (même contournement que les tests voisins).
    Object.defineProperty(global, 'navigator', { value: { clipboard: { writeText: () => Promise.resolve() } }, writable: true, configurable: true });
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    global.localStorage = {
        _store: {},
        getItem(k) { return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null; },
        setItem(k, v) { this._store[k] = String(v); },
        removeItem(k) { delete this._store[k]; },
    };
    global.fetch = function () { return Promise.reject(new Error('fetch ne devrait pas être appelé dans ce test')); };

    new Function(src)();
    const component = factory();
    component.$nextTick = function (cb) { cb(); };
    component.customCardsLoaded = true;
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

(function run() {
    // === Défaut 2/4 : plus aucun défaut non vide sur length/tone ===
    {
        const pb = loadPromptBuilder();
        assert(pb.length === '', "défaut 2/4 : `length` est vide à l'état initial (plus de \"Modéré (300-500 mots)\" caché derrière le \"-- Aucune --\" affiché)");
        assert(pb.tone === '', "défaut 2/4 : `tone` est vide à l'état initial (plus de \"Professionnel\" caché derrière le \"-- Aucun --\" affiché)");
    }

    // === Défaut 3 : rien saisi -> aucun prompt généré (état vide honnête) ===
    {
        const pb = loadPromptBuilder();
        assert(pb.prompt === '', 'défaut 3 : sans AUCUNE saisie, get prompt() est une chaîne VIDE (mesuré 832 caractères avant correctif)');
        assert(pb.promptSummary === '', 'défaut 3 : sans AUCUNE saisie, get promptSummary() (« Voici ce qui sera envoyé à l\'IA ») est vide aussi');
        // Découvert en creusant le défaut 3 (2026-09-19) : deux AUTRES lignes "Ajouté : ..." de
        // l'écran 4 fuitaient de la même façon les défauts honnêtes de l'outil (formatsSelected,
        // constraintAntiAI) tant qu'AUCUN contenu réel n'existait encore.
        assert(pb.feedbackResultat === '', 'défaut 3 (fuite additionnelle) : feedbackResultat vide sans AUCUNE saisie (formatText seul ne suffit plus)');
        assert(pb.feedbackLimites === '', 'défaut 3 (fuite additionnelle) : feedbackLimites vide sans AUCUNE saisie (constraintAntiAI coché par défaut ne suffit plus)');
        assert(pb.feedbackTon === '', 'défaut 3 (non-régression) : feedbackTon vide sans AUCUNE saisie');
        assert(pb.isValid === false, "défaut 3 (non-régression) : isValid reste false sans saisie - les boutons de destination restent inactifs");
    }

    // === Défaut 2/4, mesuré en conditions réelles : persona+tâche remplis, longueur/ton
    //     laissés sur "Aucune"/"Aucun" -> le prompt final ne contient NI longueur NI ton ===
    {
        const pb = loadPromptBuilder();
        pb.personaType = 'preset';
        pb.personas = [{ value: 'p1', label: 'Expert en marketing digital' }];
        pb.personaPreset = 'p1';
        pb.verbType = 'preset';
        pb.verb = 'Rédige';
        pb.taskObject = 'un courriel de test';
        assert(pb.isValid === true, '(prérequis du test) persona + verbe + tâche suffisent à isValid');
        assert(pb.prompt.indexOf('Modéré') === -1, 'défaut 2/4 : le prompt final ne contient PAS "Modéré (300-500 mots)" quand `length` est resté vide');
        assert(pb.prompt.indexOf('Professionnel') === -1, 'défaut 2/4 : le prompt final ne contient PAS "Professionnel" quand `tone` est resté vide');
        assert(pb.feedbackResultat.indexOf('longueur') === -1, 'défaut 2/4 : la ligne "Sera inclus..." ne mentionne pas la longueur quand `length` est vide');
        // NB : "ton " en sous-chaîne existe déjà dans le préfixe fixe "Sera inclus dans ton
        // prompt : " - on vérifie donc la valeur EXACTE plutôt qu'une sous-chaîne piégeuse.
        assert(pb.feedbackTon === 'Sera inclus dans ton prompt : rôle « Expert en marketing digital ».', 'défaut 2/4 : la ligne "Sera inclus..." ne mentionne QUE le rôle (réellement saisi), jamais un ton quand `tone` est resté vide');
        // Non-régression : une fois un VRAI contenu présent (rôle+tâche), le défaut honnête de
        // formatsSelected/constraintAntiAI (chip/case déjà visibles dès l'ouverture, jamais masqués
        // derrière "Aucune") continue de s'appliquer - seule la fuite à VIDE a été corrigée.
        assert(pb.prompt.indexOf('Paragraphes détaillés') !== -1, "non-régression : une fois un vrai contenu saisi, le format par défaut (visible en chip dès l'ouverture) reste bien appliqué au prompt réel");
        assert(pb.feedbackLimites.indexOf('écriture naturelle anti-IA') !== -1, "non-régression : idem pour l'écriture naturelle anti-IA (case cochée par défaut, visible)");
        // Round 3 (règle générale) : le repli final n'ancre plus jamais sur "la demande ci-dessus".
        assert(pb.prompt.indexOf('la demande ci-dessus') === -1, "correctif v1.289.6 vérifié : l'ancrage \"la demande ci-dessus\" n'apparaît plus dans un prompt réel");
    }

    // === Défaut 1 : la complétude ne doit JAMAIS retomber sur "complétée" par défaut ===
    {
        const pb = loadPromptBuilder();
        assert(pb.stepState(1) === 'vide', "défaut 1 : étape 1 vide à l'état initial");
        assert(pb.stepState(2) === 'vide', "défaut 1 : étape 2 vide à l'état initial");
        assert(pb.stepState(3) === 'vide', "défaut 1 : étape 3 vide à l'état initial");
        assert(pb.stepState(4) === 'vide', "défaut 1 : étape 4 vide à l'état initial");
        assert(pb.stepComplete(1) === false && pb.stepComplete(2) === false && pb.stepComplete(3) === false && pb.stepComplete(4) === false, 'défaut 1 : AUCUNE étape ne se dit complétée sans aucune saisie');
    }

    // === Défaut 1 : remplir UNE SEULE étape ne change QUE son propre état ===
    {
        const pb = loadPromptBuilder();
        pb.personaType = 'preset';
        pb.personas = [{ value: 'p1', label: 'Expert en marketing digital' }];
        pb.personaPreset = 'p1';
        assert(pb.stepState(1) === 'complete', "défaut 1 : remplir SEULEMENT l'étape 1 (persona) la marque complétée");
        assert(pb.stepState(2) === 'vide', "défaut 1 : ... et ELLE SEULE - l'étape 2 reste vide");
        assert(pb.stepState(3) === 'vide', "défaut 1 : ... l'étape 3 reste vide");
        assert(pb.stepState(4) === 'vide', "défaut 1 : ... l'étape 4 reste vide");
    }

    // === Défaut 1, LE bug de cascade : remplir l'étape 2 puis appeler nextStep() jusqu'à
    //     atteindre l'étape 4 NE DOIT PLUS marquer l'étape 4 complétée (avant : step4Visited
    //     s'armait au simple fait que this.step devienne 4 dans nextStep()). ===
    {
        const pb = loadPromptBuilder();
        pb.personaType = 'preset';
        pb.personas = [{ value: 'p1', label: 'Expert en marketing digital' }];
        pb.personaPreset = 'p1';
        pb.verbType = 'preset';
        pb.verb = 'Rédige';
        pb.taskObject = 'un courriel de test';
        pb.step = 1;
        pb.nextStep(); // -> step 2
        pb.nextStep(); // -> step 3 (étape 3 restée vide, optionnelle)
        pb.nextStep(); // -> step 4 : LE moment du bug mesuré en prod (2026-09-19)
        assert(pb.step === 4, '(prérequis du test) nextStep() a bien amené this.step à 4');
        assert(pb.stepState(4) === 'vide', "défaut 1 (LE bug de cascade) : atterrir sur l'étape 4 via nextStep() SANS y toucher un champ ne la marque PAS complétée");
        assert(pb.stepComplete(4) === false, 'défaut 1 : stepComplete(4) confirme la même chose (rougit si step4Visited/nextStep() est restauré)');
    }

    // === Même bug via goToStep() (clic direct sur l'onglet "Options" du stepper) ===
    {
        const pb = loadPromptBuilder();
        pb.personaType = 'preset';
        pb.personas = [{ value: 'p1', label: 'Expert en marketing digital' }];
        pb.personaPreset = 'p1';
        pb.verbType = 'preset';
        pb.verb = 'Rédige';
        pb.taskObject = 'un courriel de test';
        pb.goToStep(4);
        assert(pb.step === 4, '(prérequis du test) goToStep(4) a bien amené this.step à 4');
        assert(pb.stepState(4) === 'vide', "défaut 1 : goToStep(4) SANS toucher un champ ne marque pas non plus l'étape 4 complétée");
    }

    // === Même bug via openDiagnosticSection() (clic sur un lien du panneau "Vérifications") ===
    {
        const pb = loadPromptBuilder();
        pb.personaType = 'preset';
        pb.personas = [{ value: 'p1', label: 'Expert en marketing digital' }];
        pb.personaPreset = 'p1';
        pb.verbType = 'preset';
        pb.verb = 'Rédige';
        pb.taskObject = 'un courriel de test';
        pb.openDiagnosticSection('format');
        assert(pb.step === 4, '(prérequis du test) openDiagnosticSection("format") amène bien this.step à 4');
        assert(pb.stepState(4) === 'vide', "défaut 1 : openDiagnosticSection() SANS toucher un champ ne marque pas l'étape 4 complétée");
    }

    // === Le SEUL mécanisme qui doit armer la complétude de l'étape 4 : une vraie saisie ===
    {
        const pb = loadPromptBuilder();
        pb.step = 4;
        assert(pb.stepState(4) === 'vide', "(prérequis) l'étape 4 est vide juste après avoir navigué dessus");
        pb.markStep4Touched(); // simule le @change/@input délégué sur le conteneur de l'étape 4
        assert(pb.stepState(4) === 'complete', "défaut 1 : SEULE une vraie modification d'un champ de l'étape 4 (markStep4Touched) la marque complétée");
    }

    // === Trois états, pas deux : "partiel" existe réellement (étapes 1/2/3) ===
    {
        const pb = loadPromptBuilder();
        pb.personaType = 'custom'; // bascule choisie, mais aucun texte encore tapé
        assert(pb.stepState(1) === 'partiel', 'trois états : basculer "Personnalisé" sans texte tapé donne un état "partiel", pas "vide" ni "complete"');

        const pb2 = loadPromptBuilder();
        pb2.verbType = 'preset';
        pb2.verb = 'Rédige'; // un seul des deux champs requis de l'étape 2
        assert(pb2.stepState(2) === 'partiel', "trois états : un seul des deux champs requis de l'étape 2 (verbe sans tâche) donne \"partiel\"");

        const pb3 = loadPromptBuilder();
        pb3.audienceType = 'custom';
        assert(pb3.stepState(3) === 'partiel', 'trois états : basculer "Personnalisée" (audience) sans texte tapé donne "partiel"');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
