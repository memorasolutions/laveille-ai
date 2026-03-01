<?php

declare(strict_types=1);

test('CustomFields module service provider is loaded', function () {
    expect(class_exists(\Modules\CustomFields\Providers\CustomFieldsServiceProvider::class))
        ->toBeTrue();
});
