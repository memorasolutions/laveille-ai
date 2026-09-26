// tests/js/signature-wizard-stepper.test.cjs
// LOT 5 (2026-09-26) - assistant par étapes de l'éditeur de signature : navigation (goToStep/
// nextStep/prevStep), gate canGoToStep (bloque jusqu'à prénom+nom+courriel valide, débloque
// ensuite - EXACTEMENT les 3 champs `required` de SignatureContentValidator côté serveur, jamais
// un champ de plus) et les 3 états visuels du stepper (stepState).
// Exécute : node tests/js/signature-wizard-stepper.test.cjs
const fs = require('fs');
const path = require('path');

function loadSignatureAssistant(config) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/signature-courriel/signature-core.js'), 'utf8');
    let factory = null;
    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); },
        querySelector: () => ({ content: 'test-csrf-token' }),
        getElementById: () => ({ focus: () => {} }),
        activeElement: null,
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    // signature-core.js enregistre via `if (window.Alpine) { window.Alpine.data(...) }` (jamais
    // `Alpine.data(...)` seul, contrairement au constructeur de prompts) - `window.Alpine` doit
    // donc pointer sur le MÊME stub que `global.Alpine`.
    global.window = { SIGNATURE_TEMPLATES: {}, Alpine: global.Alpine };
    new Function(src)();
    const component = factory(config || {});
    component.$nextTick = function (cb) { cb(); };
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

// 1. hasMinimalIdentity() : faux tant que prénom, nom ou courriel valide manquent.
{
    const c = loadSignatureAssistant();
    assert(c.hasMinimalIdentity() === false, 'identité minimale absente au départ (tout est vide)');
    c.content.first_name = 'Marie';
    assert(c.hasMinimalIdentity() === false, 'prénom seul ne suffit pas');
    c.content.last_name = 'Tremblay';
    assert(c.hasMinimalIdentity() === false, 'prénom + nom sans courriel valide ne suffisent pas');
    c.content.email = 'pas-un-courriel';
    assert(c.hasMinimalIdentity() === false, 'un courriel mal formé ne compte pas comme valide');
    c.content.email = 'marie@example.com';
    assert(c.hasMinimalIdentity() === true, 'prénom + nom + courriel valide suffisent');
}

// 2. canGoToStep() : les étapes 1 et 2 sont TOUJOURS atteignables (c'est là que vivent les champs
//    requis) ; les étapes 3 à 5 exigent l'identité minimale, jamais un champ de plus.
{
    const c = loadSignatureAssistant();
    assert(c.canGoToStep(1) === true, 'étape 1 toujours atteignable');
    assert(c.canGoToStep(2) === true, 'étape 2 toujours atteignable (même vide)');
    assert(c.canGoToStep(3) === false, 'étape 3 bloquée tant que l\'identité minimale manque');
    assert(c.canGoToStep(4) === false, 'étape 4 bloquée tant que l\'identité minimale manque');
    assert(c.canGoToStep(5) === false, 'étape 5 bloquée tant que l\'identité minimale manque');

    c.content.first_name = 'Marie';
    c.content.last_name = 'Tremblay';
    c.content.email = 'marie@example.com';
    assert(c.canGoToStep(3) === true, 'étape 3 débloquée une fois l\'identité minimale saisie');
    assert(c.canGoToStep(4) === true, 'étape 4 débloquée - aucun blocage additionnel au-delà de l\'identité');
    assert(c.canGoToStep(5) === true, 'étape 5 débloquée - aucun blocage additionnel au-delà de l\'identité');
}

// 3. goToStep() : un saut bloqué renvoie à l'étape 2 (là où sont les champs manquants) et arme
//    showStepValidation ; un saut permis change bien l'étape et efface l'avertissement.
{
    const c = loadSignatureAssistant();
    c.step = 1;
    c.goToStep(4);
    assert(c.step === 2, 'saut bloqué : on est renvoyé à l\'étape 2 (jamais laissé sur place en silence)');
    assert(c.showStepValidation === true, 'saut bloqué : l\'avertissement de validation est armé');

    c.content.first_name = 'Marie';
    c.content.last_name = 'Tremblay';
    c.content.email = 'marie@example.com';
    c.goToStep(4);
    assert(c.step === 4, 'saut permis une fois l\'identité minimale saisie');
    assert(c.showStepValidation === false, 'saut permis : l\'avertissement est effacé');
}

