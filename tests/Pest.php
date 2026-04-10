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
