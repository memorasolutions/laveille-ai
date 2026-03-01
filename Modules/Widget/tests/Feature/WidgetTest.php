<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

test('Widget module service provider is loaded', function () {
    expect(class_exists(\Modules\Widget\Providers\WidgetServiceProvider::class))
        ->toBeTrue();
});
