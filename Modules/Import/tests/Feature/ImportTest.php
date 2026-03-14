<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

test('Import module is loaded', function () {
    expect(class_exists(\Modules\Import\Providers\ImportServiceProvider::class))
        ->toBeTrue();
});
