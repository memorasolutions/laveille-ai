<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai — #238 CWV quick wins
 *
 * Tests reflexifs source-based : pas de DB requise (équivalent S86 ErrorPagesTest pattern).
 * Vérifie que les optimisations CWV v1.18.4 (preload, fetchpriority, defer, cache, gzip)
 * sont présentes dans les fichiers cibles.
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

test('home.blade.php contains preload hero LCP with fetchpriority high', function () {
    $source = file_get_contents(base_path('Modules/FrontTheme/resources/views/home.blade.php'));
    expect($source)->toContain('<link rel="preload" as="image"');
    expect($source)->toContain('fetchpriority="high"');
});

test('home.blade.php first hero img uses fetchpriority high + eager loading', function () {
    $source = file_get_contents(base_path('Modules/FrontTheme/resources/views/home.blade.php'));
    expect($source)->toMatch('/\$hero1->featured_image.*fetchpriority="high".*loading="eager"/s');
});

test('home.blade.php hero2/3/4 use lazy loading explicit', function () {
    $source = file_get_contents(base_path('Modules/FrontTheme/resources/views/home.blade.php'));
    // hero2 / hero3 / hero4 imgs must include loading=lazy
    expect(substr_count($source, 'loading="lazy"'))->toBeGreaterThanOrEqual(3);
});

test('blog/show.blade.php preloads article featured image as LCP', function () {
    $source = file_get_contents(base_path('Modules/FrontTheme/resources/views/blog/show.blade.php'));
    expect($source)->toContain('<link rel="preload" as="image"');
    expect($source)->toContain('fetchpriority="high"');
});

test('master.blade.php defers jQuery + bootstrap.bundle + script.js', function () {
    $source = file_get_contents(base_path('Modules/FrontTheme/resources/views/layouts/master.blade.php'));
    expect($source)->toMatch('/<script defer src="https:\/\/code\.jquery\.com\/jquery/');
    expect($source)->toMatch('/<script defer src="\{\{ fronttheme_asset\(\'js\/bootstrap\.bundle\.min\.js\'\)/');
    expect($source)->toMatch('/<script defer src="\{\{ fronttheme_asset\(\'js\/script\.js\'\)/');
});

test('master.blade.php bootstrap.min.css uses async preload pattern', function () {
    $source = file_get_contents(base_path('Modules/FrontTheme/resources/views/layouts/master.blade.php'));
    expect($source)->toMatch('/preload" as="style".*bootstrap\.min\.css.*onload="this\.onload=null;this\.rel=\'stylesheet\'"/');
    expect($source)->toContain('<noscript>');
});

test('htaccess compression coverage etendue inclut application/ld+json + brotli', function () {
    $source = file_get_contents(base_path('public/.htaccess'));
    expect($source)->toContain('application/ld+json');
    expect($source)->toContain('application/manifest+json');
    expect($source)->toContain('mod_brotli');
});

test('Directory routes index/show cached via cacheResponse', function () {
    $source = file_get_contents(base_path('Modules/Directory/routes/web.php'));
    expect($source)->toContain("'/annuaire', [PublicDirectoryController::class, 'index']");
    // directory.index must have cacheResponse
    expect($source)->toMatch("/'\/annuaire'.*cacheResponse:600/s");
    // directory.show must keep doNotCacheResponse (per request user)
    expect($source)->toContain("doNotCacheResponse");
});

test('Tools routes index + show cached via cacheResponse', function () {
    $source = file_get_contents(base_path('Modules/Tools/routes/web.php'));
    expect($source)->toMatch("/'\/outils'.*cacheResponse:600/");
    expect($source)->toContain("cacheResponse:600")
        ->and($source)->toContain("PublicToolController::class, 'show'");
});

test('Dictionary routes index + show cached 3600s', function () {
    $source = file_get_contents(base_path('Modules/Dictionary/routes/web.php'));
    expect($source)->toMatch("/'\/glossaire'.*cacheResponse:3600/");
    expect($source)->toMatch("/'\/glossaire\/\{slug\}'.*cacheResponse:3600/");
});

test('News routes index + show cached 600s', function () {
    $source = file_get_contents(base_path('Modules/News/routes/web.php'));
    expect($source)->toMatch("/'\/actualites'.*cacheResponse:600/");
    expect($source)->toContain("cacheResponse:600");
});

test('config/version.php is well-formed and at least the CWV release (1.18.4)', function () {
    // Les CWV quick wins ont été introduits en 1.18.4 ; la version applicative évolue ensuite.
    // On vérifie donc la cohérence du config + un plancher >= 1.18.4 plutôt qu'une valeur figée
    // (un pin exact casserait à chaque bump SemVer — anti-pattern).
    $cfg = require base_path('config/version.php');

    expect($cfg)->toHaveKeys(['semver', 'codename', 'major', 'minor', 'patch']);
    expect($cfg['semver'])->toMatch('/^\d+\.\d+\.\d+$/');
    expect($cfg['major'])->toBeInt();
    expect($cfg['minor'])->toBeInt();
    expect($cfg['patch'])->toBeInt();

    // Plancher : version >= 1.18.4 (release CWV).
    expect(version_compare($cfg['semver'], '1.18.4', '>='))->toBeTrue();

    // Cohérence interne semver vs composants major/minor/patch.
    expect($cfg['semver'])->toBe("{$cfg['major']}.{$cfg['minor']}.{$cfg['patch']}");
});

test('CloudflareCache job uses onQueue setter to avoid Queueable trait composition error', function () {
    // Trait Illuminate\Bus\Queueable declares `public $queue` (no type, no default).
    // Child class must NOT redeclare property (incompat PHP 8.2+). Use $this->onQueue() setter.
    $source = file_get_contents(base_path('Modules/CloudflareCache/app/Jobs/PurgeCloudflareCacheJob.php'));
    expect($source)->toContain("\$this->onQueue('cloudflare')");
    expect($source)->not->toContain("public string \$queue");
    // Sanity: instantiable without fatal error
    $job = new \Modules\CloudflareCache\Jobs\PurgeCloudflareCacheJob([]);
    expect($job->queue)->toBe('cloudflare');
});
