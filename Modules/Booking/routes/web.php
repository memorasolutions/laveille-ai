<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;

// Admin routes
Route::middleware(['web', 'auth', 'permission:manage_booking'])
    ->prefix('admin/booking')
    ->name('admin.booking.')
    ->group(function () {
        Route::resource('services', \Modules\Booking\Http\Controllers\Admin\ServiceController::class);
        Route::resource('appointments', \Modules\Booking\Http\Controllers\Admin\AppointmentController::class);
        Route::put('appointments/{appointment}/assign', [\Modules\Booking\Http\Controllers\Admin\AppointmentController::class, 'assign'])->name('appointments.assign');
        Route::get('settings', [\Modules\Booking\Http\Controllers\Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [\Modules\Booking\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::resource('date-overrides', \Modules\Booking\Http\Controllers\Admin\DateOverrideController::class)->except(['show']);
        Route::resource('coupons', \Modules\Booking\Http\Controllers\Admin\CouponController::class)->except(['show']);
        Route::resource('packages', \Modules\Booking\Http\Controllers\Admin\PackageController::class)->except(['show']);
        Route::resource('gift-cards', \Modules\Booking\Http\Controllers\Admin\GiftCardController::class)->except(['show']);
        Route::get('analytics', [\Modules\Booking\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics');
        Route::get('analytics/export', [\Modules\Booking\Http\Controllers\Admin\AnalyticsController::class, 'exportCsv'])->name('analytics.export');
        Route::get('services/{service}/intake-questions', [\Modules\Booking\Http\Controllers\Admin\IntakeQuestionController::class, 'index'])->name('intake-questions.index');
        Route::get('services/{service}/intake-questions/create', [\Modules\Booking\Http\Controllers\Admin\IntakeQuestionController::class, 'create'])->name('intake-questions.create');
        Route::post('services/{service}/intake-questions', [\Modules\Booking\Http\Controllers\Admin\IntakeQuestionController::class, 'store'])->name('intake-questions.store');
        Route::get('intake-questions/{intakeQuestion}/edit', [\Modules\Booking\Http\Controllers\Admin\IntakeQuestionController::class, 'edit'])->name('intake-questions.edit');
        Route::put('intake-questions/{intakeQuestion}', [\Modules\Booking\Http\Controllers\Admin\IntakeQuestionController::class, 'update'])->name('intake-questions.update');
        Route::delete('intake-questions/{intakeQuestion}', [\Modules\Booking\Http\Controllers\Admin\IntakeQuestionController::class, 'destroy'])->name('intake-questions.destroy');
        Route::resource('webhooks', \Modules\Booking\Http\Controllers\Admin\BookingWebhookController::class)->except(['show']);
        Route::get('customers', [\Modules\Booking\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [\Modules\Booking\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customers.show');
        Route::put('appointments/{appointment}/approve', [\Modules\Booking\Http\Controllers\Admin\AppointmentController::class, 'approve'])->name('appointments.approve');
        Route::put('appointments/{appointment}/reject', [\Modules\Booking\Http\Controllers\Admin\AppointmentController::class, 'reject'])->name('appointments.reject');
        Route::get('calendar', [\Modules\Booking\Http\Controllers\Admin\CalendarController::class, 'index'])->name('calendar.index');
        Route::get('calendar/events', [\Modules\Booking\Http\Controllers\Admin\CalendarController::class, 'events'])->name('calendar.events');
        Route::get('dashboard', [\Modules\Booking\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    });

// Webhook Stripe (pas de middleware auth/CSRF - Stripe envoie sans session)
Route::post('webhook/stripe/booking', [\Modules\Booking\Http\Controllers\StripeWebhookController::class, 'handle'])
    ->name('booking.stripe.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Webhook SMS (pas de middleware auth - les providers SMS envoient sans session)
Route::post('webhook/sms/booking', [\Modules\Booking\Http\Controllers\SmsInboundController::class, 'handle'])
    ->name('booking.sms.inbound')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Widget embeddable (iframe, CORS ouvert)
Route::get('widget', [\Modules\Booking\Http\Controllers\WidgetController::class, 'show'])
    ->name('booking.widget');

// Public routes
Route::middleware(['web'])
    ->prefix('rendez-vous')
    ->name('booking.')
    ->group(function () {
        Route::get('/', [\Modules\Booking\Http\Controllers\BookingWizardController::class, 'index'])->name('wizard');
        Route::get('/manage/{cancel_token}', [\Modules\Booking\Http\Controllers\BookingWizardController::class, 'manage'])->name('manage');
        Route::post('/cancel/{cancel_token}', [\Modules\Booking\Http\Controllers\BookingWizardController::class, 'cancel'])->name('cancel');
        Route::get('/reschedule/{cancel_token}', [\Modules\Booking\Http\Controllers\BookingWizardController::class, 'reschedule'])->name('reschedule');
        Route::post('/reschedule/{cancel_token}', [\Modules\Booking\Http\Controllers\BookingWizardController::class, 'processReschedule'])->name('processReschedule');
    });

// Portail client (accès par token, pas d'auth)
Route::middleware(['web'])
    ->prefix('mon-portail')
    ->name('booking.portal.')
    ->group(function () {
        Route::get('/{token}', [\Modules\Booking\Http\Controllers\CustomerPortalController::class, 'index'])->name('index');
        Route::post('/{token}/cancel/{appointment}', [\Modules\Booking\Http\Controllers\CustomerPortalController::class, 'cancel'])->name('cancel');
        Route::get('/{token}/ical', [\Modules\Booking\Http\Controllers\CustomerPortalController::class, 'ical'])->name('ical');
    });