// 4. nextStep()/prevStep() : navigation séquentielle, gate identique à goToStep (même chemin,
//    DRY), jamais en dessous de 1 ni au-dessus de 5.
{
    const c = loadSignatureAssistant();
    c.step = 1;
    c.nextStep();
    assert(c.step === 2, 'nextStep() avance de 1 à 2 sans condition');
    c.nextStep();
    assert(c.step === 2 && c.showStepValidation === true, 'nextStep() bloqué de 2 vers 3 sans identité minimale (reste/revient à 2)');

    c.content.first_name = 'Marie';
    c.content.last_name = 'Tremblay';
    c.content.email = 'marie@example.com';
    c.nextStep();
    assert(c.step === 3, 'nextStep() avance de 2 à 3 une fois l\'identité minimale saisie');
    c.nextStep();
    c.nextStep();
    assert(c.step === 5, 'nextStep() atteint la dernière étape (5)');
    c.nextStep();
    assert(c.step === 5, 'nextStep() ne dépasse jamais l\'étape 5');

    c.prevStep();
    assert(c.step === 4, 'prevStep() recule d\'une étape');
    c.step = 1;
    c.prevStep();
    assert(c.step === 1, 'prevStep() ne descend jamais sous l\'étape 1');
}

// 5. stepState() : 3 états (vide/partiel/complete), jamais un simple booléen - étape 2 seule
//    distingue "partiel" (un des trois champs rempli, pas les trois).
{
    const c = loadSignatureAssistant();
    assert(c.stepState(1) === 'complete', 'étape 1 (mise en page) toujours "complete" - un gabarit par défaut est déjà choisi');
    assert(c.stepState(2) === 'vide', 'étape 2 "vide" au départ');
    c.content.first_name = 'Marie';
    assert(c.stepState(2) === 'partiel', 'étape 2 "partiel" avec un seul des 3 champs rempli');
    c.content.last_name = 'Tremblay';
    c.content.email = 'marie@example.com';
    assert(c.stepState(2) === 'complete', 'étape 2 "complete" avec les 3 champs valides');

    assert(c.stepState(3) === 'vide', 'étape 3 (images) "vide" sans aucune image');
    c.images.logo = { url: 'https://example.com/logo.png' };
    assert(c.stepState(3) === 'complete', 'étape 3 "complete" dès qu\'une image existe');

    assert(c.stepState(4) === 'vide', 'étape 4 (style et liens) "vide" tant que rien n\'a été personnalisé');
    c.content.cta_text = 'Réserver un appel';
    assert(c.stepState(4) === 'complete', 'étape 4 "complete" dès qu\'un champ optionnel est renseigné');

    assert(c.stepState(5) === 'vide', 'étape 5 (finaliser) toujours "vide" - pas de notion de complétion, seulement des actions');
}

// 6. Aperçu mobile collant : le focus d'un vrai champ réduit la bande, en sortir la ré-agrandit.
{
    const c = loadSignatureAssistant();
    assert(c.mobilePreviewCollapsed === false, 'bande dépliée par défaut');
    c.onWizardFieldFocusIn({ target: { matches: (sel) => sel === 'input,select,textarea' } });
    assert(c.mobilePreviewCollapsed === true, 'le focus d\'un champ réduit la bande à une poignée');
    c.onWizardFieldFocusOut({ relatedTarget: null });
    assert(c.mobilePreviewCollapsed === false, 'quitter la zone de saisie (aucun autre champ ciblé) ré-agrandit la bande');

    c.onWizardFieldFocusIn({ target: { matches: (sel) => sel === 'input,select,textarea' } });
    c.onWizardFieldFocusOut({ relatedTarget: { matches: (sel) => sel === 'input,select,textarea' } });
    assert(c.mobilePreviewCollapsed === true, 'passer d\'un champ à un autre champ garde la bande réduite');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail === 0 ? 0 : 1);
