<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

// Note: errors.404 extends fronttheme_layout() which depends on Settings DB → smoke prod only

it('errors.layout view contains charte tokens', function () {
    $rendered = view('errors.layout')->render();

    // Palette charte courante (refonte error pages) : primary #0B7285, accent #C2410C.
    expect($rendered)->toContain('#0B7285');
    expect($rendered)->toContain('#C2410C');
    expect($rendered)->toContain('--c-primary');
    expect($rendered)->toContain('--c-accent');
});

it('errors.405 view contains code and microcopy', function () {
    $rendered = view('errors.405')->render();

    expect($rendered)->toContain('405');
    expect($rendered)->toContain('Méthode non autorisée');
    expect($rendered)->toContain('Cette action');
    expect($rendered)->toContain('autoris');
    expect($rendered)->toContain('#0B7285');
});

it('errors.403 view contains code and microcopy', function () {
    $rendered = view('errors.403')->render();

    expect($rendered)->toContain('403');
    expect($rendered)->toContain('Accès non autorisé');
});

it('errors.429 view contains code and microcopy', function () {
    $rendered = view('errors.429')->render();

    expect($rendered)->toContain('429');
    expect($rendered)->toContain('Trop de requêtes');
});

it('errors.500 view contains code and microcopy', function () {
    $rendered = view('errors.500')->render();

    expect($rendered)->toContain('500');
    expect($rendered)->toContain('Erreur système');
    expect($rendered)->toContain('sable mouvant');
});

it('errors.503 view is autonomous and contains charte tokens', function () {
    $exception = new Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(60, '');
    $rendered = view('errors.503', ['exception' => $exception])->render();

    expect($rendered)->toContain('Maintenance en cours');
    expect($rendered)->toContain('#0B7285');
    expect($rendered)->toContain('#C2410C');
    expect($rendered)->toContain('503');
    expect($rendered)->not->toContain('extends');
});

it('all error pages have noindex robots meta', function () {
    foreach (['layout', '405', '503'] as $view) {
        $rendered = $view === '503'
            ? view("errors.$view", ['exception' => new Exception('test')])->render()
            : view("errors.$view")->render();

        expect($rendered)->toContain('noindex');
    }
});
