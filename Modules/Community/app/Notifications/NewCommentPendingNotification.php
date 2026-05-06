<?php

declare(strict_types=1);

namespace Modules\Community\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\Community\Models\Comment;

/**
 * #186 (2026-05-06) : email a l'admin quand nouveau commentaire pending.
 * Trigger : CommentsThread::addComment() apres Comment::create() si status=pending.
 * Transport : Brevo API (#161 transport custom).
 */
class NewCommentPendingNotification extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->comment;
        $author = $c->user?->name ?: ($c->guest_name ?: __('Anonyme'));
        $articleTitle = $c->article?->title ?: __('Article supprimé / Type non-article');
        $excerpt = Str::limit((string) $c->content, 250);
        $adminUrl = url('/admin/blog/comments');

        return (new MailMessage())
            ->subject('[laveille.ai] '.__('Nouveau commentaire en attente').' — '.$author)
            ->greeting(__('Bonjour Stéphane,'))
            ->line(__('Un nouveau commentaire attend ta modération sur laveille.ai.'))
            ->line('')
            ->line('**'.__('Auteur').' :** '.$author)
            ->line('**'.__('Article').' :** '.$articleTitle)
            ->line('**'.__('Extrait').' :**')
            ->line($excerpt)
            ->action(__('Modérer dans l\'admin'), $adminUrl)
            ->line(__('Tu peux Approuver / Spam / Supprimer directement depuis le tableau d\'administration.'))
            ->salutation(__('— Notification automatique laveille.ai'));
    }
}
