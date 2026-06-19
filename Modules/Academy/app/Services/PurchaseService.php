<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Laravel\Cashier\Checkout;
use Modules\Academy\Exceptions\CourseNotPurchasableException;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;

class PurchaseService
{
    /**
     * Crée une session Stripe Checkout pour un cours payant.
     *
     * @param  \App\Models\User            $user
     * @param  \Modules\Academy\Models\Course $course
     * @param  string                      $successUrl
     * @param  string                      $cancelUrl
     * @return \Laravel\Cashier\Checkout
     *
     * @throws \Modules\Academy\Exceptions\CourseNotPurchasableException
     */
    public function createCheckoutSession(
        User $user,
        Course $course,
        string $successUrl,
        string $cancelUrl,
    ): Checkout {
        // Refus : cours gratuit
        if ($course->access_type === 'free') {
            throw new CourseNotPurchasableException('Le cours est gratuit.');
        }

        // Refus : déjà inscrit avec statut actif
        $alreadyEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        if ($alreadyEnrolled) {
            throw new CourseNotPurchasableException('Déjà inscrit.');
        }

        // Construction des items Stripe
        if (! empty($course->stripe_price_id)) {
            // Prix Stripe existant → on le référence directement
            $items = [['price' => $course->stripe_price_id, 'quantity' => 1]];
        } elseif ($course->price_cents !== null && $course->price_cents > 0) {
            // Prix dynamique
            $items = [[
                'price_data' => [
                    'currency'     => strtolower($course->currency ?: 'cad'),
                    'product_data' => ['name' => $course->title],
                    'unit_amount'  => $course->price_cents,
                ],
                'quantity' => 1,
            ]];
        } else {
            throw new CourseNotPurchasableException('Prix non configuré.');
        }

        $sessionOptions = [
            'mode'        => 'payment',
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'metadata'    => [
                'course_id' => (string) $course->id,
                'user_id'   => (string) $user->id,
            ],
        ];

        return $user->checkout($items, $sessionOptions);
    }
}
