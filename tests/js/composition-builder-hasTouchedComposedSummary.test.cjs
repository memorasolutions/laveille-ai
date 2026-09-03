// tests/js/composition-builder-hasTouchedComposedSummary.test.cjs
// Garde-fou de non-regression (Lot 4b, design doc "extension de l'ecran de composition des
// actualites", 2026-09-03) : hasTouchedComposedSummary() testait auparavant si les 8 champs du
// resume structure etaient NON VIDES pour decider d'envoyer composed_summary a update(). Or ces
// 8 champs arrivent PRE-REMPLIS des le chargement d'une fiche qui porte deja un resume MACHINE
// (collecte RSS, AiSummaryService) - « non vide » ne veut donc pas dire « touche par un humain ».
// Defaut mesure en QC visuelle le 2026-09-03 : enregistrer le seul credit photo (aucun champ du
// resume modifie) faisait quand meme basculer composed_summary_active a true dans le dos de
// l'administrateur, au prochain chargement.
//
// Fix : hasTouchedComposedSummary() compare desormais la serialisation courante
// (buildComposedSummaryPayload()) a composedSummarySnapshot, l'instantane pose par loadArticle()
// au moment du chargement - seul un changement REEL depuis cet instantane fait basculer le
// drapeau, jamais la simple presence de contenu pre-rempli.
//
// Execute : node tests/js/composition-builder-hasTouchedComposedSummary.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

const BLADE_PATH = path.join(__dirname, '../../Modules/News/resources/views/admin/composition-builder.blade.php');
const PICKER_PATH = path.join(__dirname, '../../public/assets/admin/news-article-picker.js');

// Extrait le bloc <script> inline qui declare compositionBuilder(opts) - pur JS, aucune syntaxe
// Blade a l'interieur (verifie : x-data="compositionBuilder({...})" ne vit que dans l'attribut
// HTML de l'element, jamais a l'interieur de ce bloc <script>). Le PREMIER </script> rencontre
// APRES le marqueur de depart ferme necessairement CE bloc : un <script> ne peut pas en contenir
// un autre en HTML, donc aucune ambiguite possible avec un </script> plus loin dans le fichier.
function extractCompositionBuilderSource(bladeSrc) {
    const startMarker = 'function compositionBuilder(opts) {';
    const startIdx = bladeSrc.indexOf(startMarker);
    if (startIdx === -1) {
        throw new Error('marqueur de depart "function compositionBuilder(opts) {" introuvable dans composition-builder.blade.php');
    }
    const endIdx = bladeSrc.indexOf('</script>', startIdx);
    if (endIdx === -1) {
        throw new Error('balise </script> de fermeture introuvable apres compositionBuilder');
    }
    return bladeSrc.slice(startIdx, endIdx);
}

function loadComponent(fetchImpl) {
    const bladeSrc = fs.readFileSync(BLADE_PATH, 'utf8');
    const pickerSrc = fs.readFileSync(PICKER_PATH, 'utf8');
    const builderSrc = extractCompositionBuilderSource(bladeSrc);

    // window EST l'objet global (comme dans un vrai navigateur) : le mixin partage pose
    // "window.NewsArticlePicker = function (opts) {...}" (public/assets/admin/news-article-picker.js)
    // et compositionBuilder() appelle ensuite l'identifiant NU NewsArticlePicker(...) - exactement
    // comme sur la page reelle, ou les deux <script> partagent le meme scope global du navigateur.
    // Sans cet alias, l'assignation window.NewsArticlePicker resterait invisible a l'identifiant nu.
    global.window = global;
    global.document = {
        addEventListener: () => {},
        querySelector: () => null,
        getElementById: () => null,
    };
    global.localStorage = {
        getItem: () => null,
        setItem: () => {},
        removeItem: () => {},
    };
    global.fetch = fetchImpl;
    global.URLSearchParams = require('url').URLSearchParams;

    // Charge d'abord le mixin partage - ordre reel de la page (news-article-picker.js est inclus
    // AVANT le <script> inline ; voir la note "window.NewsArticlePicker doit exister avant init()"
    // en tete de composition-builder.blade.php).
    new Function(pickerSrc)();

    const compositionBuilder = new Function(builderSrc + '\nreturn compositionBuilder;')();

    return compositionBuilder({
        showEndpointTemplate: '/admin/news/composition/__SLUG__',
        updateEndpointTemplate: '/admin/news/composition/__SLUG__',
        candidatesEndpoint: '/admin/news/composition/candidates',
    });
}

// Reponse show() typique d'une fiche fraichement collectee : resume structure deja rempli par
// AiSummaryService (collecte RSS), jamais encore compose par un humain - composed_summary_active
// est donc false. internal_source_text non vide pour que loadArticle() ne declenche pas
// fetchSource() en arriere-plan (chemin hors-perimetre de ce test).
function machineSummaryShowResponse() {
    return {
        id: 1,
        slug: 'article-test-resume-machine',
        title: 'Titre original de la collecte',
        seo_title: null,
        summary: 'Resume classique publie',
        internal_source_text: 'Texte source deja capture, evite le fetch automatique.',
        editorial_proof_pairs: [],
        source_captured_at: null,
        source_content_hash: null,
        primary_sources: [],
        image_credit: null,
        composed_summary: {
            hook: 'Accroche generee automatiquement par la machine',
            key_points: ['Premier point machine', 'Second point machine'],
            why_important: 'Explication generee automatiquement',
            key_number: '42 %',
            quote: { text: 'Citation rapportee par la source', author: 'Un porte-parole' },
            angle_qc_ca: 'Angle Quebec/Canada genere automatiquement',
            action_concrete: 'Action concrete suggeree automatiquement',
            reperes_dates: [{ date: '2026-01-01', texte: 'Repere genere automatiquement', url: '' }],
        },
        composed_summary_active: false,
        related_tools: [],
        nature_original: null,
        niveau_preuve: null,
        nature_original_options: {},
        niveau_preuve_options: {},
        is_published: false,
        reviewed_at: null,
        reviewed_by: null,
        site_url: 'https://laveille.ai/actualites/article-test-resume-machine',
        source_url: null,
        updated_at: '2026-09-03T12:00:00-04:00',
        has_image: false,
        image_url: null,
    };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

(async function run() {
    const component = loadComponent(() => Promise.resolve({
        ok: true,
        json: () => Promise.resolve(machineSummaryShowResponse()),
    }));
    component.newsItems = [{ id: 1, slug: 'article-test-resume-machine' }];

    await component.loadArticle(1);

    assert(component.composedSummaryActive === false, "loadArticle() d'une fiche MACHINE pose composedSummaryActive a false");
    assert(
        component.hasTouchedComposedSummary() === false,
        "hasTouchedComposedSummary() retourne FALSE juste apres le chargement d'un resume MACHINE (les 8 champs sont non vides, mais non TOUCHES)"
    );

    component.formHook = 'Accroche reecrite a la main par un administrateur';

    assert(
        component.hasTouchedComposedSummary() === true,
        "hasTouchedComposedSummary() retourne TRUE des qu'un champ est reellement modifie apres le chargement"
    );

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
