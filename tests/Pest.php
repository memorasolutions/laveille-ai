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
