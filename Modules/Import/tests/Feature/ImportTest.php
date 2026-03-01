<?php

declare(strict_types=1);

test('Import module is loaded', function () {
    expect(class_exists(\Modules\Import\Providers\ImportServiceProvider::class))
        ->toBeTrue();
});
