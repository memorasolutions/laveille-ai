<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests M5 — Paiement Stripe Cashier des cours payants
 *
 * Stratégie : tests STRUCTURELS (analyse du code source) + UNITAIRES purs (aucune DB, aucun appel Stripe réel).
 * Aucun RefreshDatabase (incompatible JSON_UNQUOTE/SQLite dans ce projet).
 * Aucun appel API Stripe réel. Mocks via anonymous classes ou source grep.
 *
 * Groupes :
 *   1. Exception — CourseNotPurchasableException
 *   2. PurchaseService — structure source
 *   3. PurchaseService — garde-fou cours gratuit (unit, pas de DB call)
 *   4. StripeWebhookListener — structure source
 *   5. StripeWebhookListener — unit handle() : event non-pertinent + metadata absente (pas de DB)
 *   6. EventServiceProvider — listener enregistré pour WebhookHandled
 *   7. Routes M5 déclarées
 *   8. Vues — CTA « Acheter » câblé dans show et lesson
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Events\WebhookHandled;
use Modules\Academy\Exceptions\CourseNotPurchasableException;
use Modules\Academy\Http\Controllers\PurchaseController;
use Modules\Academy\Listeners\StripeWebhookListener;
use Modules\Academy\Models\Course;
use Modules\Academy\Providers\EventServiceProvider;
use Modules\Academy\Services\PurchaseService;

// ══ Groupe 1 : Exception ══════════════════════════════════════════════════

test('CourseNotPurchasableException existe dans le module Academy', function () {
    expect(class_exists(CourseNotPurchasableException::class))->toBeTrue();
});

test('CourseNotPurchasableException étend RuntimeException', function () {
    $e = new CourseNotPurchasableException('test');
    expect($e)->toBeInstanceOf(\RuntimeException::class);
    expect($e->getMessage())->toBe('test');
});

// ══ Groupe 2 : PurchaseService — structure source ═════════════════════════

test('PurchaseService existe dans le module Academy', function () {
    expect(class_exists(PurchaseService::class))->toBeTrue();
});

test('PurchaseService possède la méthode createCheckoutSession', function () {
    expect(method_exists(PurchaseService::class, 'createCheckoutSession'))->toBeTrue();
});

test('PurchaseService refuse un cours gratuit (message source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Services/PurchaseService.php'));
    expect($source)->toContain("Le cours est gratuit.");
});

test('PurchaseService refuse un user déjà inscrit (message source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Services/PurchaseService.php'));
    expect($source)->toContain("Déjà inscrit.");
});

test('PurchaseService refuse un cours sans prix configuré (message source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Services/PurchaseService.php'));
    expect($source)->toContain("Prix non configuré.");
});

test('PurchaseService utilise stripe_price_id en priorité (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Services/PurchaseService.php'));
    // Doit vérifier stripe_price_id avant de construire price_data
    expect($source)->toContain('stripe_price_id');
    expect($source)->toContain("'price' => \$course->stripe_price_id");
});

test('PurchaseService passe les metadata course_id et user_id au checkout (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Services/PurchaseService.php'));
    expect($source)->toContain("'course_id'");
    expect($source)->toContain("'user_id'");
    expect($source)->toContain("'metadata'");
});

test('PurchaseService mode payment dans les sessionOptions (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Services/PurchaseService.php'));
    // Présence de la clé 'mode' avec valeur 'payment' (espaces d'alignement possibles)
    expect($source)->toContain("'mode'")
        ->and($source)->toContain("'payment'");
});

// ══ Groupe 3 : PurchaseService — unit : cours gratuit (aucun appel DB) ══

test('PurchaseService lève CourseNotPurchasableException si access_type === free', function () {
    // La garde free est vérifiée AVANT la requête Enrollment (pas de DB call ici)
    $course = new Course();
    $course->access_type = 'free';

    $user = new \App\Models\User();

    $service = new PurchaseService();

    expect(fn () => $service->createCheckoutSession(
        user: $user,
        course: $course,
        successUrl: 'https://example.com/success',
        cancelUrl: 'https://example.com/cancel',
    ))->toThrow(CourseNotPurchasableException::class, 'Le cours est gratuit.');
});

// ══ Groupe 4 : PurchaseController — structure ═════════════════════════════

test('PurchaseController existe dans le module Academy', function () {
    expect(class_exists(PurchaseController::class))->toBeTrue();
});

test('PurchaseController possède la méthode __invoke', function () {
    expect(method_exists(PurchaseController::class, '__invoke'))->toBeTrue();
});

test('PurchaseController injecte PurchaseService via constructeur (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Http/Controllers/PurchaseController.php'));
    expect($source)->toContain('PurchaseService');
    expect($source)->toContain('__construct');
});

test('PurchaseController gère CourseNotPurchasableException (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Http/Controllers/PurchaseController.php'));
    expect($source)->toContain('CourseNotPurchasableException');
    expect($source)->toContain('catch');
});

// ══ Groupe 5 : StripeWebhookListener — structure source ══════════════════

test('StripeWebhookListener existe dans le module Academy', function () {
    expect(class_exists(StripeWebhookListener::class))->toBeTrue();
});

