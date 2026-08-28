// tests/js/constructeur-prompts-gabarits-v2.test.cjs
// Garde-fou de non-régression - gabarits v2 du prompt généré (tâche 1653, panel multi-IA
// 2026-08-07, spec-gabarits-v2.md). Couvre les points nommés au §Tests de la spec qui n'ont pas
// de fichier de test dédié existant :
//   1. VERROU chaîne de pensée (G6e/G9b) : les 2 réglages actifs -> une seule instruction émise.
//   2. Clôture conditionnelle selon constraintAskIfUnclear (G10).
//   3. Ancrage final avec rappel du livrable, tronqué à ~80 caractères (G10).
//   4. Critères de réussite (G7), remplaçant l'ancienne checklist "Avant de finaliser".
//   5. Longueur désambiguïsée quand la deuxième tâche est active (G5).
// Le contexte balisé """ (G3) est couvert par tests/js/constructeur-prompts-contextinfo.test.cjs
// (assertion adaptée au nouveau gabarit dans ce même chantier).
// Exécute : node tests/js/constructeur-prompts-gabarits-v2.test.cjs (ou npm run test:js)
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
    global.navigator = global.navigator || {};
    global.navigator.clipboard = { writeText: (text) => Promise.resolve() };
    global.window.copyToClipboard = function (text) { return navigator.clipboard.writeText(text); };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    new Function(src)();
    return factory();
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }

function baseComponent() {
    const c = loadPromptBuilder();
    c.personaType = 'preset';
    c.personaPreset = 'redacteur_web';
    c.personas = [{ value: 'redacteur_web', label: 'Rédacteur web' }];
    c.verbType = 'preset';
    c.verb = 'Rédige';
    c.taskObject = 'un courriel de bienvenue';
    return c;
}

// --- 1. VERROU chaîne de pensée : les deux réglages actifs -> une seule instruction. ---
{
    const c = baseComponent();
    c.constraintChainOfThought = true;
    c.technique = 'zero-shot-cot';
    const prompt = c.prompt;
    assert(prompt.includes('Réfléchis étape par étape et montre ton raisonnement avant ta réponse finale.'), 'les 2 réglages actifs -> instruction fusionnée présente');
    assert(!prompt.includes('Montre ton raisonnement complet étape par étape avant de formuler ta réponse finale.'), 'la ligne de contrainte seule ne double pas la fusionnée');
    assert(!prompt.includes('Avant de répondre, réfléchis étape par étape à ta stratégie'), 'la ligne de la technique 9b est coupée par le verrou');
    const occurrences = (prompt.match(/étape par étape/g) || []).length;
    assert(occurrences === 1, 'une seule occurrence de "étape par étape" dans tout le prompt (pas de doublon)');
}

// --- 1b. Chaque réglage seul garde son comportement d'origine (verrou non déclenché). ---
{
    const c = baseComponent();
    c.constraintChainOfThought = true;
    // technique reste 'zero-shot' (défaut) : pas de zero-shot-cot.
    assert(c.prompt.includes('Montre ton raisonnement complet étape par étape avant de formuler ta réponse finale.'), 'constraintChainOfThought seul conserve son texte original');
}
{
    const c = baseComponent();
    c.technique = 'zero-shot-cot';
    // constraintChainOfThought reste false (défaut).
    assert(c.prompt.includes('Avant de répondre, réfléchis étape par étape à ta stratégie'), 'technique zero-shot-cot seule conserve son texte original');
}

// --- 2. Clôture conditionnelle selon constraintAskIfUnclear. ---
{
    const c = baseComponent();
    const prompt = c.prompt;
    assert(prompt.includes('Produis maintenant : rédige un courriel de bienvenue.'), 'clôture normale : "Produis maintenant" inconditionnel avec le livrable');
    assert(!prompt.includes('Sinon, pose d\'abord tes questions'), 'pas de clôture conditionnelle quand askIfUnclear est désactivé');
}
{
    const c = baseComponent();
    c.constraintAskIfUnclear = true;
    const prompt = c.prompt;
    assert(prompt.includes('Si tout est clair, produis maintenant : rédige un courriel de bienvenue. Sinon, pose d\'abord tes questions de clarification, groupées en un seul message.'), 'clôture conditionnelle complète quand askIfUnclear est actif');
    assert(!prompt.includes('Produis maintenant : '), 'la forme inconditionnelle "Produis maintenant : " n\'apparaît plus telle quelle');
}

