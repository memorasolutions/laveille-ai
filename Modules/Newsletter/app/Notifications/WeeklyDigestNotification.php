<?php

declare(strict_types=1);

namespace Modules\Newsletter\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class WeeklyDigestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?object $highlight,
        public Collection $topNews,
        public ?object $toolOfWeek,
        public ?object $featuredArticle,
        public ?object $didYouKnow,
        public int $weekNumber,
        public ?object $aiTerm = null,
        public ?object $interactiveTool = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $unsubscribeUrl = route('newsletter.unsubscribe', ['token' => $notifiable->token ?? 'preview']);
        $subject = 'Le digest IA #'.$this->weekNumber.' — '.($this->highlight?->seo_title ?? $this->highlight?->title ?? 'Votre veille hebdomadaire');

        return (new MailMessage)
            ->subject($subject)
            ->view('newsletter::emails.digest-weekly', [
                'subject' => $subject,
                'highlight' => $this->highlight,
                'topNews' => $this->topNews,
                'toolOfWeek' => $this->toolOfWeek,
                'featuredArticle' => $this->featuredArticle,
                'didYouKnow' => $this->didYouKnow,
                'aiTerm' => $this->aiTerm,
                'interactiveTool' => $this->interactiveTool,
                'unsubscribeUrl' => $unsubscribeUrl,
                'weekNumber' => $this->weekNumber,
            ]);
    }
}
