<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
pest()->extend(Tests\TestCase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in(__DIR__.'/../Modules/Academy/tests/Feature');

// Module Sso (SSO SAML + provisioning SCIM) — désactivé par défaut
// (modules_statuses.json). Chaque test active le module lui-même via
// Modules\Sso\Tests\Concerns\SkipsWhenSsoDisabled + Nwidart Module::find()
// forcé enabled en beforeEach (même pattern que Academy), donc PAS dans
// $disabledModuleTestDirs ci-dessous (qui skip inconditionnellement).
pest()->extend(Tests\TestCase::class)
    ->in(__DIR__.'/../Modules/Sso/tests/Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit/Helpers');

/*
 * Modules désactivés dans ce déploiement (modules_statuses.json).
 * Leurs tests (Modules/<Module>/tests) ciblent des routes/migrations/providers non chargés :
 * on les saute proprement tant que le module reste désactivé, sans toucher au code prod.
 * Si un module est réactivé un jour, ses tests reprennent automatiquement.
 */
$disabledModuleTestDirs = [
    'ABTest', 'Backup', 'Booking', 'CustomFields', 'FormBuilder',
    'Import', 'SaaS', 'Storage', 'Team', 'Tenancy', 'Testimonials',
];

foreach ($disabledModuleTestDirs as $moduleName) {
    pest()->beforeEach(function () use ($moduleName) {
        if (! \Nwidart\Modules\Facades\Module::find($moduleName)?->isEnabled()) {
            test()->markTestSkipped("Module {$moduleName} désactivé dans ce déploiement.");
        }
    })->in(__DIR__.'/../Modules/'.$moduleName.'/tests');
}

/*
 * Fichiers de tests à la RACINE (tests/Feature/*.php), reliquats du gabarit
 * memora/laravel-saas-boilerplate, ENTIÈREMENT dédiés à SaaS et/ou Tenancy
 * (aucune fonctionnalité indépendante testée dans ces fichiers). Non couverts
 * par $disabledModuleTestDirs ci-dessus (qui ne cible que Modules/<X>/tests).
 * Skip propre quand le(s) module(s) concerné(s) sont désactivés, sans casser
 * le fichier. Si SaaS/Tenancy est réactivé un jour, ces tests reprennent
 * automatiquement (audit 2026-07-22).
 */
$disabledRootTestFiles = [
    'SaaS' => [
        __DIR__.'/Feature/Phase137Test.php',
        __DIR__.'/Feature/Phase138Test.php',
        __DIR__.'/Feature/Phase170Test.php',
        __DIR__.'/Feature/Phase171Test.php',
        __DIR__.'/Feature/SaasCheckoutTest.php',
    ],
];

foreach ($disabledRootTestFiles as $moduleName => $files) {
    pest()->beforeEach(function () use ($moduleName) {
        if (! \Nwidart\Modules\Facades\Module::find($moduleName)?->isEnabled()) {
            test()->markTestSkipped("Module {$moduleName} désactivé dans ce déploiement.");
        }
    })->in(...$files);
}

// Phase20SaasTest : entièrement dédié à SaaS ET Tenancy à la fois.
pest()->beforeEach(function () {
    $saasDisabled = ! \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled();
    $tenancyDisabled = ! \Nwidart\Modules\Facades\Module::find('Tenancy')?->isEnabled();
    if ($saasDisabled || $tenancyDisabled) {
        test()->markTestSkipped('Module SaaS et/ou Tenancy désactivé dans ce déploiement.');
    }
})->in(__DIR__.'/Feature/Phase20SaasTest.php');

afterEach(function () {
    // Flush Livewire EventBus pour éviter le memory leak entre tests
    if (class_exists(\Livewire\Mechanisms\EventBus::class)) {
        app()->forgetInstance(\Livewire\Mechanisms\EventBus::class);
    }
    if (class_exists(\Livewire\LivewireManager::class)) {
        app()->forgetInstance(\Livewire\LivewireManager::class);
    }
    gc_collect_cycles();
});

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
 * Helper global partagé entre les tests de rendu du blade du constructeur de prompts
 * (Modules/Tools/tests/Feature/ConstructeurGabaritsRenderTest.php et
 * PromptFromDraftRenderTest.php). DOIT vivre ICI, dans tests/Pest.php, et nulle part ailleurs :
 * Pest ne charge QUE ce fichier (Bootstrappers\BootFiles du package) pour amorcer TOUTE
 * exécution, quel que soit son périmètre (suite complète, un seul module, un seul fichier). Une
 * fonction PHP globale déclarée à même un fichier de test n'est disponible que si CE fichier a
 * déjà été chargé dans le même processus - vrai par accident quand la suite tourne au complet
 * dans l'ordre alphabétique, faux dès qu'un fichier est ciblé isolément. C'est exactement ce qui
 * bloquait la suite complète : la fonction était déclarée dans
 * ConstructeurGabaritsRenderTest.php et utilisée par PromptFromDraftRenderTest.php sans garantie
 * d'ordre de chargement entre les deux fichiers (« Call to undefined function
 * ctRenderConstructeur() », corrigé le 2026-08-29).
 */
