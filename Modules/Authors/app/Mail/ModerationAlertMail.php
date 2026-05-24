<?php

declare(strict_types=1);

namespace Modules\Authors\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Blog\Models\Article;
use Modules\Core\Mail\Traits\RoutesToWorkspaceMailer;

class ModerationAlertMail extends Mailable
{
    use Queueable, RoutesToWorkspaceMailer, SerializesModels;

    public function __construct(
        public Article $article,
        public string $finalStatus,
        public ?string $reviewSummary = null
    ) {}

    public function build(): self
    {
        $this->routeToWorkspaceMailer();

        return $this->subject("⚠️ Article flaggé : {$this->article->title}")
            ->view('authors::mail.moderation-alert', [
                'article' => $this->article,
                'status' => $this->finalStatus,
                'summary' => $this->reviewSummary,
            ]);
    }
}
