<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SEO\Services\SeoService;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

Route::get('/robots.txt', function () {
    $seo = app(SeoService::class);

    return response($seo->generateRobotsTxt(), 200, [
        'Content-Type' => 'text/plain',
    ]);
});

Route::get('/sitemap.xml', function () {
    $sitemap = Sitemap::create()
        ->add(Url::create('/')->setPriority(1.0)->setChangeFrequency('daily'));

    return $sitemap->toResponse(request());
});
