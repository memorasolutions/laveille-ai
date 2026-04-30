<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->toastPath = base_path('Modules/FrontTheme/resources/views/partials/toast.blade.php');
    $this->content = file_get_contents($this->toastPath);
});

it('toast partial existe sur disque', function () {
    expect($this->toastPath)->toBeFile();
});

it('toast partial declare listener legacy @toast.window', function () {
    expect($this->content)->toContain('@toast.window');
});

it('toast partial declare listener nouveau @toast-show.window', function () {
    expect($this->content)->toContain('@toast-show.window');
});

it('toast partial mappe variant danger vers error', function () {
    expect($this->content)->toContain("(v === 'danger' || v === 'warning') ? 'error'");
});

it('toast partial mappe variant warning vers error', function () {
    expect($this->content)->toContain("(v === 'danger' || v === 'warning') ? 'error'");
});

it('toast partial mappe variant success vers success', function () {
    expect($this->content)->toContain("v === 'success' ? 'success'");
});

it('toast partial fallback variant info', function () {
    expect($this->content)->toContain(": 'info'");
});

it('toast partial supporte duration custom', function () {
    expect($this->content)->toContain('$event.detail.duration || 3000');
});

it('toast partial inclut accessibility role status aria-live polite', function () {
    expect($this->content)->toContain('role="status"');
    expect($this->content)->toContain('aria-live="polite"');
});

it('toast partial applique position fixed bottom right z-index', function () {
    expect($this->content)->toContain('position: fixed');
    expect($this->content)->toContain('bottom: 24px');
    expect($this->content)->toContain('right: 24px');
    expect($this->content)->toContain('z-index: 10001');
});
