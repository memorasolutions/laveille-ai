<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 77 (2026-07-27) : passe adversariale fraîche après le lot round 76 (131 clés header/footer/
// sidebar/search-palette + fix :count). 3 manques réels trouvés et vérifiés indépendamment, aucun
// recoupement avec les rounds 66-76 :
//
// 1. Modules/FrontTheme/resources/views/partials/newsletter-widget.blade.php - piste explicitement
//    laissée non vérifiée par le round 76 (le widget réel rendu dans la sidebar, puisque la route
//    'newsletter.subscribe' existe toujours dans cette app - PAS le widget de contact "Comment nous
//    aider ?" couvert au round 76). 2 clés __() sans traduction EN : "Restez informé" et
//    "Inscrivez-vous pour recevoir nos derniers articles.".
// 2. public/assets/tools/constructeur-prompts/constructeur-prompts-core.js - objets `helps` (13
//    clés d'aide contextuelle "?"), `techniqueHints` (5 clés) et 3 messages du panneau "Diagnostic
//    rapide" étaient du texte UI pur (jamais injecté dans le prompt généré - donc PAS le piège
//    personas/verbes/audiences round 74) codé en dur en français dans ce fichier JS externe, sans
//    aucun pont i18n contrairement au reste du fichier (window.promptBuilderConfig.i18n.*). Fixé en
//    ajoutant $pbHelps/$pbTechniqueHints (variables PHP, __()) + i18n.diagnostic{Format,Audience,
//    Contraintes} côté Blade, et repli français conditionnel côté JS
//    ((window.promptBuilderConfig && window.promptBuilderConfig.helps) || {...français...}).
// 3. constructeur-prompts.blade.php:123 - l'indicateur d'étape (cercles "1"/"2") était un <div>
//    cliquable à la souris (@click) mais totalement inatteignable au clavier (pas de tabindex, role,
//    ni gestionnaire clavier) - échec WCAG 2.1.1. Cibles aussi sous le standard 44px déjà appliqué
//    ailleurs dans ce même fichier (WCAG 2.2 AAA SC 2.5.5). Fixé : role="button" tabindex="0"
//    @keydown.enter/.space aria-current aria-label + taille 44px + classe .ct-step-circle avec
//    focus-visible.
//
// Piège de compilation Blade rencontré en corrigeant #2 (et évité) : @json(__('...')) avec un
// tableau PHP littéral multi-lignes contenant des parenthèses/virgules imbriquées dans les valeurs
// (ex. "(pour un résumé)", "(ChatGPT)") plante la compilation Blade ("unexpected identifier, expecting
// ')'") - contournement : construire le tableau comme variable PHP normale dans un bloc @php
// existant ($pbHelps/$pbTechniqueHints), puis @json($pbHelps) en référence simple.

it('has English translations for the shared newsletter-widget keys (round 77)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Restez informé',
        'Inscrivez-vous pour recevoir nos derniers articles.',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('renders the newsletter-widget translated in EN locale (round 77)', function () {
    // Rendu direct du partial (pas via route('blog.index'), qui déclenche une requête pré-existante
    // hors-scope non liée à l'i18n - cf. Round76AdversarialFixesTest).
    app()->setLocale('en');
    $html = view('fronttheme::partials.sidebar')->render();

    expect($html)->toContain('Stay informed');
    expect($html)->toContain('Subscribe to receive our latest articles.');
    expect($html)->not->toContain('Restez informé');
    expect($html)->not->toContain('Inscrivez-vous pour recevoir nos derniers articles.');
});

it('has English translations for the constructeur-prompts JS help/technique-hint/diagnostic strings (round 77)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        "Donner un rôle à l'IA aide à orienter ses réponses selon une expertise ou un style spécifique. Ex: « Tu es un expert marketing » donnera des réponses plus stratégiques.",
        "Choisir un verbe d'action précise ce que l'IA doit faire : rédiger, analyser, résumer, créer... Le verbe détermine le type de résultat.",
        "Décrivez clairement et précisément ce que l'IA doit produire. Plus vous donnez de contexte et de détails, meilleur sera le résultat.",
        "Spécifier le public aide l'IA à adapter son langage. Un texte pour des débutants sera différent d'un texte pour des experts.",
        'Le format guide la structure de la réponse. Une liste à puces est facile à lire, un tableau est bon pour comparer, un plan est idéal pour organiser.',
        'Indiquer une longueur permet de contrôler si la réponse est concise (pour un résumé) ou détaillée (pour un article complet).',
        'Le ton change le style : professionnel pour un rapport, chaleureux pour un courriel client, académique pour un mémoire.',
        "Les délimiteurs (###) séparent vos instructions de vos données. Utile quand vous analysez un texte spécifique : l'IA sait où commence le texte à analyser.",
        "L'IA a tendance à produire des textes génériques reconnaissables. Cette option force un style plus naturel, varié et authentiquement humain.",
        "Canvas (ChatGPT) et artefact (Claude) sont des espaces de travail dédiés où l'IA crée du contenu que vous pouvez modifier directement.",
        "La chaîne de pensée force l'IA à montrer son raisonnement, pas juste le résultat. Très utile pour les problèmes complexes, les mathématiques ou la logique.",
        "Au lieu de deviner, l'IA vous posera des questions de clarification. Résultat : des réponses beaucoup plus pertinentes dès le premier essai.",
        "L'IA répond directement, sans exemple ni étape intermédiaire.",
        "L'IA réfléchit en interne avant de répondre, sans montrer ce raisonnement.",
        "Vous donnez 2-3 exemples du résultat attendu pour guider l'IA.",
        'Exemples fournis, puis raisonnement détaillé appliqué au même modèle.',
        "L'IA avance étape par étape et attend votre accord avant de continuer.",
        "Tu n'as pas indiqué la forme de la réponse attendue (texte court, liste, tableau...) ni sa longueur.",
        "Tu n'as pas indiqué à qui s'adresse la réponse (par exemple : tes élèves, des parents, des collègues). L'IA adaptera mieux son ton si elle le sait.",
        "Tu n'as coché aucune règle à faire respecter (par exemple : éviter le style trop « IA », poser une question si la demande est floue).",
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('injects helps/techniqueHints/diagnostic i18n translated into window.promptBuilderConfig on the real page in EN locale (round 77)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('Assigning a role to the AI helps guide its responses');
    expect($html)->toContain('The AI responds directly, without examples or intermediate steps.');
    expect($html)->toContain('diagnosticFormat');
    expect($html)->toContain("You haven't specified the expected response format (short text, list, table...) or its length.");
    expect($html)->not->toContain("Donner un rôle à l'IA aide");
});

it('the JS file falls back to window.promptBuilderConfig for helps and techniqueHints (round 77)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('helps: (window.promptBuilderConfig && window.promptBuilderConfig.helps) ||');
    expect($js)->toContain('techniqueHints: (window.promptBuilderConfig && window.promptBuilderConfig.techniqueHints) ||');
    expect($js)->toContain('i18n.diagnosticFormat ||');
    expect($js)->toContain('i18n.diagnosticAudience ||');
    expect($js)->toContain('i18n.diagnosticContraintes ||');
});

// Round 151 (2026-08-01, refonte écrans 1-2) : les 2 tests ci-dessous verrouillaient
// l'accessibilité clavier de l'indicateur d'étapes numéroté (cercles "1"/"2"). Cet indicateur a
// été RETIRÉ dans son ensemble - consigne explicite de la refonte : « aucune numérotation
// d'étapes ». L'accessibilité clavier qu'ils protégeaient est sans objet (l'élément n'existe
// plus). Les 4 autres tests de ce fichier (i18n newsletter-widget + helps/techniqueHints/
// diagnostic) restent inchangés et continuent de protéger des invariants réels.

it('no longer renders the numbered step indicator (round 151, replaces round 77 keyboard-access checks)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->not->toContain('class="ct-step-circle"');
});

it('renders the wizard without the step indicator on the real page (round 151)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->not->toContain('class="ct-step-circle"');
});
