<?php

declare(strict_types=1);

use App\Http\Controllers\ContactController;
use App\Http\Controllers\CookieConsentController;
use Modules\Faq\Http\Controllers\PublicFaqController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Modules\Blog\Models\Article;
use Modules\SaaS\Models\Plan;
use Modules\Settings\Models\Setting;

// Sitemap dynamique
Route::get('/sitemap.xml', [\Modules\SEO\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

Route::middleware('cacheResponse')->group(function () {
    Route::get('/', function () {
        $homepageType = Setting::get('homepage.type', 'landing');
        $pageId = Setting::get('homepage.page_id');

        if ($homepageType === 'page' && $pageId && class_exists(\Modules\Pages\Models\StaticPage::class)) {
            $page = \Modules\Pages\Models\StaticPage::where('status', 'published')->find($pageId);
            if ($page) {
                $template = $page->template ?? 'default';
                $viewName = "pages::public.templates.{$template}";
                if (! view()->exists($viewName)) {
                    $viewName = 'pages::public.templates.default';
                }

                return view($viewName, compact('page'));
            }
        }

        return view('landing', [
            'plans' => Plan::active()->ordered()->get(),
            'recentPosts' => Article::published()->latest('published_at')->take(3)->get(),
        ]);
    })->name('home');

    Route::get('/faq', [PublicFaqController::class, 'show'])->name('faq.show');
    Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');

    Route::get('/pricing', function () {
        return view('pricing', [
            'plans' => \Modules\SaaS\Models\Plan::active()->ordered()->get(),
        ]);
    })->name('pricing');

    Route::view('/about', 'about')->name('about');
    Route::view('/legal', 'legal')->name('legal');
    Route::view('/privacy', 'privacy')->name('privacy');
    Route::view('/terms', 'terms')->name('terms');
});

Route::post('/contact', [ContactController::class, 'send'])->name('contact.send')->middleware('honeypot');

// Cookie consent
Route::get('/cookie-preferences', [CookieConsentController::class, 'preferences'])->name('cookie.preferences');
Route::post('/cookie-consent/accept', [CookieConsentController::class, 'accept'])->name('cookie.accept');
Route::post('/cookie-consent/decline', [CookieConsentController::class, 'decline'])->name('cookie.decline');
Route::post('/cookie-consent/customize', [CookieConsentController::class, 'customize'])->name('cookie.customize');

// Language switcher
Route::post('/locale/{locale}', LocaleController::class)->name('locale.switch');

// La route /dashboard est gérée par le module Auth (UserDashboardController)
// Voir Modules/Auth/routes/web.php
