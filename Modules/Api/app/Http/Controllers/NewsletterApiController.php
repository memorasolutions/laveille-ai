<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\SubscribeRequest;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WelcomeNewsletterNotification;

final class NewsletterApiController extends BaseApiController
{
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $subscriber = Subscriber::firstOrCreate(['email' => $request->validated('email')]);

        if ($subscriber->wasRecentlyCreated) {
            $subscriber->notify(new WelcomeNewsletterNotification($subscriber));
        }

        return $this->respondCreated(null, 'Abonné avec succès');
    }
}
