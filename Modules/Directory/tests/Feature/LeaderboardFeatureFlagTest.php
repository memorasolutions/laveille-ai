<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai — #232 (v1.17.2)
 *
 * Tests feature flag `directory.leaderboard.enabled` (config réversible).
 *
 * Quand off (default false en prod, user solo contributor) :
 *   - GET /annuaire/classement → 302 redirect /annuaire + flash session `info`
 *   - Liens menu/footer cachés (vue header partials)
 *
 * Quand on (DIRECTORY_LEADERBOARD_ENABLED=true) :
 *   - GET /annuaire/classement → 200 (comportement original LeaderboardController)
 *   - Liens menu/footer visibles
 *
 * Tests reflexifs sur partials Blade pour éviter dépendance DB lourde.
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

it('route classement source declare la closure gate + redirect', function () {
    $routesContent = file_get_contents(base_path('Modules/Directory/routes/web.php'));

    // Closure gate inline
    expect($routesContent)->toContain("if (! config('directory.leaderboard.enabled', false))");
    // Redirect vers directory.index
    expect($routesContent)->toContain("redirect()->route('directory.index')");
    // Flash session 'info' contextuelle
    expect($routesContent)->toContain("with('info'");
    // Fallback comportement original quand on (LeaderboardController::index)
    expect($routesContent)->toContain('app(LeaderboardController::class)->index()');
});

it('route name directory.leaderboard reste enregistre cote routes/web.php', function () {
    $routesContent = file_get_contents(base_path('Modules/Directory/routes/web.php'));

    // Nom de route conservé pour que Route::has('directory.leaderboard') reste vrai
    // (gates @if = Route::has + config flag). Route reste mappée sur /annuaire/classement.
    expect($routesContent)->toContain("->name('directory.leaderboard')");
    expect($routesContent)->toMatch("/Route::get\('\/annuaire\/classement'/");
});

it('header partial gate combine Route::has + config flag', function () {
    $content = file_get_contents(base_path('Modules/FrontTheme/resources/views/partials/header.blade.php'));

    // Aucune ref leaderboard sans gate config
    expect($content)->not->toMatch('/@if\(Route::has\(\'directory\.leaderboard\'\)\)/');
    // Toutes les refs combinent les deux conditions
    expect(substr_count($content, "Route::has('directory.leaderboard') && config('directory.leaderboard.enabled', false)"))
        ->toBeGreaterThanOrEqual(4);
});

it('footer partial gate combine Route::has + config flag', function () {
    $content = file_get_contents(base_path('Modules/FrontTheme/resources/views/partials/footer.blade.php'));

    expect($content)->toContain("Route::has('directory.leaderboard') && config('directory.leaderboard.enabled', false)");
    expect($content)->not->toMatch("/@if\(Route::has\('directory\.leaderboard'\)\)\\s*<li>/");
});

it('config directory.leaderboard.enabled existe et default false', function () {
    // Verify default value in config file (avant override par .env)
    $config = require base_path('Modules/Directory/config/config.php');

    expect($config)->toHaveKey('leaderboard');
    expect($config['leaderboard'])->toHaveKey('enabled');
    expect($config['leaderboard']['enabled'])->toBeFalse();
});

it('route name directory.leaderboard reste enregistre (Route::has true)', function () {
    // Critique : Route::has doit rester vrai pour que les gates fonctionnent
    // (Route::has + config flag = double sécurité). Si on retirait la route,
    // les gates @if seraient False par Route::has et on perdrait la traçabilité.
    expect(\Illuminate\Support\Facades\Route::has('directory.leaderboard'))->toBeTrue();
});