// --- 3. Ancrage avec livrable tronqué à ~80 caractères, coupé au dernier mot entier. ---
{
    const c = baseComponent();
    // Ce texte NE commence PAS par le verbe "Rédige" -> _taskWithoutLeadingVerb() le renvoie
    // intact (141 caractères), ce qui force la troncature à s'exercer réellement.
    c.taskObject = 'rédiger un courriel de bienvenue très détaillé pour accueillir chaleureusement chaque nouvel employé de notre entreprise dès son premier jour';
    const prompt = c.prompt;
    // Valeur calculée indépendamment (règle : coupe à 80 caractères, recule jusqu'au dernier
    // espace, ajoute une ellipse - jamais de coupe en plein mot ni d'espace traînant).
    const expectedObject = 'rédiger un courriel de bienvenue très détaillé pour accueillir chaleureusement…';
    assert(expectedObject.length - 1 <= 80, 'sanity du calcul attendu : 80 caractères avant l\'ellipse');
    // Correctif 2026-08-28 (défaut mesuré au navigateur) : truncateAtWord() ajoute déjà l'ellipse
    // « … » - un point ajouté juste après produisait « …. », qu'aucune règle typographique
    // française n'admet. L'ancrage se termine donc SUR l'ellipse, sans point redondant.
    assert(prompt.includes('Produis maintenant : rédige ' + expectedObject), 'le livrable tronqué correspond exactement au calcul attendu (verbe en minuscule + objet coupé au dernier mot entier + ellipse)');
    assert(!prompt.includes('….'), 'aucun point n\'est collé après l\'ellipse de troncature (jamais de "….")');
    assert(prompt.trimEnd().endsWith('…'), 'le prompt se termine sur l\'ellipse elle-même, pas sur un point ajouté après coup');
    // La tâche complète (non tronquée) reste présente dans le bloc "Ta tâche :" - seule la
    // reprise de clôture est raccourcie, jamais la demande d'origine.
    assert(prompt.includes('Ta tâche : Rédige ' + c.taskObject + '.'), 'le bloc "Ta tâche :" garde la demande complète, non tronquée');
    assert(!prompt.slice(prompt.lastIndexOf('Produis maintenant : ')).includes('chaleureusement chaque'), 'la clôture elle-même ne contient pas le texte situé après la coupe');
}

// --- 3c. Même troncature, mais avec constraintAskIfUnclear actif : la clause "Sinon..." doit
// suivre l'ellipse sans point collé devant (défaut 2026-08-28, mesuré au navigateur). ---
{
    const c = baseComponent();
    c.taskObject = 'rédiger un courriel de bienvenue très détaillé pour accueillir chaleureusement chaque nouvel employé de notre entreprise dès son premier jour';
    c.constraintAskIfUnclear = true;
    const prompt = c.prompt;
    const expectedObject = 'rédiger un courriel de bienvenue très détaillé pour accueillir chaleureusement…';
    assert(prompt.includes(expectedObject + ' Sinon, pose d\'abord tes questions de clarification'), 'avec askIfUnclear, la clause "Sinon..." suit l\'ellipse avec un simple espace, jamais un point collé');
    assert(!prompt.includes('….'), 'aucun point n\'est collé après l\'ellipse même quand la clarification conditionnelle est active');
}

// --- 3b. Repli "la demande ci-dessus" quand aucun verbe/objet n'est disponible pour le livrable. ---
{
    const c = loadPromptBuilder();
    c.personaType = 'custom';
    c.personaCustom = 'expert en pédagogie';
    // Ni verbe ni taskObject renseignés : isValid() serait faux, mais promptSegments() peut
    // encore produire un segment (rôle seul) -> la clôture doit se replier proprement.
    const prompt = c.prompt;
    assert(prompt.includes('Produis maintenant : la demande ci-dessus.'), 'repli sur "la demande ci-dessus" quand le livrable ne peut pas être dérivé');
}

// --- 4. Critères de réussite (G7), remplace "Avant de finaliser, vérifie que". ---
{
    const c = baseComponent();
    c.cadreStrict = true;
    c.tone = 'Chaleureux';
    c.audienceType = 'custom';
    c.audienceCustom = 'des parents d\'élèves';
    c.length = 'Court (100-200 mots)';
    c.constraintAntiAI = true;
    const prompt = c.prompt;
    assert(prompt.includes('La réponse est réussie si :'), 'le nouvel intitulé "La réponse est réussie si :" est présent');
    assert(!prompt.includes('Avant de finaliser, vérifie que'), 'l\'ancien intitulé a disparu');
    assert(prompt.includes('le ton Chaleureux est tenu du début à la fin'), 'le critère de ton nomme la valeur réelle du ton');
    assert(prompt.includes('elle est directement utilisable par des parents d\'élèves'), 'le critère d\'audience nomme la valeur réelle de l\'audience');
    assert(prompt.includes('la longueur visée (Court (100-200 mots)) est respectée'), 'le critère de longueur nomme la valeur réelle de la longueur');
    assert(prompt.includes('elle se lit comme un texte écrit par un humain attentif, sans formules toutes faites'), 'le critère anti-IA reformulé est présent');
    assert(prompt.includes('Si tu ne peux pas satisfaire un critère, dis-le explicitement au lieu de le contourner.'), 'consigne de transparence si un critère ne peut pas être satisfait');
    assert(prompt.includes('Avant de livrer, vérifie silencieusement ta réponse contre ces critères et corrige ce qui ne passe pas ; n\'affiche pas cette vérification.'), 'consigne de vérification silencieuse avant livraison');
}

// --- 4b. Cadre strict désactivé : aucun bloc "critères de réussite" ne s'affiche. ---
{
    const c = baseComponent();
    c.cadreStrict = false;
    assert(!c.prompt.includes('La réponse est réussie si :'), 'cadreStrict désactivé -> pas de critères de réussite');
}

// --- 5. Longueur désambiguïsée quand la deuxième tâche est active (G5). ---
{
    const c = baseComponent();
    c.length = 'Modéré (300-500 mots)';
    assert(c.prompt.includes('Longueur visée : Modéré (300-500 mots)') && !c.prompt.includes('(pour le livrable principal)'), 'sans 2e tâche, la longueur reste inchangée');
}
{
    const c = baseComponent();
    c.length = 'Modéré (300-500 mots)';
    c.secondTaskEnabled = true;
    c.verbType2 = 'preset';
    c.verb2 = 'Traduis';
    assert(c.prompt.includes('Longueur visée : Modéré (300-500 mots) (pour le livrable principal)'), 'avec 2e tâche active, la longueur précise "pour le livrable principal"');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
