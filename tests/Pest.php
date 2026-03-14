<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
pest()->extend(Tests\TestCase::class)
    ->in('Feature');

afterEach(function () {
    gc_collect_cycles();
});

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