function ctRenderConstructeur(): string
{
    $tool = \Modules\Tools\Models\Tool::where('slug', 'constructeur-prompts')->first();
    if (! $tool) {
        (new \Modules\Tools\Database\Seeders\ToolSeeder())->run();
        $tool = \Modules\Tools\Models\Tool::where('slug', 'constructeur-prompts')->firstOrFail();
    }

    return view('tools::public.tools.constructeur-prompts', ['tool' => $tool])->render();
}

/*
 * Isole lang_path() par worker Paratest - même mécanisme, même remède que le cache des vues
 * compilées (tests/bootstrap.php, TEST_TOKEN) : course RÉELLE mesurée le 2026-08-30 entre
 * tests/Feature/Phase155Test.php et Modules/Translation/tests/Feature/TranslationModuleTest.php,
 * les deux SEULS fichiers qui ÉCRIVENT (File::put) dans le vrai lang/fr.json et lang/en.json, que
 * `php artisan test --parallel` fait tourner en processus concurrents partageant ces mêmes
 * fichiers sur disque. Les ~30 autres fichiers qui LISENT lang_path() (Phase162-165Test,
 * TranslationTest, Modules/Tools/tests/Feature/RoundXXAdversarialFixesTest...) n'écrivent jamais :
 * protégés gratuitement dès que les deux écrivains ci-dessus n'écrivent plus dans le vrai fichier.
 *
 * Contrairement à VIEW_COMPILED_PATH (lu directement par le framework via env(), voir
 * tests/bootstrap.php), lang_path() n'est PAS piloté par variable d'environnement - il faut
 * appeler explicitement $app->useLangPath() APRÈS le boot de l'application, d'où ce helper appelé
 * en beforeEach() plutôt qu'un ajout dans tests/bootstrap.php (qui tourne avant que $app existe).
 * Inactif hors --parallel (TEST_TOKEN absent) : comportement inchangé en local/série.
 */
function testsIsolatedLangPath(): ?string
{
    $token = getenv('TEST_TOKEN');
    if ($token === false || $token === '') {
        return null;
    }

    // Même assainissement que tests/bootstrap.php avant de mettre le jeton dans un chemin.
    $token = preg_replace('/[^a-zA-Z0-9]/', '', $token);
    if ($token === '') {
        return null;
    }

    $path = __DIR__.'/../storage/framework/testing/lang-paratest-'.$token;

    if (! is_dir($path) && ! @mkdir($path, 0775, true) && ! is_dir($path)) {
        throw new RuntimeException('Impossible de créer le lang_path isolé du worker : '.$path);
    }

    // Copié une seule fois par worker (premier test qui le demande, détecté par l'absence de
    // fr.json) : les écrivains mutent ensuite CETTE copie, jamais le vrai lang/fr.json.
    if (! file_exists($path.'/fr.json')) {
        copy(__DIR__.'/../lang/fr.json', $path.'/fr.json');
        copy(__DIR__.'/../lang/en.json', $path.'/en.json');
        // PAS de symlink fr_CA.json ici (contrairement au vrai lang/) : première version de ce
        // correctif le reproduisait « pour fidélité » et a RECASSÉ ces mêmes 4 tests sur CI Linux
        // (toujours vert en local macOS) - TranslationService::getLocales() dédup par realpath()
        // en gardant le PREMIER fichier rencontré par File::files() (Symfony Finder), et l'ordre
        // d'énumération d'un répertoire n'est PAS garanti alphabétique : ext4 (runner CI) peut
        // renvoyer fr_CA.json avant fr.json, ce qu'APFS (ce poste) ne fait jamais dans ce cas
        // précis. Résultat mesuré sur CI : getLocales() rendait ['fr_CA', 'en'], jamais 'fr' -
        // exactement le symptôme d'origine, pour une cause entièrement différente et auto-infligée.
        // Ni Phase155Test.php ni TranslationModuleTest.php ne testent la locale 'fr_CA' : aucun
        // fichier tiers n'a de raison d'exister dans une copie isolée qui n'appartient qu'à ces
        // deux écrivains - un seul fichier par réalpath, dédup jamais en jeu, ordre sans objet.
    }
    // Retire un éventuel symlink fr_CA.json laissé par une exécution locale antérieure à ce
    // correctif (répertoire storage/framework/testing/ réutilisé d'un run à l'autre hors CI,
    // qui repart toujours d'un checkout neuf) - sans quoi le guard ci-dessus, qui ne teste que
    // fr.json, ne le nettoierait jamais.
    if (is_link($path.'/fr_CA.json')) {
        @unlink($path.'/fr_CA.json');
    }

    return $path;
}
