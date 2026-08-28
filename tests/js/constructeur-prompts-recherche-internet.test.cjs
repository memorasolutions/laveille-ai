// tests/js/constructeur-prompts-recherche-internet.test.cjs
// Garde-fou de non-régression (tâche 2026-08-12, 3 verbes de recherche + date + zones
// géographiques) :
//   1. La phrase de date apparaît pour les verbes 2/3 (recherche Internet), jamais pour le verbe 1
//      seul ("Recherche") ni pour un verbe non lié à la recherche.
//   2. La date n'est JAMAIS figée dans l'état sauvegardé : un prompt sauvegardé un jour X et
//      rouvert (_applyWizardParams) un jour Y affiche la date Y, pas la date X (piège du brouillon
//      / prompt sauvegardé à durée indéfinie).
//   3. Le champ Zones (isSearchVerbActive) s'active pour les 3 verbes de recherche, y compris le
//      verbe 1 seul (qui ne déclenche pas la date), et jamais pour un verbe personnalisé ni un
//      verbe non lié à la recherche.
//   4. Dédoublonnage des zones insensible à la casse et aux accents, libellé affiché conservé tel
//      que saisi en premier.
//   5. Phrase de zone unique vs phrase multi-zones en sections distinctes.
//   6. Découpage automatique au collage (virgule/point-virgule), jamais lors d'un ajout manuel.
//   7. Plafond de 5 zones, message d'atteinte de plafond.
// Exécute : node tests/js/constructeur-prompts-recherche-internet.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptBuilder(todayCfg) {
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
            today: todayCfg || { long: '12 août 2026', iso: '2026-08-12' },
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

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

const VERB_PLAIN = 'Recherche';
const VERB_WEB = 'Recherche sur Internet, en priorisant les sites officiels et pertinents';
const VERB_DEEP = 'Recherche en profondeur, Internet inclus';

// === 1. Phrase de date : verbes 2/3 seulement ===
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_WEB; c.taskObject = 'les tendances RH 2026';
    assert(c.prompt.includes('Nous sommes le 12 août 2026 (2026-08-12).'), 'la phrase de date apparaît pour le verbe "Recherche sur Internet..."');
    assert(c.prompt.includes('Utilise les informations les plus récentes disponibles à cette date'), 'la consigne de fraîcheur accompagne la date');
}
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_DEEP; c.taskObject = 'le marché de l\'IA en éducation';
    assert(c.prompt.includes('Nous sommes le 12 août 2026 (2026-08-12).'), 'la phrase de date apparaît pour le verbe "Recherche en profondeur..."');
}
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_PLAIN; c.taskObject = 'les meilleures pratiques Loi 25';
    assert(!c.prompt.includes('Nous sommes le'), 'aucune phrase de date pour le verbe 1 "Recherche" seul');
}
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = 'Rédige'; c.taskObject = 'un courriel';
    assert(!c.prompt.includes('Nous sommes le'), 'aucune phrase de date pour un verbe non lié à la recherche');
}
// verbe custom identique au texte du verbe de recherche : jamais reconnu comme verbe de recherche.
{
    const c = loadPromptBuilder();
    c.verbType = 'custom'; c.verbCustom = VERB_WEB; c.taskObject = 'un sujet';
    assert(!c.prompt.includes('Nous sommes le'), 'un verbe personnalisé identique au texte du verbe de recherche ne déclenche pas la date (mode Prédéfini requis)');
    assert(c.isSearchVerbActive === false, 'isSearchVerbActive reste faux en mode personnalisé');
}
// 2e tâche : le verbe 2 déclenche la date même si le verbe 1 n'est pas un verbe de recherche.
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = 'Rédige'; c.taskObject = 'un rapport';
    c.secondTaskEnabled = true; c.verbType2 = 'preset'; c.verb2 = VERB_DEEP;
    assert(c.isDatedSearchVerbActive === true, 'le verbe de la 2e tâche déclenche aussi isDatedSearchVerbActive');
    assert(c.prompt.includes('Nous sommes le 12 août 2026'), 'la phrase de date apparaît quand seul le verbe 2 est un verbe de recherche daté');
}

