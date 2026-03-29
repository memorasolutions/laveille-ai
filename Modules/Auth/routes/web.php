<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\EmailVerificationController;
use Modules\Auth\Http\Controllers\ForcePasswordChangeController;
use Modules\Auth\Http\Controllers\MagicLinkController;
use Modules\Auth\Http\Controllers\NotificationPreferenceController;
use Modules\Auth\Http\Controllers\OnboardingController;
use Modules\Auth\Http\Controllers\PasswordConfirmationController;
use Modules\Auth\Http\Controllers\PrivacyCenterController;
use Modules\Auth\Http\Controllers\SocialAuthController;
use Modules\Auth\Http\Controllers\TwoFactorProfileController;
use Modules\Auth\Http\Controllers\UserActivityController;
use Modules\Auth\Http\Controllers\UserApiTokenController;
use Modules\Auth\Http\Controllers\UserArticleController;
use Modules\Auth\Http\Controllers\UserContributionsController;
use Modules\Auth\Http\Controllers\UserDashboardController;
use Modules\Auth\Http\Controllers\UserNotificationsController;
use Modules\Auth\Http\Controllers\UserSessionController;
use Modules\Auth\Http\Controllers\UserSubscriptionController;
use Modules\Auth\Livewire\ForgotPassword;
use Modules\Auth\Livewire\Login;
use Modules\Auth\Livewire\Register;
use Modules\Auth\Livewire\ResetPassword;
use Modules\Auth\Livewire\TwoFactorChallenge;
use Modules\Auth\Services\AuthService;

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

// MagicLink - connexion sans mot de passe avec code 6 caractères
Route::middleware(['guest', 'throttle:5,1'])->group(function () {
    Route::get('/magic-link', [MagicLinkController::class, 'showRequestForm'])->name('magic-link.request');
    Route::post('/magic-link', [MagicLinkController::class, 'sendLink'])->name('magic-link.send');
    Route::get('/magic-link/verify', [MagicLinkController::class, 'showVerifyForm'])->name('magic-link.verify');
    Route::post('/magic-link/verify', [MagicLinkController::class, 'verify'])->name('magic-link.confirm');
    Route::post('/magic-link/sms', [MagicLinkController::class, 'sendSms'])->name('magic-link.sms')->middleware('throttle:3,1');
});

// API magic-link JSON (pour auth inline sans redirection)
Route::middleware(['web', 'throttle:5,1'])->group(function () {
    Route::post('/api/magic-link/send', [MagicLinkController::class, 'sendLinkApi'])->name('magic-link.api.send');
    Route::post('/api/magic-link/verify', [MagicLinkController::class, 'verifyApi'])->name('magic-link.api.verify');
});

// Route 2FA : accessible sans auth (l'utilisateur est temporairement déconnecté)
Route::get('/two-factor-challenge', TwoFactorChallenge::class)->name('auth.two-factor-challenge');

// Force password change (accessible uniquement si must_change_password = true)
Route::middleware('auth')->group(function () {
    Route::get('/password/force-change', [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::post('/password/force-change', [ForcePasswordChangeController::class, 'update'])->name('password.force-change.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function (AuthService $authService) {
        $authService->logout();

        return redirect('/');
    })->name('logout');
});

// Social Auth (Google, GitHub)
Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

// Email verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify')->middleware('signed');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->name('verification.send')->middleware('throttle:6,1');
});

// Onboarding wizard
Route::middleware('auth')->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
});

