<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\SubscribeRequest;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WelcomeNewsletterNotification;

/**
 * @group Newsletter
 *
 * Public endpoints for managing newsletter subscriptions.
 */
final class NewsletterApiController extends BaseApiController
{
    /**
     * Subscribe an email address to the newsletter (idempotent).
     *
     * @unauthenticated
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $subscriber = Subscriber::firstOrCreate(['email' => $request->validated('email')]);

        if ($subscriber->wasRecentlyCreated) {
            $subscriber->notify(new WelcomeNewsletterNotification($subscriber));
        }

        return $this->respondCreated(null, 'Abonné avec succès');
    }
}