// === 2. La date n'est jamais figée dans l'état sauvegardé (piège brouillon / ?edit=) ===
{
    const cOld = loadPromptBuilder({ long: '10 janvier 2026', iso: '2026-01-10' });
    cOld.verbType = 'preset'; cOld.verb = VERB_WEB; cOld.taskObject = 'les subventions PME';
    const savedParams = cOld.wizardParams;
    assert(!('today' in savedParams) && JSON.stringify(savedParams).indexOf('2026-01-10') === -1, 'wizardParams ne contient jamais la date figée');
    assert(savedParams.verb === VERB_WEB, 'wizardParams conserve le libellé statique du verbe, jamais une phrase datée');

    // Réouverture un autre jour (nouvelle page = nouvelle config serveur) : la date DOIT changer.
    const cReopened = loadPromptBuilder({ long: '12 août 2026', iso: '2026-08-12' });
    cReopened._applyWizardParams(savedParams, { legacy: false });
    assert(cReopened.prompt.includes('Nous sommes le 12 août 2026 (2026-08-12).'), 'le prompt rouvert affiche la date COURANTE (12 août 2026), pas la date de sauvegarde');
    assert(!cReopened.prompt.includes('10 janvier 2026'), 'le prompt rouvert ne contient jamais l\'ancienne date figée');
}

// === 3. isSearchVerbActive (zones) : les 3 verbes, y compris le verbe 1 seul ===
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_PLAIN;
    assert(c.isSearchVerbActive === true, 'le verbe 1 "Recherche" seul active isSearchVerbActive');
    assert(c.isDatedSearchVerbActive === false, 'mais pas isDatedSearchVerbActive (pas de date pour ce verbe)');
}
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = 'Analyse';
    assert(c.isSearchVerbActive === false, 'un verbe non lié à la recherche laisse isSearchVerbActive faux');
}

// === 4. Dédoublonnage insensible à la casse et aux accents, libellé conservé ===
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_WEB;
    c.zoneInput = 'Québec'; c.addZoneFromInput();
    c.zoneInput = 'québec'; c.addZoneFromInput();
    c.zoneInput = '  QUÉBEC  '; c.addZoneFromInput();
    c.zoneInput = 'quebec'; c.addZoneFromInput(); // sans accent
    assert(c.zones.length === 1, 'les 4 variantes de casse/accents/espaces de "Québec" ne créent qu\'UNE seule zone');
    assert(c.zones[0] === 'Québec', 'le libellé affiché reste EXACTEMENT celui saisi en premier ("Québec")');
}

// === 5. Phrase unique vs multi-zones en sections distinctes ===
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_PLAIN; c.taskObject = 'les tendances EdTech';
    c.zoneInput = 'Québec'; c.addZoneFromInput();
    assert(c.prompt.includes('Concentre ta recherche sur : Québec.'), 'une seule zone produit la phrase courte "Concentre ta recherche sur"');
}
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_PLAIN; c.taskObject = 'les tendances EdTech';
    c.zoneInput = 'Québec'; c.addZoneFromInput();
    c.zoneInput = 'France'; c.addZoneFromInput();
    c.zoneInput = 'Belgique'; c.addZoneFromInput();
    const p = c.prompt;
    assert(p.includes('Couvre les zones suivantes dans des sections distinctes : Québec, France, Belgique.'), 'plusieurs zones produisent la phrase "sections distinctes" avec les 3 zones dans l\'ordre');
    assert(p.includes('Pour chacune, adapte le contenu à ses spécificités locales.'), 'la consigne d\'adaptation par zone est présente');
    assert(!p.includes('Concentre ta recherche sur'), 'la phrase à une seule zone n\'apparaît jamais en multi-zones');
}
// Zones renseignées mais verbe non-recherche : jamais injectées dans le prompt.
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = 'Rédige'; c.taskObject = 'un texte';
    c.zoneInput = 'Québec'; c.addZoneFromInput();
    assert(!c.prompt.includes('Québec'), 'des zones renseignées ne sont jamais injectées si le verbe n\'est pas un verbe de recherche');
}

