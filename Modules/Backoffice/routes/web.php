<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Backoffice\Http\Controllers\ActivityLogController;
use Modules\Backoffice\Http\Controllers\AnalyticsController;
use Modules\Backoffice\Http\Controllers\ApiTokenController;
use Modules\Backoffice\Http\Controllers\BackofficeHealthController;
use Modules\Backoffice\Http\Controllers\BackupController;
use Modules\Backoffice\Http\Controllers\BlockedIpController;
use Modules\Backoffice\Http\Controllers\BrandingController;
use Modules\Backoffice\Http\Controllers\CacheController;
use Modules\Backoffice\Http\Controllers\CookieCategoryController;
use Modules\Backoffice\Http\Controllers\DashboardController;
use Modules\Backoffice\Http\Controllers\DataRetentionController;
use Modules\Backoffice\Http\Controllers\EmailTemplateController;
use Modules\Backoffice\Http\Controllers\ExportController;
use Modules\Backoffice\Http\Controllers\FailedJobController;
use Modules\Backoffice\Http\Controllers\FeatureFlagController;
use Modules\Backoffice\Http\Controllers\ImpersonationController;
use Modules\Backoffice\Http\Controllers\ImportController;
use Modules\Backoffice\Http\Controllers\InlineEditController;
use Modules\Backoffice\Http\Controllers\LogController;
use Modules\Backoffice\Http\Controllers\LoginHistoryController;
use Modules\Backoffice\Http\Controllers\MailLogController;
use Modules\Backoffice\Http\Controllers\MaintenanceController;
use Modules\Backoffice\Http\Controllers\MediaController;
use Modules\Backoffice\Http\Controllers\NotificationController;
use Modules\Backoffice\Http\Controllers\OnboardingStepController;
use Modules\Backoffice\Http\Controllers\PlanController;
use Modules\Backoffice\Http\Controllers\PluginController;
use Modules\Backoffice\Http\Controllers\ProfileController;
use Modules\Backoffice\Http\Controllers\PushNotificationController;
use Modules\Backoffice\Http\Controllers\RevenueController;
use Modules\Backoffice\Http\Controllers\RoleController;
use Modules\Backoffice\Http\Controllers\SchedulerController;
use Modules\Backoffice\Http\Controllers\SearchController;
use Modules\Backoffice\Http\Controllers\SecurityDashboardController;
use Modules\Backoffice\Http\Controllers\SeoController;
use Modules\Backoffice\Http\Controllers\SettingController;
use Modules\Backoffice\Http\Controllers\ShortcodeController;
use Modules\Backoffice\Http\Controllers\StatsController;
use Modules\Backoffice\Http\Controllers\SystemInfoController;
use Modules\Backoffice\Http\Controllers\TranslationController;
use Modules\Backoffice\Http\Controllers\TrashController;
use Modules\Backoffice\Http\Controllers\UserController;
use Modules\Backoffice\Http\Controllers\WebhookController;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        // ── Dashboard & Stats (accessible à tous les admins) ──
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('stats', StatsController::class)->name('stats');

        // ── Profil personnel (pas de permission spécifique, propre profil) ──
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('profile/two-factor/enable', [ProfileController::class, 'enableTwoFactor'])->name('profile.2fa.enable');
        Route::post('profile/two-factor/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.2fa.confirm');
        Route::delete('profile/two-factor', [ProfileController::class, 'disableTwoFactor'])->name('profile.2fa.disable');
        Route::get('profile/tokens', [ApiTokenController::class, 'index'])->name('profile.tokens.index');
        Route::post('profile/tokens', [ApiTokenController::class, 'store'])->name('profile.tokens.store');
        Route::delete('profile/tokens/{id}', [ApiTokenController::class, 'destroy'])->name('profile.tokens.destroy');

        // ── Recherche globale (utilitaire, pas de permission spécifique) ──
        Route::get('search', [SearchController::class, 'index'])->name('search')->middleware('throttle:search');

        // ── Inline editing (vérifie la permission par entité dans le contrôleur) ──
        Route::patch('inline/{entity}/{id}', [InlineEditController::class, 'update'])->name('inline.update');

        // ── Analytics (accessible avec view_dashboard) ──
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('overview', [AnalyticsController::class, 'overview'])->name('overview');
            Route::get('webhooks', [AnalyticsController::class, 'webhooks'])->name('webhooks');
            Route::get('content', [AnalyticsController::class, 'content'])->name('content');
            Route::get('activity', [AnalyticsController::class, 'activity'])->name('activity');
        });

        // ── Gestion des utilisateurs ──
        Route::middleware('permission:manage_users')->group(function () {
            Route::resource('users', UserController::class);
            Route::post('users/{user}/impersonate', [ImpersonationController::class, 'impersonate'])->name('users.impersonate');
            Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        });

        // ── Gestion des rôles ──
        Route::middleware('permission:manage_roles')->group(function () {
            Route::resource('roles', RoleController::class);
        });

        // ── Menus ──
        Route::middleware('permission:manage_menus')->group(function () {
            Route::resource('menus', \Modules\Menu\Http\Controllers\MenuController::class);
            Route::post('menus/{menu}/save-items', [\Modules\Menu\Http\Controllers\MenuController::class, 'saveItems'])->name('menus.save-items');
        });

        // ── Paramètres ──
        Route::middleware('permission:manage_settings')->group(function () {
            Route::resource('settings', SettingController::class)->except(['show']);
        });

        // ── Branding ──
        Route::middleware('permission:manage_branding')->group(function () {
            Route::get('branding', [BrandingController::class, 'edit'])->name('branding.edit');
            Route::put('branding', [BrandingController::class, 'update'])->name('branding.update');
        });

        // ── Logs d'activité ──
        Route::middleware('permission:manage_activity_logs')->group(function () {
            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
            Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
            Route::delete('activity-logs/purge', [ActivityLogController::class, 'purge'])->name('activity-logs.purge');
        });

        // ── Notifications ──
        Route::middleware('permission:manage_notifications')->group(function () {
            Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('notifications/broadcast', [NotificationController::class, 'broadcast'])->name('notifications.broadcast');
            Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
            Route::get('push-notifications', [PushNotificationController::class, 'index'])->name('push-notifications.index');
            Route::post('push-notifications', [PushNotificationController::class, 'store'])->name('push-notifications.store');
        });

        // ── Sauvegardes ──
        Route::middleware('permission:manage_backups')->group(function () {
            Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('backups/run', [BackupController::class, 'run'])->name('backups.run');
            Route::get('backups/download', [BackupController::class, 'download'])->name('backups.download');
            Route::delete('backups/delete', [BackupController::class, 'delete'])->name('backups.delete');
        });

        // ── Export CSV ──
        Route::middleware(['permission:manage_exports', 'throttle:export'])->group(function () {
            Route::get('export/users', [ExportController::class, 'users'])->name('export.users');
            Route::get('export/roles', [ExportController::class, 'roles'])->name('export.roles');
            Route::get('export/settings', [ExportController::class, 'settings'])->name('export.settings');
            Route::get('export/articles', [ExportController::class, 'articles'])->name('export.articles');
            Route::get('export/categories', [ExportController::class, 'categories'])->name('export.categories');
            Route::get('export/plans', [ExportController::class, 'plans'])->name('export.plans');
            Route::get('export/campaigns', [ExportController::class, 'campaigns'])->name('export.campaigns');
            Route::get('export/pages', [ExportController::class, 'pages'])->name('export.pages');
            Route::get('export/comments', [ExportController::class, 'comments'])->name('export.comments');
        });

        // ── Import CSV ──
        Route::middleware(['permission:manage_imports', 'throttle:import'])->group(function () {
            Route::get('import/users', [ImportController::class, 'showForm'])->name('import.users');
            Route::post('import/users', [ImportController::class, 'importUsers'])->name('import.users.store');
            Route::get('import/articles', [ImportController::class, 'showFormArticles'])->name('import.articles');
            Route::post('import/articles', [ImportController::class, 'importArticles'])->name('import.articles.store');
            Route::get('import/categories', [ImportController::class, 'showFormCategories'])->name('import.categories');
            Route::post('import/categories', [ImportController::class, 'importCategories'])->name('import.categories.store');
            Route::get('import/subscribers', [ImportController::class, 'showFormSubscribers'])->name('import.subscribers');
            Route::post('import/subscribers', [ImportController::class, 'importSubscribers'])->name('import.subscribers.store');
            Route::get('import/plans', [ImportController::class, 'showFormPlans'])->name('import.plans');
            Route::post('import/plans', [ImportController::class, 'importPlans'])->name('import.plans.store');
            Route::get('import/pages', [ImportController::class, 'showFormPages'])->name('import.pages');
            Route::post('import/pages', [ImportController::class, 'importPages'])->name('import.pages.store');
            Route::get('import/comments', [ImportController::class, 'showFormComments'])->name('import.comments');
            Route::post('import/comments', [ImportController::class, 'importComments'])->name('import.comments.store');
        });
        Route::middleware('permission:manage_imports')->group(function () {
            Route::get('import/template/{type}', [ImportController::class, 'template'])->name('import.template');
        });

        // ── Webhooks ──
        Route::middleware('permission:manage_webhooks')->group(function () {
            Route::get('webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
            Route::post('webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
            Route::delete('webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
        });

        // ── Plans SaaS ──
        Route::middleware('permission:manage_plans')->group(function () {
            Route::resource('plans', PlanController::class)->except(['show']);
            Route::get('revenue', [RevenueController::class, 'index'])->name('revenue');
            Route::get('revenue/metrics', [RevenueController::class, 'metrics'])->name('revenue.metrics');
        });

        // ── Feature Flags ──
        Route::middleware('permission:manage_feature_flags')->group(function () {
            Route::get('feature-flags', [FeatureFlagController::class, 'index'])->name('feature-flags.index');
            Route::post('feature-flags/{name}', [FeatureFlagController::class, 'toggle'])->name('feature-flags.toggle');
            Route::post('feature-flags/{name}/conditions', [FeatureFlagController::class, 'updateConditions'])->name('feature-flags.conditions');
        });

        // ── Plugins & Modules (manage_roles car administration système) ──
        Route::middleware('permission:manage_roles')->group(function () {
            Route::get('plugins', [PluginController::class, 'index'])->name('plugins.index');
            Route::post('plugins/{name}/toggle', [PluginController::class, 'toggle'])->name('plugins.toggle');
        });

        // ── SEO ──
        Route::middleware('permission:manage_seo')->group(function () {
            Route::resource('seo', SeoController::class)->except(['show'])->parameters(['seo' => 'metaTag']);
        });

        // ── Médias ──
        Route::middleware('permission:manage_media')->group(function () {
            Route::get('media', [MediaController::class, 'index'])->name('media.index');
            Route::delete('media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
        });

        // ── Santé système ──
        Route::middleware('permission:view_health')->group(function () {
            Route::get('health', [BackofficeHealthController::class, 'index'])->name('health');
            Route::post('health/refresh', [BackofficeHealthController::class, 'refresh'])->name('health.refresh');
        });

        // ── Journaux application ──
        Route::middleware('permission:view_logs')->group(function () {
            Route::get('logs', [LogController::class, 'index'])->name('logs');
            Route::post('logs/clear', [LogController::class, 'clear'])->name('logs.clear');
        });

        // ── Gestion système (maintenance, cache, jobs, scheduler, etc.) ──
        Route::middleware('permission:manage_system')->group(function () {
            Route::get('failed-jobs', [FailedJobController::class, 'index'])->name('failed-jobs.index');
            Route::post('failed-jobs/{id}/retry', [FailedJobController::class, 'retry'])->name('failed-jobs.retry');
            Route::delete('failed-jobs/{id}', [FailedJobController::class, 'destroy'])->name('failed-jobs.destroy');
            Route::delete('failed-jobs', [FailedJobController::class, 'destroyAll'])->name('failed-jobs.destroy-all');
            Route::post('maintenance/toggle', [MaintenanceController::class, 'toggle'])->name('maintenance.toggle');
            Route::get('scheduler', [SchedulerController::class, 'index'])->name('scheduler');
            Route::get('mail-log', [MailLogController::class, 'index'])->name('mail-log');
            Route::get('system-info', [SystemInfoController::class, 'index'])->name('system-info');
            Route::get('data-retention', [DataRetentionController::class, 'index'])->name('data-retention');
            Route::get('cache', [CacheController::class, 'index'])->name('cache');
            Route::post('cache/clear-cache', [CacheController::class, 'clearCache'])->name('cache.clear-cache');
            Route::post('cache/clear-config', [CacheController::class, 'clearConfig'])->name('cache.clear-config');
            Route::post('cache/clear-views', [CacheController::class, 'clearViews'])->name('cache.clear-views');
            Route::post('cache/clear-routes', [CacheController::class, 'clearRoutes'])->name('cache.clear-routes');
            Route::post('cache/clear-all', [CacheController::class, 'clearAll'])->name('cache.clear-all');
        });

        // ── Sécurité (dashboard, IPs bloquées, historique connexions) ──
        Route::middleware('permission:manage_security')->group(function () {
            Route::get('security', [SecurityDashboardController::class, 'index'])->name('security');
            Route::get('blocked-ips', [BlockedIpController::class, 'index'])->name('blocked-ips.index');
            Route::post('blocked-ips', [BlockedIpController::class, 'store'])->name('blocked-ips.store');
            Route::delete('blocked-ips/{blockedIp}', [BlockedIpController::class, 'destroy'])->name('blocked-ips.destroy');
            Route::get('login-history', [LoginHistoryController::class, 'index'])->name('login-history');
        });

        // ── Corbeille ──
        Route::middleware('permission:manage_trash')->group(function () {
            Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
            Route::post('trash/articles/{id}/restore', [TrashController::class, 'restoreArticle'])->name('trash.restore-article');
            Route::post('trash/comments/{id}/restore', [TrashController::class, 'restoreComment'])->name('trash.restore-comment');
            Route::delete('trash/articles/{id}', [TrashController::class, 'forceDeleteArticle'])->name('trash.force-delete-article');
            Route::delete('trash/comments/{id}', [TrashController::class, 'forceDeleteComment'])->name('trash.force-delete-comment');
        });

        // ── Templates email ──
        Route::middleware('permission:manage_email_templates')->group(function () {
            Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
            Route::get('email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
            Route::put('email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
            Route::get('email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
            Route::post('email-templates/{emailTemplate}/reset', [EmailTemplateController::class, 'resetToDefault'])->name('email-templates.reset');
        });

        // ── Catégories cookies (RGPD) ──
        Route::middleware('permission:manage_cookies')->group(function () {
            Route::resource('cookie-categories', CookieCategoryController::class);
        });

        // ── Onboarding ──
        Route::middleware('permission:manage_onboarding')->group(function () {
            Route::resource('onboarding-steps', OnboardingStepController::class)->only(['index', 'edit', 'update']);
        });

        // ── Shortcodes ──
        Route::middleware('permission:manage_shortcodes')->group(function () {
            Route::resource('shortcodes', ShortcodeController::class)->except(['show']);
        });

        // ── Traductions ──
        Route::middleware('permission:manage_translations')->group(function () {
            Route::get('translations', [TranslationController::class, 'index'])->name('translations.index');
            Route::get('translations/export/{locale}', [TranslationController::class, 'export'])->name('translations.export');
        });

        // ── API tokens (gestion) ──
        Route::middleware('permission:manage_api')->group(function () {
            // API token management routes can be added here
        });
    });

// Impersonation stop - accessible by impersonated users (no EnsureIsAdmin)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::post('impersonate/stop', [ImpersonationController::class, 'stopImpersonating'])->name('impersonate.stop');
    });