// User Dashboard (espace personnel, auth requise, layout frontend USNews)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/user/profile', [UserDashboardController::class, 'profile'])->name('user.profile');
    Route::put('/user/profile', [UserDashboardController::class, 'updateProfile'])->name('user.profile.update');
    Route::put('/user/password', [UserDashboardController::class, 'updatePassword'])->name('user.password.update');

    // Gestion articles utilisateur
    Route::get('/user/articles', [UserArticleController::class, 'index'])->name('user.articles.index');
    Route::get('/user/articles/create', [UserArticleController::class, 'create'])->name('user.articles.create');
    Route::post('/user/articles', [UserArticleController::class, 'store'])->name('user.articles.store');
    Route::get('/user/articles/{article}/edit', [UserArticleController::class, 'edit'])->name('user.articles.edit');
    Route::put('/user/articles/{article}', [UserArticleController::class, 'update'])->name('user.articles.update');
    Route::delete('/user/articles/{article}', [UserArticleController::class, 'destroy'])->name('user.articles.destroy');

    // Notifications utilisateur
    Route::get('/user/notifications', [UserNotificationsController::class, 'index'])->name('user.notifications');
    Route::post('/user/notifications/mark-all-read', [UserNotificationsController::class, 'markAllRead'])->name('user.notifications.markAllRead');
    Route::post('/user/notifications/{id}/read', [UserNotificationsController::class, 'markRead'])->name('user.notifications.markRead');
    Route::delete('/user/notifications/all', [UserNotificationsController::class, 'destroyAll'])->name('user.notifications.destroyAll');
    Route::delete('/user/notifications/{id}', [UserNotificationsController::class, 'destroy'])->name('user.notifications.destroy');
    Route::put('/user/notifications/frequency', [UserNotificationsController::class, 'updateFrequency'])->name('user.notifications.updateFrequency');

    // Préférences de notification par type/canal
    Route::get('/user/notification-preferences', [NotificationPreferenceController::class, 'index'])->name('user.notification-preferences');
    Route::put('/user/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('user.notification-preferences.update');

    // Abonnement SaaS
    Route::get('/user/subscription', [UserSubscriptionController::class, 'index'])->name('user.subscription');
    Route::post('/user/subscription/cancel', [UserSubscriptionController::class, 'cancel'])->name('user.subscription.cancel');
    Route::post('/user/subscription/resume', [UserSubscriptionController::class, 'resume'])->name('user.subscription.resume');
    Route::post('/user/subscription/swap', [UserSubscriptionController::class, 'swapPlan'])->name('user.subscription.swap');
    Route::get('/user/invoices', [UserSubscriptionController::class, 'invoices'])->name('user.invoices');
    Route::get('/user/invoices/{invoice}', [UserSubscriptionController::class, 'downloadInvoice'])->name('user.invoices.download');

    // Centre de confidentialité RGPD
    Route::get('/user/privacy', [PrivacyCenterController::class, 'index'])->name('user.privacy');

    // Suppression compte + export RGPD
    Route::delete('/user/account', [UserDashboardController::class, 'deleteAccount'])->name('user.account.delete');
    Route::get('/user/export-data', [UserDashboardController::class, 'exportData'])->name('user.export-data')->middleware('throttle:export');

    // Confirmation mot de passe (middleware password.confirm)
    Route::get('/confirm-password', [PasswordConfirmationController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [PasswordConfirmationController::class, 'confirm'])->name('password.confirm.post');

    // Passkeys (clés d'accès WebAuthn)
    Route::get('/user/passkeys', fn () => view('auth::themes.backend.profile.passkeys'))->name('user.passkeys');

    // 2FA gestion profil
    Route::get('/user/two-factor/setup', [TwoFactorProfileController::class, 'setup'])->name('user.two-factor.setup');
    Route::post('/user/two-factor/confirm', [TwoFactorProfileController::class, 'confirm'])->name('user.two-factor.confirm');
    Route::post('/user/two-factor/disable', [TwoFactorProfileController::class, 'disable'])->name('user.two-factor.disable');
    Route::get('/user/two-factor/recovery-codes', [TwoFactorProfileController::class, 'recoveryCodes'])->name('user.two-factor.recovery-codes');
    Route::post('/user/two-factor/recovery-codes/regenerate', [TwoFactorProfileController::class, 'regenerateRecoveryCodes'])->name('user.two-factor.regenerate');

    // Mes contributions (suggestions + votes)
    Route::get('/user/contributions', [UserContributionsController::class, 'index'])->name('user.contributions');

    // Journal d'activité utilisateur (Phase 96)
    Route::get('/user/activity', [UserActivityController::class, 'index'])->name('user.activity');

    // Sessions actives (Phase 95)
    Route::get('/user/sessions', [UserSessionController::class, 'index'])->name('user.sessions');
    Route::post('/user/sessions/{id}/revoke', [UserSessionController::class, 'revoke'])->name('user.sessions.revoke');
    Route::post('/user/sessions/revoke-others', [UserSessionController::class, 'revokeOthers'])->name('user.sessions.revoke-others');

    // Tokens API (Sanctum PAT)
    Route::get('/user/api-tokens', [UserApiTokenController::class, 'index'])->name('user.api-tokens');
    Route::post('/user/api-tokens', [UserApiTokenController::class, 'store'])->name('user.api-tokens.store');
    Route::delete('/user/api-tokens/{id}', [UserApiTokenController::class, 'destroy'])->name('user.api-tokens.destroy');
});
