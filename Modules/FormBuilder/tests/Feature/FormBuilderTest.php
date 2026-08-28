<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

// 2026-08-28 : sans cette declaration, Laravel n'est jamais demarre pour ce fichier,
// et le crochet beforeEach de tests/Pest.php qui saute les modules desactives echoue sur
// « A facade root has not been set ». Les 3 seuls fichiers de test du depot dans ce cas.
uses(Tests\TestCase::class);

// FormBuilder tests are in tests/Feature/FormBuilderTest.php (project root)
// This file is intentionally left as a placeholder.

test('placeholder', function () {
    expect(true)->toBeTrue();
});
