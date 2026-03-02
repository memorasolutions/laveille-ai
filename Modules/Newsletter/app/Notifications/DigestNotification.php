<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DigestNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Collection $articles) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $count = $this->articles->count();
        $appName = config('app.name');
        $plural = $count > 1 ? 's' : '';

        $mail = (new MailMessage)
            ->subject("Nouveaux articles cette semaine - {$appName}")
            ->greeting('Bonjour,')
            ->line("Voici les nouveaux articles publies cette semaine sur {$appName}.")
            ->line("{$count} nouvel{$plural} article{$plural} :");

        foreach ($this->articles->take(5) as $article) {
            $locale = app()->getLocale();
            $title = $article->getTranslation('title', $locale);
            $slug = $article->getTranslation('slug', $locale);
            $mail->line("- {$title} : ".route('blog.show', $slug));
        }

        if ($count > 5) {
            $remaining = $count - 5;
            $mail->line("... et {$remaining} autre{$plural} article{$plural}.");
        }

        $mail->action('Lire les articles', route('blog.index'))
            ->line('Pour vous desabonner : '.route('newsletter.unsubscribe', ['token' => $notifiable->token]));

        return $mail;
    }
}
