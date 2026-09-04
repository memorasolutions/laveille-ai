// tests/js/composition-builder-publishMissingList-credit.test.cjs
// Garde-fou #2244 (2026-09-04). Le serveur refuse desormais de publier une fiche dont
// image_credit est vide (NewsArticle::publishReadinessCheck() - un credit vide signifie que la
// fiche partirait avec la carte de repli generee a partir du titre, deux recidives en production
// les 2026-08-31 et 2026-09-03). La moitie CLIENTE de ce garde-fou vivait dans
// publishMissingList(), qui listait encore les 3 champs d'AVANT le correctif : le bouton
// « Publier » restait donc ACTIF, et le redacteur ne decouvrait le refus qu'apres l'appel HTTP.
// Defaut trouve par la revue adversariale de Codex, puis VERIFIE dans le code reel avant d'etre
// accepte (un oracle a deja fabrique un defaut inexistant sur ce projet - #2196).
//
// Pourquoi un test JS et pas un test Pest : /admin/news/composition est protege par le middleware
// two.factor, la QC navigateur y est donc impossible sans contourner une double authentification -
// ce qu'on ne fait pas. Le bac a sable Node est la seule preuve disponible pour cette moitie-la.
//
// Execute : node tests/js/composition-builder-publishMissingList-credit.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

const BLADE_PATH = path.join(__dirname, '../../Modules/News/resources/views/admin/composition-builder.blade.php');
const PICKER_PATH = path.join(__dirname, '../../public/assets/admin/news-article-picker.js');

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

function loadComponent() {
    const builderSrc = extractCompositionBuilderSource(fs.readFileSync(BLADE_PATH, 'utf8'));
    const pickerSrc = fs.readFileSync(PICKER_PATH, 'utf8');

    global.window = global;
    global.document = {
        addEventListener: () => {},
        querySelector: () => null,
        getElementById: () => null,
    };
    global.localStorage = { getItem: () => null, setItem: () => {}, removeItem: () => {} };
    global.fetch = () => Promise.reject(new Error('aucun appel reseau attendu dans ce test'));
    global.URLSearchParams = require('url').URLSearchParams;

    new Function(pickerSrc)();
    const compositionBuilder = new Function(builderSrc + '\nreturn compositionBuilder;')();

    return compositionBuilder({
        showEndpointTemplate: '/admin/news/composition/__SLUG__',
        updateEndpointTemplate: '/admin/news/composition/__SLUG__',
        candidatesEndpoint: '/admin/news/composition/candidates',
    });
}

// Etat d'une fiche dont les 3 champs historiques sont remplis : sans le garde-fou #2244,
// publishMissingList() renvoie [] et le bouton « Publier » est actif - alors que le serveur, lui,
// refuserait la publication faute de credit d'image.
function ficheSansCredit(component) {
    component.formSeoTitle = 'Un titre pour Google';
    component.formSummary = 'Un chapeau qui tient en deux lignes.';
    component.proofPairs = [{ statement: 'Une affirmation', excerpt: 'un extrait', type: 'fact' }];
    component.formImageCredit = '';
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

(function run() {
    const component = loadComponent();

    ficheSansCredit(component);
    const sansCredit = component.publishMissingList();
    assert(
        sansCredit.some((m) => /cr[ée]dit/i.test(m)),
        "publishMissingList() signale le credit d'image manquant (le bouton Publier reste donc desactive)"
    );

    component.formImageCredit = '   ';
    assert(
        component.publishMissingList().some((m) => /cr[ée]dit/i.test(m)),
        'un credit fait uniquement d espaces compte comme absent'
    );

    component.formImageCredit = 'Photo : Agence Untelle';
    const avecCredit = component.publishMissingList();
    assert(
        avecCredit.length === 0,
        'une fiche complete avec son credit ne signale plus rien (aucun faux blocage introduit)'
    );

    // Objection (b) de la revue Codex, RETENUE : appeler publishMissingList() ne prouve rien si
    // le bouton « Publier » n'y est plus lie. Le bac a sable Node n'execute pas Alpine, donc la
    // seule preuve disponible ici porte sur la SOURCE de la vue : le bouton doit lier son
    // attribut :disabled a publishMissingList(). Cette assertion attrape la suppression, le
    // deplacement ou l'inversion de la liaison - ce que le reste du test ne voit pas.
    const bladeSrc = fs.readFileSync(BLADE_PATH, 'utf8');
    const boutonPublier = bladeSrc.indexOf('Publier et purger le texte source');
    const liaison = bladeSrc.lastIndexOf(':disabled="publishing || publishMissingList().length > 0"', boutonPublier);
    assert(
        boutonPublier !== -1 && liaison !== -1 && (boutonPublier - liaison) < 900,
        'le bouton « Publier » lie bien son attribut :disabled a publishMissingList() dans la vue'
    );

    // Non-regression des 3 controles d'origine : le garde-fou ne doit pas les avoir remplaces.
    component.formSeoTitle = '';
    assert(
        component.publishMissingList().some((m) => /titre SEO/i.test(m)),
        'le controle historique du titre SEO fonctionne toujours'
    );

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
