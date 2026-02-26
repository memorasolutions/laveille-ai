<?php

declare(strict_types=1);

namespace Modules\Webhooks\Enums;

enum WebhookEvent: string
{
    case ArticleCreated = 'article.created';
    case ArticleUpdated = 'article.updated';
    case ArticleDeleted = 'article.deleted';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case PlanCreated = 'plan.created';
    case PlanUpdated = 'plan.updated';
    case CommentCreated = 'comment.created';
    case CommentApproved = 'comment.approved';

    public function label(): string
    {
        return match ($this) {
            self::ArticleCreated => 'Article créé',
            self::ArticleUpdated => 'Article mis à jour',
            self::ArticleDeleted => 'Article supprimé',
            self::UserCreated => 'Utilisateur créé',
            self::UserUpdated => 'Utilisateur mis à jour',
            self::PlanCreated => 'Plan créé',
            self::PlanUpdated => 'Plan mis à jour',
            self::CommentCreated => 'Commentaire créé',
            self::CommentApproved => 'Commentaire approuvé',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
