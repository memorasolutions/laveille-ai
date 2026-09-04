// tests/js/constructeur-prompts-wizard-params-roundtrip.test.cjs
// Ticket #2239 (2026-09-04). 6 champs de la deuxième tâche et des contraintes avancées
// (secondTaskEnabled, verbType2, verb2, verbCustom2, constraintForceQcm, constraintRepeatList)
// influencent bel et bien le texte final généré (get prompt(), voir L.888, L.894, L.1286-1287,
// L.1311, L.1452, L.1615, L.1648, L.1652) mais manquaient aux DEUX bouts du round-trip serveur :
// absents du getter wizardParams (jamais envoyés à la sauvegarde) ET jamais relus dans
// _applyWizardParams (jamais restaurés à la réouverture ?edit=ID / ?remix=ID / historique invité).
// Même piège que le round 42 documenté juste au-dessus du getter wizardParams dans le fichier
// source : un réglage silencieusement réinitialisé à la réouverture, puis écrasé en base par
// l'"Enregistrer" suivant (perte de donnée permanente et silencieuse).
// Execute : node tests/js/constructeur-prompts-wizard-params-roundtrip.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder() {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;
    global.document = { addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); } };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: false, i18n: {} },
        dispatchEvent: () => {},
        open: () => {},
        toast: () => {},
    };
    global.navigator.clipboard = { writeText: (text) => Promise.resolve() };
    global.window.copyToClipboard = function (text) { return navigator.clipboard.writeText(text); };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    new Function(src)();
    return factory();
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

// (a) Aller : les 6 champs, une fois renseignés dans l'état, apparaissent dans l'objet retourné
// par wizardParams avec leur valeur exacte.
{
    const component = loadPromptBuilder();
    component.secondTaskEnabled = true;
    component.verbType2 = 'custom';
    component.verb2 = 'Critique';
    component.verbCustom2 = 'Traduis en anglais';
    component.constraintForceQcm = true;
    component.constraintRepeatList = true;

    const p = component.wizardParams;
    assert(p.secondTaskEnabled === true, '#2239 aller : wizardParams inclut secondTaskEnabled');
    assert(p.verbType2 === 'custom', '#2239 aller : wizardParams inclut verbType2');
    assert(p.verb2 === 'Critique', '#2239 aller : wizardParams inclut verb2');
    assert(p.verbCustom2 === 'Traduis en anglais', '#2239 aller : wizardParams inclut verbCustom2');
    assert(p.constraintForceQcm === true, '#2239 aller : wizardParams inclut constraintForceQcm');
    assert(p.constraintRepeatList === true, '#2239 aller : wizardParams inclut constraintRepeatList');
}

// (a bis) Aller, valeurs par défaut : une deuxième tâche jamais activée sérialise quand même ses
// 6 champs (à leurs valeurs par défaut), au lieu d'être absente de l'objet.
{
    const component = loadPromptBuilder();
    const p = component.wizardParams;
    assert(p.secondTaskEnabled === false, '#2239 aller : wizardParams inclut secondTaskEnabled même resté à sa valeur par défaut (false)');
    assert(p.verbType2 === 'preset', '#2239 aller : wizardParams inclut verbType2 même resté à sa valeur par défaut (preset)');
    assert(p.constraintForceQcm === false, '#2239 aller : wizardParams inclut constraintForceQcm même resté à sa valeur par défaut (false)');
    assert(p.constraintRepeatList === false, '#2239 aller : wizardParams inclut constraintRepeatList même resté à sa valeur par défaut (false)');
}

// (b) Retour : _applyWizardParams avec un objet contenant ces 6 clés les restitue bien dans l'état.
// Pré-état volontairement DIFFÉRENT de "saved" ci-dessous (y compris verbType2 à l'opposé de sa
// valeur par défaut 'preset') : si _applyWizardParams ne lit pas une clé, l'assertion le détecte
// (la valeur resterait au pré-état au lieu de basculer vers "saved") - une assertion qui se
// contenterait de rejouer la valeur par défaut ne prouverait rien.
{
    const component = loadPromptBuilder();
    component.secondTaskEnabled = false;
    component.verbType2 = 'preset';
    component.verb2 = 'Ancien verbe';
    component.verbCustom2 = 'Ancien verbe personnalisé';
    component.constraintForceQcm = false;
    component.constraintRepeatList = false;

    const saved = {
        secondTaskEnabled: true,
        verbType2: 'custom',
        verb2: 'Critique',
        verbCustom2: 'Traduis en anglais',
        constraintForceQcm: true,
        constraintRepeatList: true,
    };
    component._applyWizardParams(saved, { legacy: false });

    assert(component.secondTaskEnabled === true, '#2239 retour : _applyWizardParams restaure secondTaskEnabled');
    assert(component.verbType2 === 'custom', '#2239 retour : _applyWizardParams restaure verbType2');
    assert(component.verb2 === 'Critique', '#2239 retour : _applyWizardParams restaure verb2');
    assert(component.verbCustom2 === 'Traduis en anglais', '#2239 retour : _applyWizardParams restaure verbCustom2');
    assert(component.constraintForceQcm === true, '#2239 retour : _applyWizardParams restaure constraintForceQcm');
    assert(component.constraintRepeatList === true, '#2239 retour : _applyWizardParams restaure constraintRepeatList');
}

