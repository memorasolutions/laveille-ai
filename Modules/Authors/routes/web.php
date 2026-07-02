<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authors\Http\Controllers\AuthorsController;
use Modules\Authors\Http\Controllers\MiniSiteController;
use Modules\Authors\Http\Controllers\PostController;
use Modules\Authors\Http\Controllers\AffiliateController;
use Modules\Authors\Http\Controllers\UpgradeController;
use Modules\Authors\Http\Controllers\OgImageController;

// S121 — Dynamic OG image (PNG GD) per post, distinct prefix to avoid /@slug collision
Route::get('/og-image/{slug}/{postSlug}.png', [OgImageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->where('postSlug', '[a-z0-9-]+')
    ->name('authors.og-image');

// S121 — Comment moderation queue (super-admin only)
Route::middleware(['web', 'auth', \Modules\Authors\Http\Middleware\EnsureSuperAdmin::class])
    ->prefix('backoffice/authors')
    ->group(function () {
        Route::get('/comments', fn () => view('authors::backoffice.comment-moderation'))
            ->name('backoffice.authors.comments');
    });

// S115 — API publique JSON Authors profils (no auth, public read-only)
// Préfixe author-profiles pour éviter collision avec route resource api/v1/authors/{author} (admin auth)
Route::prefix('api/v1')
    ->middleware('throttle:60,1')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () {
        Route::get('/author-profiles/{slug}', [\Modules\Authors\Http\Controllers\Api\PublicAuthorApiController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('authors.api.show');

        Route::get('/author-profiles/{slug}/posts', [\Modules\Authors\Http\Controllers\Api\PublicAuthorApiController::class, 'posts'])
            ->where('slug', '[a-z0-9-]+')
            ->name('authors.api.posts');
    });

// S115/S116 — Backoffice all-authors viewer (super-admin only)
Route::middleware(['web', 'auth', \Modules\Authors\Http\Middleware\EnsureSuperAdmin::class])->prefix('/backoffice')->group(function () {
    Route::get('/authors', fn () => view('authors::backoffice.all-authors'))
        ->name('authors.backoffice.all');
});

// S112 — Affiliate links cloaking
Route::get('/go/{slug}', [AffiliateController::class, 'go'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware('throttle:60,1')
    ->name('authors.affiliate.go');

// S112 — Tips Stripe one-tap
Route::post('/auteur/{slug}/tip', [MiniSiteController::class, 'tip'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware('throttle:10,1')
    ->name('authors.tip');

// S111 — Webmentions IndieWeb endpoint (réception backlinks décentralisés)
Route::post('/webmention', function (\Illuminate\Http\Request $request) {
    $source = $request->input('source');
    $target = $request->input('target');
    if (! $source || ! $target) {
        return response('Missing source or target', 400);
    }
    $service = app(\Modules\Authors\Services\WebmentionService::class);
    $webmention = $service->receive($source, $target);

    return response($webmention ? 'Accepted' : 'Target not found', $webmention ? 202 : 400);
})
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('throttle:30,1')
    ->name('authors.webmention.receive');

// S110 — Sitemap dédié auteurs + posts (SEO/AEO)
Route::get('/sitemap-authors.xml', function () {
    $xml = app(\Modules\Authors\Services\AuthorsSitemapService::class)->generate();

    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600, s-maxage=86400',
    ]);
})->name('authors.sitemap');

// Mini-site public (sans menu laveille, layout pleine page)
Route::get('/@{slug}', [MiniSiteController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware(\Modules\Authors\Http\Middleware\CacheAuthorMiniSite::class)
    ->name('authors.mini-site.show');

Route::get('/@{slug}/feed.xml', [MiniSiteController::class, 'rss'])
    ->where('slug', '[a-z0-9-]+')
    ->name('authors.mini-site.rss');

Route::get('/@{slug}/feed.json', [MiniSiteController::class, 'jsonFeed'])
    ->where('slug', '[a-z0-9-]+')
    ->name('authors.mini-site.json-feed');

// S122 — Tag archive (MUST come before catch-all /@{slug}/{postSlug} for regex priority)
Route::get('/@{slug}/tag/{tag}', [\Modules\Authors\Http\Controllers\TagArchiveController::class, 'show'])
    ->where(['slug' => '[a-z0-9-]+', 'tag' => '[A-Za-z0-9\-\.%]+'])
    ->name('authors.tag-archive');

// S122 — Draft preview signed link (MUST come before catch-all)
Route::get('/@{slug}/preview/{postSlug}', [\Modules\Authors\Http\Controllers\DraftPreviewController::class, 'show'])
    ->middleware('signed')
    ->where(['slug' => '[a-z0-9-]+', 'postSlug' => '[a-z0-9-]+'])
    ->name('authors.post.preview');

// Article public (S107 P2) — MUST come AFTER feed.xml/feed.json + tag/preview routes for regex priority
Route::get('/@{slug}/{postSlug}', [PostController::class, 'show'])
    ->where(['slug' => '[a-z0-9-]+', 'postSlug' => '[a-z0-9-]+'])
    ->middleware(\Modules\Authors\Http\Middleware\CacheAuthorMiniSite::class)
    ->name('authors.post.show');

// Newsletter subscribe par auteur (S107 P0b — Service + table + Brevo + double opt-in)
Route::post('/auteur/{slug}/newsletter/subscribe', [MiniSiteController::class, 'subscribe'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware('throttle:5,1')
    ->name('authors.newsletter.subscribe');

Route::get('/auteur/{slug}/newsletter/confirm/{token}', [MiniSiteController::class, 'confirmNewsletter'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware(['signed', 'throttle:10,1'])
    ->name('authors.newsletter.confirm');

Route::get('/auteur/{slug}/newsletter/unsubscribe/{token}', [MiniSiteController::class, 'unsubscribeNewsletter'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware(['signed', 'throttle:10,1'])
    ->name('authors.newsletter.unsubscribe');

// RFC 8058 one-click POST endpoint (Gmail/Yahoo bulk sender compliance 2026)
Route::post('/auteur/{slug}/newsletter/unsubscribe-1click/{token}', [MiniSiteController::class, 'unsubscribeOneClick'])
    ->where('slug', '[a-z0-9-]+')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('throttle:30,1')
    ->name('authors.newsletter.unsubscribe-1click');

// Dashboard auteur (auth required)
Route::middleware(['web', 'auth'])->prefix('/auteur')->group(function () {
    Route::get('/dashboard', fn () => view('authors::dashboard'))
        ->name('authors.dashboard');

    Route::get('/curation/save', fn () => response()->json(['todo' => true]))
        ->name('authors.curation.save');

    // Premium upgrade (S107 P3 — Stripe Cashier)
    Route::get('/upgrade', [UpgradeController::class, 'show'])->name('authors.upgrade');
    Route::post('/upgrade/checkout', [UpgradeController::class, 'checkout'])
        ->middleware('throttle:10,1')
        ->name('authors.upgrade.checkout');
    Route::post('/upgrade/billing-portal', [UpgradeController::class, 'billingPortal'])
        ->middleware('throttle:10,1')
        ->name('authors.upgrade.billing-portal');
});

// Route TEST LOCAL UNIQUEMENT (à retirer en prod) — preview dashboard sans auth
if (app()->environment('local')) {
    Route::get('/auteur/test-dashboard/{authorProfileId}', function ($authorProfileId) {
        return view('authors::test-dashboard', ['authorProfileId' => (int) $authorProfileId]);
    })->name('authors.test-dashboard');

    // Preview AuthorEditor (migration Tiptap) sans auth — validation visuelle Playwright
    Route::get('/auteur/test-editor', function () {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'test-editor@laveille.local'],
            ['name' => 'Auteur Test', 'password' => bcrypt(str()->random(32))]
        );
        $profile = \Modules\Authors\Models\AuthorProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['slug' => 'auteur-test-editor', 'display_name' => 'Auteur Test', 'tier' => 'free']
        );

        return view('authors::test-editor', ['authorProfile' => $profile]);
    })->name('authors.test-editor');
}

// Actions modération via signed URLs (depuis emails)
Route::middleware(['signed'])->group(function () {
    Route::get('/auteur/moderation/{article}/approve', fn () => response()->json(['todo' => true]))
        ->name('authors.moderation.approve');

    Route::get('/auteur/moderation/{article}/depublish', fn () => response()->json(['todo' => true]))
        ->name('authors.moderation.depublish');

    Route::get('/auteur/moderation/{article}/ban', fn () => response()->json(['todo' => true]))
        ->name('authors.moderation.ban');
});

// CRUD admin auteurs (legacy nwidart scaffold)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('authors', AuthorsController::class)->names('authors');
});
