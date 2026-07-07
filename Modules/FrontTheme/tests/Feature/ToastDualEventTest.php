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

it('toast partial lit le type depuis $event.detail.type', function () {
    // Implémentation courante : un seul listener @toast.window qui lit message/type de detail.
    expect($this->content)->toContain('$event.detail.type');
    expect($this->content)->toContain('$event.detail.message');
});

it('toast partial style success applique fond vert', function () {
    expect($this->content)->toContain("type === 'success'");
    expect($this->content)->toContain('#065f46');
});

it('toast partial style error applique fond rouge', function () {
    expect($this->content)->toContain("type === 'error'");
    expect($this->content)->toContain('#DC2626');
});

it('toast partial fallback type info (fond teal)', function () {
    // Pas de mapping explicite : le ternaire retombe sur le style info par défaut.
    // Couleur mise à jour de #0B7285 (5.59:1, échec AAA) vers #064E5A (9.35:1, conforme
    // WCAG AAA) suite au remplacement du token --c-primary dans public/css/charte.css
    // (issue #217, session S83). Le partial toast.blade.php utilise la valeur actuelle.
    expect($this->content)->toContain("type === 'info'");
    expect($this->content)->toContain('#064E5A');
});

it('toast partial auto-dismiss après 3000ms', function () {
    expect($this->content)->toContain('setTimeout(() => show = false, 3000)');
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
