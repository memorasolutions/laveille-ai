<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Authors\Mail\NewsletterConfirmationMail;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;

final class NewsletterSubscriberService
{
    public function __construct(
        private readonly Mailer $mailer
    ) {
    }

    public function subscribe(
        string $email,
        AuthorProfile $author,
        string $source = 'inline',
        ?string $ip = null,
        ?string $ua = null,
        string $locale = 'fr'
    ): AuthorSubscriber {
        $existing = AuthorSubscriber::where('email', $email)
            ->where('author_profile_id', $author->id)
            ->first();

        if ($existing) {
            if ($existing->confirmed_at !== null) {
                return $existing;
            }

            $existing->confirmation_token = AuthorSubscriber::generateConfirmationToken();
            $existing->save();
            $subscriber = $existing;
        } else {
            $subscriber = AuthorSubscriber::create([
                'email' => $email,
                'author_profile_id' => $author->id,
                'source' => $source,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'locale' => $locale,
                'confirmation_token' => AuthorSubscriber::generateConfirmationToken(),
            ]);
        }

        Mail::to($email)->send(new NewsletterConfirmationMail($subscriber, $author));

        Log::channel('daily')->info('newsletter.subscribe', [
            'email' => $email,
            'author_profile_id' => $author->id,
            'source' => $source,
            'ip' => $ip,
            'ua' => $ua,
            'locale' => $locale,
            'subscriber_id' => $subscriber->id,
        ]);

        return $subscriber;
    }

    public function confirm(string $token): ?AuthorSubscriber
    {
        $subscriber = AuthorSubscriber::where('confirmation_token', $token)->first();

        if (! $subscriber || $subscriber->isUnsubscribed()) {
            return null;
        }

        $subscriber->markConfirmed();
        // event(new SubscriberConfirmed($subscriber)); // TODO S108 : create Event + welcome sequence listener

        return $subscriber;
    }

    public function unsubscribe(string $token): bool
    {
        $subscriber = AuthorSubscriber::firstWhere('confirmation_token', $token);

        if (! $subscriber) {
            return false;
        }

        $subscriber->markUnsubscribed();

        return true;
    }

    public function isSubscribed(string $email, AuthorProfile $author): bool
    {
        return AuthorSubscriber::where('email', $email)
            ->where('author_profile_id', $author->id)
            ->whereNotNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->exists();
    }
}