// (b bis) Retour, miroir de verbCustom/verbType : un verbCustom2 rempli restaure verbCustom2 ET
// force verbType2 à 'custom' en mode legacy, exactement comme verbCustom le fait pour verbType
// (même bloc à trois lignes, voir _applyWizardParams juste après la restauration de verbCustom).
{
    const component = loadPromptBuilder();
    component.verbType2 = 'preset';
    component._applyWizardParams({ verbCustom2: 'Simplifie le texte' }, { legacy: true });
    assert(component.verbCustom2 === 'Simplifie le texte', '#2239 retour : _applyWizardParams restaure verbCustom2');
    assert(component.verbType2 === 'custom', "#2239 retour : un verbCustom2 rempli force verbType2 sur 'custom' en mode legacy (miroir exact de verbCustom/verbType)");
}

// (c) Non-régression legacy : un ancien prompt sauvegardé AVANT ce correctif n'a aucune des 6
// clés. _applyWizardParams (appelée avec { legacy: true } pour ?edit=ID / ?remix=ID) ne doit RIEN
// écraser pour lui : une clé absente laisse la valeur par défaut, jamais un écrasement par
// undefined.
{
    const component = loadPromptBuilder();
    const before = {
        secondTaskEnabled: component.secondTaskEnabled,
        verbType2: component.verbType2,
        verb2: component.verb2,
        verbCustom2: component.verbCustom2,
        constraintForceQcm: component.constraintForceQcm,
        constraintRepeatList: component.constraintRepeatList,
    };
    const legacyPayloadSansLes6Champs = {
        selectedTask: 'redaction',
        verbType: 'preset',
        verb: 'Résume',
        taskObject: 'cet article',
    };
    component._applyWizardParams(legacyPayloadSansLes6Champs, { legacy: true });

    assert(component.secondTaskEnabled === before.secondTaskEnabled, '#2239 legacy : un ancien prompt sans secondTaskEnabled ne change pas la valeur par défaut');
    assert(component.verbType2 === before.verbType2, '#2239 legacy : un ancien prompt sans verbType2 ne change pas la valeur par défaut');
    assert(component.verb2 === before.verb2, '#2239 legacy : un ancien prompt sans verb2 ne change pas la valeur par défaut');
    assert(component.verbCustom2 === before.verbCustom2, '#2239 legacy : un ancien prompt sans verbCustom2 ne change pas la valeur par défaut');
    assert(component.constraintForceQcm === before.constraintForceQcm, '#2239 legacy : un ancien prompt sans constraintForceQcm ne change pas la valeur par défaut');
    assert(component.constraintRepeatList === before.constraintRepeatList, '#2239 legacy : un ancien prompt sans constraintRepeatList ne change pas la valeur par défaut');
}

// (d) Round-trip complet : ce que wizardParams sérialise, _applyWizardParams le restitue à
// l'identique (le chemin réel emprunté par ?edit=ID : sauvegarde → réouverture).
{
    const original = loadPromptBuilder();
    original.secondTaskEnabled = true;
    original.verbType2 = 'custom';
    original.verb2 = '';
    original.verbCustom2 = 'Reformule pour un public expert';
    original.constraintForceQcm = true;
    original.constraintRepeatList = false;
    const serialized = original.wizardParams;

    const reopened = loadPromptBuilder();
    reopened._applyWizardParams(serialized, { legacy: false });

    assert(reopened.secondTaskEnabled === true, '#2239 round-trip : secondTaskEnabled survit à un aller-retour complet');
    assert(reopened.verbType2 === 'custom', '#2239 round-trip : verbType2 survit à un aller-retour complet');
    assert(reopened.verbCustom2 === 'Reformule pour un public expert', '#2239 round-trip : verbCustom2 survit à un aller-retour complet');
    assert(reopened.constraintForceQcm === true, '#2239 round-trip : constraintForceQcm survit à un aller-retour complet');
    assert(reopened.constraintRepeatList === false, '#2239 round-trip : constraintRepeatList (resté à false) survit à un aller-retour complet');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
