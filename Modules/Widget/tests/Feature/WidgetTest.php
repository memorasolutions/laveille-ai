<?php

declare(strict_types=1);

test('Widget module service provider is loaded', function () {
    expect(class_exists(\Modules\Widget\Providers\WidgetServiceProvider::class))
        ->toBeTrue();
});
