<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\RequestId::class);
        $middleware->append(\Modules\Auth\Http\Middleware\CheckBlockedIp::class);
        $middleware->append(\Modules\Core\Http\Middleware\SecurityHeaders::class);

        $aliases = [
            'csp' => \Modules\Core\Http\Middleware\ContentSecurityPolicy::class,
            'force-https' => \Modules\Core\Http\Middleware\ForceHttps::class,
            'sanitize' => \Modules\Core\Http\Middleware\SanitizeInput::class,
            'force-json' => \Modules\Core\Http\Middleware\ForceJsonResponse::class,
            'two.factor' => \Modules\Auth\Http\Middleware\EnsureTwoFactorAuthenticated::class,
            'cacheResponse' => \Spatie\ResponseCache\Middlewares\CacheResponse::class,
            'doNotCacheResponse' => \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class,
            'honeypot' => \App\Http\Middleware\HoneypotProtection::class,
            'recaptcha' => \App\Http\Middleware\VerifyRecaptcha::class,
            'force.password.change' => \Modules\Auth\Http\Middleware\ForcePasswordChange::class,
            'onboarding' => \Modules\Auth\Http\Middleware\EnsureOnboardingCompleted::class,
            'subscribed' => \Modules\SaaS\Http\Middleware\EnsureSubscribed::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ];

        if (class_exists(\Modules\FrontTheme\Http\Middleware\ThemeMiddleware::class)) {
            $aliases['theme'] = \Modules\FrontTheme\Http\Middleware\ThemeMiddleware::class;
        }

        $middleware->alias($aliases);

        $middleware->validateCsrfTokens(except: ['stripe/webhook']);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\DetectPrivacyJurisdiction::class,
            \App\Http\Middleware\ResolveCookiePreferences::class,
            \Modules\Core\Http\Middleware\SetBackofficeTheme::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié.',
                ], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ressource introuvable.',
                ], 404);
            }

            // Check URL redirects before returning 404
            $path = '/' . ltrim($request->path(), '/');
            $redirect = \Illuminate\Support\Facades\Cache::remember(
                "url_redirect:{$path}",
                3600,
                fn () => \Modules\SEO\Models\UrlRedirect::findRedirect($path),
            );

            if ($redirect) {
                $redirect->recordHit();
                \Illuminate\Support\Facades\Cache::forget("url_redirect:{$path}");

                return redirect($redirect->to_url, $redirect->status_code);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès interdit.',
                ], 403);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('livewire*')) {
                return redirect()->back();
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de requêtes. Veuillez réessayer plus tard.',
                ], 429);
            }
        });
    })->create();
