<?php

declare(strict_types=1);

namespace Modules\Community\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CategoryContentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected mixed $content,
        protected string $categoryTag,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->content->seo_title ?? $this->content->title ?? $this->content->name ?? '';

        return (new MailMessage)
            ->subject(__('Nouveau contenu dans :category', ['category' => $this->categoryTag]))
            ->line(__('Un nouveau contenu a été publié dans la catégorie ":category" :', ['category' => $this->categoryTag]))
            ->line($title)
            ->action(__('Voir'), $this->getContentUrl());
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category_tag' => $this->categoryTag,
            'content_type' => get_class($this->content),
            'content_id' => $this->content->id,
            'title' => $this->content->seo_title ?? $this->content->title ?? $this->content->name ?? '',
        ];
    }

    protected function getContentUrl(): string
    {
        if (method_exists($this->content, 'getUrl')) {
            return $this->content->getUrl();
        }

        // Fallback : utilise le slug si disponible
        $slug = $this->content->slug ?? $this->content->id;

        return url("/{$slug}");
    }
}
