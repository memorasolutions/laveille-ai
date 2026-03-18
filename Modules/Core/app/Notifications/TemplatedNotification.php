<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Services\EmailTemplateService;

abstract class TemplatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderTemplate($this->getTemplateSlug(), $this->getTemplateData($notifiable))
            ?? $this->getFallbackMail($notifiable);
    }

    protected function renderTemplate(string $slug, array $data): ?MailMessage
    {
        if (! class_exists(EmailTemplateService::class)) {
            return null;
        }

        try {
            $rendered = app(EmailTemplateService::class)->render($slug, $data);

            if ($rendered) {
                return (new MailMessage)
                    ->subject($rendered['subject'])
                    ->view('notifications::email.html-wrapper', ['content' => $rendered['body_html']]);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    abstract protected function getTemplateSlug(): string;

    abstract protected function getTemplateData(object $notifiable): array;

    abstract protected function getFallbackMail(object $notifiable): MailMessage;
}
