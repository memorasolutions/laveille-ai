<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use Symfony\Component\Mime\Email;

trait HasOneClickUnsubscribe
{
    protected function applyOneClickUnsubscribe(MailMessage $message, ?string $token): MailMessage
    {
        if (empty($token)) {
            return $message;
        }

        $url = route('newsletter.unsubscribe.oneclick', ['token' => $token]);
        $mailto = 'mailto:unsubscribe@laveille.ai?subject=unsubscribe';

        return $message->withSymfonyMessage(function (Email $email) use ($url, $mailto): void {
            $email->getHeaders()->addTextHeader('List-Unsubscribe', "<{$url}>, <{$mailto}>");
            $email->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        });
    }
}
