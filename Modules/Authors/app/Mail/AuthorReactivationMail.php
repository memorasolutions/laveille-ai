<?php

declare(strict_types=1);

namespace Modules\Authors\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Authors\Models\AuthorProfile;
use Modules\Core\Mail\Traits\RoutesToWorkspaceMailer;

class AuthorReactivationMail extends Mailable
{
    use Queueable, RoutesToWorkspaceMailer, SerializesModels;

    public function __construct(
        public AuthorProfile $authorProfile,
        public string $sequenceLevel
    ) {}

    public function build(): self
    {
        $this->routeToWorkspaceMailer();

        $name = $this->authorProfile->user?->name ?? 'auteur';
        $data = [
            'author' => $this->authorProfile,
            'name' => $name,
        ];

        return match ($this->sequenceLevel) {
            'soft_nudge' => $this->subject("Tout va bien {$name} ?")->view('authors::mail.reactivation-soft-nudge', $data),
            'at_risk' => $this->subject("On t'attend, {$name}")->view('authors::mail.reactivation-at-risk', $data),
            'dormant' => $this->subject('Veux-tu garder ton espace ?')->view('authors::mail.reactivation-dormant', $data),
            'final' => $this->subject('Dernière chance avant archivage')->view('authors::mail.reactivation-final', $data),
            default => $this->subject('Reviens écrire')->view('authors::mail.reactivation-soft-nudge', $data),
        };
    }
}
