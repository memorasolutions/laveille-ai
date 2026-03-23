<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Backoffice\Http\Controllers\ActivityLogController;
use Modules\Backoffice\Http\Controllers\AnalyticsController;
use Modules\Backoffice\Http\Controllers\AnnouncementController;
use Modules\Backoffice\Http\Controllers\ApiTokenController;
use Modules\Backoffice\Http\Controllers\BackofficeHealthController;
use Modules\Backoffice\Http\Controllers\BackupController;
use Modules\Backoffice\Http\Controllers\BlockedIpController;
use Modules\Backoffice\Http\Controllers\BrandingController;
use Modules\Backoffice\Http\Controllers\CacheController;
use Modules\Backoffice\Http\Controllers\ContactMessageController;
use Modules\Backoffice\Http\Controllers\CookieCategoryController;
use Modules\Backoffice\Http\Controllers\DashboardController;
use Modules\Backoffice\Http\Controllers\DataRetentionController;
use Modules\Backoffice\Http\Controllers\DocumentationController;
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
use Modules\Backoffice\Http\Controllers\UrlRedirectController;
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
        Route::post('profile/sessions/{id}/revoke', [ProfileController::class, 'revokeSession'])->name('profile.sessions.revoke');
        Route::post('profile/sessions/revoke-others', [ProfileController::class, 'revokeOtherSessions'])->name('profile.sessions.revoke-others');

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
        Route::get('users/create', [UserController::class, 'create'])->name('users.create')->middleware('permission:create_users');
        Route::middleware('permission:view_users')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        });
        Route::post('users', [UserController::class, 'store'])->name('users.store')->middleware('permission:create_users');
        Route::middleware('permission:update_users')->group(function () {
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('users/{user}/impersonate', [ImpersonationController::class, 'impersonate'])->name('users.impersonate');
            Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        });
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('permission:delete_users');

        // ── Gestion des rôles ──
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create')->middleware('permission:create_roles');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store')->middleware('permission:create_roles');
        Route::middleware('permission:view_roles')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        });
        Route::middleware('permission:update_roles')->group(function () {
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy')->middleware('permission:delete_roles');

        // ── Menus ──
        Route::middleware('permission:view_menus')->group(function () {
            Route::get('menus', [\Modules\Menu\Http\Controllers\MenuController::class, 'index'])->name('menus.index');
        });
        Route::middleware('permission:create_menus')->group(function () {
            Route::get('menus/create', [\Modules\Menu\Http\Controllers\MenuController::class, 'create'])->name('menus.create');
            Route::post('menus', [\Modules\Menu\Http\Controllers\MenuController::class, 'store'])->name('menus.store');
        });
        Route::get('menus/{menu}', [\Modules\Menu\Http\Controllers\MenuController::class, 'show'])->name('menus.show')->middleware('permission:view_menus');
        Route::middleware('permission:update_menus')->group(function () {
            Route::get('menus/{menu}/edit', [\Modules\Menu\Http\Controllers\MenuController::class, 'edit'])->name('menus.edit');
            Route::put('menus/{menu}', [\Modules\Menu\Http\Controllers\MenuController::class, 'update'])->name('menus.update');
            Route::post('menus/{menu}/save-items', [\Modules\Menu\Http\Controllers\MenuController::class, 'saveItems'])->name('menus.save-items');
        });
        Route::delete('menus/{menu}', [\Modules\Menu\Http\Controllers\MenuController::class, 'destroy'])->name('menus.destroy')->middleware('permission:delete_menus');

        // ── Messages contact ──
        Route::middleware('permission:view_contacts')->group(function () {
            Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
            Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
        });
        Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy')->middleware('permission:delete_contacts');

        // ── FAQ ──
        Route::middleware('permission:view_faqs')->group(function () {
            Route::get('faqs', [\Modules\Faq\Http\Controllers\FaqController::class, 'index'])->name('faqs.index');
        });
        Route::middleware('permission:create_faqs')->group(function () {
            Route::get('faqs/create', [\Modules\Faq\Http\Controllers\FaqController::class, 'create'])->name('faqs.create');
            Route::post('faqs', [\Modules\Faq\Http\Controllers\FaqController::class, 'store'])->name('faqs.store');
            Route::post('faqs/reorder', [\Modules\Faq\Http\Controllers\FaqController::class, 'reorder'])->name('faqs.reorder');
        });
        Route::middleware('permission:update_faqs')->group(function () {
            Route::get('faqs/{faq}/edit', [\Modules\Faq\Http\Controllers\FaqController::class, 'edit'])->name('faqs.edit');
            Route::put('faqs/{faq}', [\Modules\Faq\Http\Controllers\FaqController::class, 'update'])->name('faqs.update');
        });
        Route::get('faqs/{faq}', [\Modules\Faq\Http\Controllers\FaqController::class, 'show'])->name('faqs.show')->middleware('permission:view_faqs');
        Route::delete('faqs/{faq}', [\Modules\Faq\Http\Controllers\FaqController::class, 'destroy'])->name('faqs.destroy')->middleware('permission:delete_faqs');

        // ── Témoignages (module Testimonials requis) ──
        if (\Nwidart\Modules\Facades\Module::find('Testimonials')?->isEnabled()) {
            Route::get('testimonials', [\Modules\Testimonials\Http\Controllers\TestimonialController::class, 'index'])->name('testimonials.index')->middleware('permission:view_testimonials');
            Route::middleware('permission:create_testimonials')->group(function () {
                Route::get('testimonials/create', [\Modules\Testimonials\Http\Controllers\TestimonialController::class, 'create'])->name('testimonials.create');
                Route::post('testimonials', [\Modules\Testimonials\Http\Controllers\TestimonialController::class, 'store'])->name('testimonials.store');
                Route::post('testimonials/reorder', [\Modules\Testimonials\Http\Controllers\TestimonialController::class, 'reorder'])->name('testimonials.reorder');
            });
            Route::middleware('permission:update_testimonials')->group(function () {
                Route::get('testimonials/{testimonial}/edit', [\Modules\Testimonials\Http\Controllers\TestimonialController::class, 'edit'])->name('testimonials.edit');
                Route::put('testimonials/{testimonial}', [\Modules\Testimonials\Http\Controllers\TestimonialController::class, 'update'])->name('testimonials.update');
            });
            Route::delete('testimonials/{testimonial}', [\Modules\Testimonials\Http\Controllers\TestimonialController::class, 'destroy'])->name('testimonials.destroy')->middleware('permission:delete_testimonials');
        }

        // ── Media API (pour TipTap editor) ──
        Route::get('media-api', [\Modules\Media\Http\Controllers\MediaController::class, 'index'])->name('media-api.index')->middleware('permission:view_media');
        Route::middleware('permission:manage_media')->group(function () {
            Route::post('media-api', [\Modules\Media\Http\Controllers\MediaController::class, 'store'])->name('media-api.store');
            Route::patch('media-api/{id}', [\Modules\Media\Http\Controllers\MediaController::class, 'update'])->name('media-api.update');
            Route::delete('media-api/{id}', [\Modules\Media\Http\Controllers\MediaController::class, 'destroy'])->name('media-api.destroy');
            Route::post('media-api/{id}/crop', [\Modules\Media\Http\Controllers\MediaController::class, 'crop'])->name('media-api.crop');
        });

        // ── Paramètres ──
        Route::middleware('permission:view_settings')->group(function () {
            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        });
        Route::middleware('permission:manage_settings')->group(function () {
            Route::get('settings/create', [SettingController::class, 'create'])->name('settings.create');
            Route::post('settings', [SettingController::class, 'store'])->name('settings.store');
            Route::get('settings/{setting}/edit', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings/{setting}', [SettingController::class, 'update'])->name('settings.update');
            Route::delete('settings/{setting}', [SettingController::class, 'destroy'])->name('settings.destroy');
        });

        // ── Branding ──
        Route::middleware('permission:manage_branding')->group(function () {
            Route::get('branding', [BrandingController::class, 'edit'])->name('branding.edit');
            Route::put('branding', [BrandingController::class, 'update'])->name('branding.update');
        });

        // ── Logs d'activité ──
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index')->middleware('permission:view_activity_logs');
        Route::middleware('permission:manage_activity_logs')->group(function () {
            Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
            Route::delete('activity-logs/purge', [ActivityLogController::class, 'purge'])->name('activity-logs.purge');
        });

        // ── Notifications ──
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('permission:view_notifications');
        Route::middleware('permission:manage_notifications')->group(function () {
            Route::post('notifications/broadcast', [NotificationController::class, 'broadcast'])->name('notifications.broadcast');
            Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
            Route::get('push-notifications', [PushNotificationController::class, 'index'])->name('push-notifications.index');
            Route::post('push-notifications', [PushNotificationController::class, 'store'])->name('push-notifications.store');
        });

        // ── Sauvegardes ──
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index')->middleware('permission:view_backups');
        Route::middleware('permission:manage_backups')->group(function () {
            Route::post('backups/run', [BackupController::class, 'run'])->name('backups.run');
            Route::get('backups/download', [BackupController::class, 'download'])->name('backups.download');
            Route::delete('backups/delete', [BackupController::class, 'delete'])->name('backups.delete');
            Route::delete('backups/bulk-delete', [BackupController::class, 'bulkDelete'])->name('backups.bulk-delete');
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
        Route::get('import/template/{type}', [ImportController::class, 'template'])->name('import.template')->middleware('permission:manage_imports');

        // ── Webhooks ──
        Route::get('webhooks', [WebhookController::class, 'index'])->name('webhooks.index')->middleware('permission:view_webhooks');
        Route::middleware('permission:manage_webhooks')->group(function () {
            Route::post('webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
            Route::delete('webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
        });

        // ── Plans SaaS (module SaaS requis) ──
        if (\Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
            Route::middleware('permission:view_plans')->group(function () {
                Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
                Route::get('revenue', [RevenueController::class, 'index'])->name('revenue');
                Route::get('revenue/metrics', [RevenueController::class, 'metrics'])->name('revenue.metrics');
            });
            Route::middleware('permission:create_plans')->group(function () {
                Route::get('plans/create', [PlanController::class, 'create'])->name('plans.create');
                Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
            });
            Route::middleware('permission:update_plans')->group(function () {
                Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
                Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
            });
            Route::delete('plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy')->middleware('permission:delete_plans');
        }

        // ── Feature Flags ──
        Route::get('feature-flags', [FeatureFlagController::class, 'index'])->name('feature-flags.index')->middleware('permission:view_feature_flags');
        Route::middleware('permission:manage_feature_flags')->group(function () {
            Route::post('feature-flags/{name}', [FeatureFlagController::class, 'toggle'])->name('feature-flags.toggle');
            Route::post('feature-flags/{name}/conditions', [FeatureFlagController::class, 'updateConditions'])->name('feature-flags.conditions');
        });

        // ── Tests A/B (module ABTest requis) ──
        if (\Nwidart\Modules\Facades\Module::find('ABTest')?->isEnabled()) {
            Route::middleware('permission:view_feature_flags')->group(function () {
                Route::get('experiments', [\Modules\ABTest\Http\Controllers\ExperimentController::class, 'index'])->name('experiments.index');
                Route::get('experiments/{experiment}', [\Modules\ABTest\Http\Controllers\ExperimentController::class, 'show'])->name('experiments.show');
            });
            Route::middleware('permission:create_feature_flags')->group(function () {
                Route::get('experiments/create', [\Modules\ABTest\Http\Controllers\ExperimentController::class, 'create'])->name('experiments.create');
                Route::post('experiments', [\Modules\ABTest\Http\Controllers\ExperimentController::class, 'store'])->name('experiments.store');
            });
            Route::middleware('permission:manage_feature_flags')->group(function () {
                Route::post('experiments/{experiment}/start', [\Modules\ABTest\Http\Controllers\ExperimentController::class, 'start'])->name('experiments.start');
                Route::post('experiments/{experiment}/complete', [\Modules\ABTest\Http\Controllers\ExperimentController::class, 'complete'])->name('experiments.complete');
                Route::delete('experiments/{experiment}', [\Modules\ABTest\Http\Controllers\ExperimentController::class, 'destroy'])->name('experiments.destroy');
            });
        }

        // ── Plugins & Modules ──
        Route::middleware('permission:manage_system')->group(function () {
            Route::get('plugins', [PluginController::class, 'index'])->name('plugins.index');
            Route::post('plugins/{name}/toggle', [PluginController::class, 'toggle'])->name('plugins.toggle');
        });

        // ── Annonces ──
        Route::middleware('permission:view_settings')->group(function () {
            Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        });
        Route::middleware('permission:manage_settings')->group(function () {
            Route::get('announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
            Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
            Route::get('announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
            Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
            Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        });

        // ── SEO ──
        Route::middleware('permission:view_seo')->group(function () {
            Route::get('seo', [SeoController::class, 'index'])->name('seo.index');
            Route::get('redirects', [UrlRedirectController::class, 'index'])->name('redirects.index');
        });
        Route::middleware('permission:manage_seo')->group(function () {
            Route::get('seo/create', [SeoController::class, 'create'])->name('seo.create');
            Route::post('seo', [SeoController::class, 'store'])->name('seo.store');
            Route::get('seo/{metaTag}/edit', [SeoController::class, 'edit'])->name('seo.edit');
            Route::put('seo/{metaTag}', [SeoController::class, 'update'])->name('seo.update');
            Route::delete('seo/{metaTag}', [SeoController::class, 'destroy'])->name('seo.destroy');
            Route::post('redirects', [UrlRedirectController::class, 'store'])->name('redirects.store');
            Route::put('redirects/{redirect}', [UrlRedirectController::class, 'update'])->name('redirects.update');
            Route::delete('redirects/{redirect}', [UrlRedirectController::class, 'destroy'])->name('redirects.destroy');
        });

        // ── Médias ──
        Route::get('media', [MediaController::class, 'index'])->name('media.index')->middleware('permission:view_media');
        Route::delete('media/{id}', [MediaController::class, 'destroy'])->name('media.destroy')->middleware('permission:manage_media');

        // ── Santé système ──
        Route::middleware('permission:view_health')->group(function () {
            Route::get('health', [BackofficeHealthController::class, 'index'])->name('health');
            Route::post('health/refresh', [BackofficeHealthController::class, 'refresh'])->name('health.refresh');
            Route::post('health/fix', [BackofficeHealthController::class, 'fix'])->name('health.fix');
            Route::post('health/explain', [BackofficeHealthController::class, 'explain'])->name('health.explain');
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
            Route::get('scheduler/create', [SchedulerController::class, 'create'])->name('scheduler.create');
            Route::post('scheduler', [SchedulerController::class, 'store'])->name('scheduler.store');
            Route::get('scheduler/{scheduledTask}/edit', [SchedulerController::class, 'edit'])->name('scheduler.edit');
            Route::put('scheduler/{scheduledTask}', [SchedulerController::class, 'update'])->name('scheduler.update');
            Route::delete('scheduler/{scheduledTask}', [SchedulerController::class, 'destroy'])->name('scheduler.destroy');
            Route::post('scheduler/{scheduledTask}/toggle', [SchedulerController::class, 'toggle'])->name('scheduler.toggle');
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
        Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index')->middleware('permission:view_email_templates');
        Route::get('email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview')->middleware('permission:view_email_templates');
        Route::middleware('permission:manage_email_templates')->group(function () {
            Route::get('email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
            Route::put('email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
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
        Route::get('translations', [TranslationController::class, 'index'])->name('translations.index')->middleware('permission:view_translations');
        Route::get('translations/export/{locale}', [TranslationController::class, 'export'])->name('translations.export')->middleware('permission:manage_translations');

        // ── API tokens (gestion) ──
        Route::middleware('permission:manage_api')->group(function () {
            // API token management routes can be added here
        });

        // ── Documentation ──
        Route::get('documentation', [DocumentationController::class, 'index'])
            ->name('documentation')
            ->middleware('permission:view_documentation');
    });

// Impersonation stop - accessible by impersonated users (no EnsureIsAdmin)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::post('impersonate/stop', [ImpersonationController::class, 'stopImpersonating'])->name('impersonate.stop');
    });