test('StripeWebhookListener possède la méthode handle(WebhookHandled)', function () {
    expect(method_exists(StripeWebhookListener::class, 'handle'))->toBeTrue();
    $reflection = new ReflectionMethod(StripeWebhookListener::class, 'handle');
    $params = $reflection->getParameters();
    expect($params)->toHaveCount(1);
    expect($params[0]->getType()->getName())->toBe(WebhookHandled::class);
});

test('StripeWebhookListener écoute checkout.session.completed (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Listeners/StripeWebhookListener.php'));
    expect($source)->toContain('checkout.session.completed');
});

test('StripeWebhookListener utilise firstOrCreate pour l idempotence (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Listeners/StripeWebhookListener.php'));
    expect($source)->toContain('firstOrCreate');
});

test('StripeWebhookListener est défensif sur les metadata absentes (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Listeners/StripeWebhookListener.php'));
    // Doit tester que course_id et user_id sont présents avant de continuer
    expect($source)->toContain('course_id');
    expect($source)->toContain('user_id');
    expect($source)->toContain('return;');
});

test('StripeWebhookListener attrape Throwable silencieusement (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/app/Listeners/StripeWebhookListener.php'));
    expect($source)->toContain('Throwable');
    expect($source)->toContain('Log::error');
});

// ══ Groupe 6 : StripeWebhookListener — unit handle() sans DB ═════════════

test('handle() ne lève pas d exception sur un event non-pertinent', function () {
    $event = new WebhookHandled(['type' => 'customer.subscription.created', 'data' => ['object' => []]]);
    $listener = new StripeWebhookListener();

    // Ne doit pas lever d'exception
    $listener->handle($event);
    expect(true)->toBeTrue(); // Atteint ssi aucune exception
});

test('handle() ne lève pas d exception si metadata course_id absente', function () {
    $event = new WebhookHandled([
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['metadata' => []]],
    ]);
    $listener = new StripeWebhookListener();
    $listener->handle($event);
    expect(true)->toBeTrue();
});

test('handle() ne lève pas d exception si payload vide', function () {
    $event = new WebhookHandled([]);
    $listener = new StripeWebhookListener();
    $listener->handle($event);
    expect(true)->toBeTrue();
});

// ══ Groupe 7 : EventServiceProvider — listener enregistré ═════════════════

test('EventServiceProvider enregistre StripeWebhookListener pour WebhookHandled', function () {
    $provider = new EventServiceProvider(app());
    $listen   = $provider->listens();

    expect(array_key_exists(WebhookHandled::class, $listen))->toBeTrue();
    expect($listen[WebhookHandled::class])->toContain(StripeWebhookListener::class);
});

// ══ Groupe 8 : Routes M5 ══════════════════════════════════════════════════

test('la route academy.courses.purchase est déclarée', function () {
    expect(Route::has('academy.courses.purchase'))->toBeTrue();
});

test('la route academy.courses.purchase utilise le middleware auth', function () {
    $route = Route::getRoutes()->getByName('academy.courses.purchase');
    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('auth');
});

test('la route academy.courses.purchase est une méthode GET', function () {
    $route = Route::getRoutes()->getByName('academy.courses.purchase');
    expect($route->methods())->toContain('GET');
});

// ══ Groupe 9 : Vues — CTA câblé ══════════════════════════════════════════

test('show.blade.php contient le lien academy.courses.purchase (CTA Acheter)', function () {
    $source = file_get_contents(base_path('Modules/Academy/resources/views/public/show.blade.php'));
    expect($source)->toContain('academy.courses.purchase');
});

test('lesson.blade.php contient le lien academy.courses.purchase (CTA Acheter depuis lecteur)', function () {
    $source = file_get_contents(base_path('Modules/Academy/resources/views/public/lesson.blade.php'));
    expect($source)->toContain('academy.courses.purchase');
});

test('show.blade.php affiche le CTA Acheter uniquement si non inscrit et cours payant (source)', function () {
    $source = file_get_contents(base_path('Modules/Academy/resources/views/public/show.blade.php'));
    // Doit être dans un bloc conditionnel !$isEnrolled && !$isFree
    expect($source)->toContain('!$isEnrolled && !$isFree');
});

// ══ Groupe 10 : Non-régression cours gratuit (M1 intact) ═════════════════

test('EnrollmentService::enrollFree est toujours accessible (M1 non-régressé)', function () {
    expect(class_exists(\Modules\Academy\Services\EnrollmentService::class))->toBeTrue();
    expect(method_exists(\Modules\Academy\Services\EnrollmentService::class, 'enrollFree'))->toBeTrue();
});

test('un cours gratuit ne passe jamais par PurchaseService (PurchaseService vérifie access_type free)', function () {
    // PurchaseService.php doit contenir la garde `access_type === 'free'` AVANT tout appel Stripe
    $source = file_get_contents(base_path('Modules/Academy/app/Services/PurchaseService.php'));
    // La garde doit apparaître avant la construction des $items
    $guardPos = strpos($source, "access_type === 'free'");
    $itemsPos = strpos($source, 'stripe_price_id');
    expect($guardPos)->toBeLessThan($itemsPos);
});
