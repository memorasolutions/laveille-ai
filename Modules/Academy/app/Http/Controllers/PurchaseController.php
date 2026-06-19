<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Academy\Exceptions\CourseNotPurchasableException;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\PurchaseService;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    /**
     * Démarre une session Stripe Checkout pour un cours payant.
     * Route : GET academie/courses/{course:slug}/purchase  (auth)
     *
     * @param  \Illuminate\Http\Request          $request
     * @param  \Modules\Academy\Models\Course    $course
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request, Course $course): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $showUrl    = route('academy.courses.show', $course);
        $successUrl = $showUrl . '?purchased=1';
        $cancelUrl  = $showUrl;

        try {
            $checkout = $this->purchaseService->createCheckoutSession(
                user: $user,
                course: $course,
                successUrl: $successUrl,
                cancelUrl: $cancelUrl,
            );

            return $checkout->redirect();
        } catch (CourseNotPurchasableException $e) {
            return redirect($showUrl)->with('error', $e->getMessage());
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return redirect($showUrl)->with('error', 'Erreur de paiement Stripe, veuillez réessayer.');
        }
    }
}