// === 6. Découpage automatique au collage, jamais lors d'un ajout manuel ===
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_PLAIN;
    // Ajout MANUEL d'un nom contenant une virgule légitime : jamais découpé.
    c.zoneInput = 'Washington, D.C.'; c.addZoneFromInput();
    assert(c.zones.length === 1 && c.zones[0] === 'Washington, D.C.', 'un ajout manuel (bouton/Entrée) ne découpe jamais sur la virgule, même dans un nom qui en contient une');
}
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_PLAIN;
    let prevented = false;
    const fakeEvent = { clipboardData: { getData: () => 'Québec, France; Belgique' }, preventDefault: () => { prevented = true; } };
    c.handleZonePaste(fakeEvent);
    assert(prevented === true, 'le collage d\'une liste appelle preventDefault (le découpage remplace le comportement par défaut)');
    assert(c.zones.length === 3 && c.zones[0] === 'Québec' && c.zones[1] === 'France' && c.zones[2] === 'Belgique', 'le collage découpe correctement sur virgule ET point-virgule');
}
{
    const c = loadPromptBuilder();
    let prevented = false;
    const fakeEvent = { clipboardData: { getData: () => 'Québec' }, preventDefault: () => { prevented = true; } };
    c.handleZonePaste(fakeEvent);
    assert(prevented === false, 'le collage d\'une seule valeur (sans séparateur) n\'intercepte jamais le comportement natif du champ');
}

// === 7. Plafond de 5 zones + message d'atteinte ===
{
    const c = loadPromptBuilder();
    ['Québec', 'France', 'Belgique', 'Suisse', 'Maroc', 'Sénégal'].forEach(function (z) {
        c.zoneInput = z; c.addZoneFromInput();
    });
    assert(c.zones.length === 5, 'le plafond de 5 zones est respecté (la 6e est refusée)');
    assert(c.zoneLimitMessage === true, 'le message d\'atteinte de plafond est activé');
    assert(c.zones.indexOf('Sénégal') === -1, 'la zone excédentaire n\'est jamais ajoutée');
    c.removeZone(0);
    assert(c.zoneLimitMessage === false, 'retirer une zone alors qu\'on était au plafond réarme la possibilité d\'en ajouter (message effacé)');
}

// === 8. promptSummary (aperçu langage clair) applique le MÊME séparateur verbe/objet que le
// prompt réel (défaut mesuré au navigateur, 2026-08-28) : les deux consommaient la même règle à
// deux endroits distincts du code, un seul avait reçu le correctif du 2026-08-12 - voir
// _verbObjectSeparator() dans core.js, désormais seule source de cette règle. Sans lui, l'aperçu
// produisait "...pertinents les meilleures pratiques..." collés sans ponctuation, alors que le
// prompt réel disait déjà ". Voici ce qu'il faut trouver : ...".
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = VERB_WEB; c.taskObject = 'les meilleures pratiques 2026 pour sécuriser un poste de travail';
    assert(c.promptSummary.indexOf('pertinents les meilleures pratiques') === -1, 'le résumé en langage clair ne colle jamais verbe et objet sans ponctuation pour un verbe de recherche daté');
    assert(c.promptSummary.indexOf('pertinents. Voici ce qu\'il faut trouver : les meilleures pratiques') !== -1, 'le résumé applique le même séparateur explicite que le prompt réel envoyé à l\'IA');
    assert(c.prompt.indexOf('pertinents. Voici ce qu\'il faut trouver : les meilleures pratiques') !== -1, 'le prompt réel garde le même texte (non-régression du correctif du 2026-08-12)');
}
{
    const c = loadPromptBuilder();
    c.verbType = 'preset'; c.verb = 'Rédige'; c.taskObject = 'un courriel de bienvenue';
    assert(c.promptSummary.indexOf('Rédige un courriel de bienvenue') !== -1, 'un verbe ordinaire garde le simple espace dans le résumé (comportement inchangé)');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
