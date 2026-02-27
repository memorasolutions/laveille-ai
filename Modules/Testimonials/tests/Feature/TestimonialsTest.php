<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

test('Testimonials module is loaded', function () {
    expect(array_key_exists('testimonials', app('modules')->allEnabled()))
        ->toBeTrue();
});
