<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

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
    case OrderCreated = 'order.created';
    case OrderPaid = 'order.paid';
    case OrderShipped = 'order.shipped';
    case OrderRefunded = 'order.refunded';
    case LowStockDetected = 'inventory.low_stock';

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
            self::OrderCreated => 'Commande créée',
            self::OrderPaid => 'Commande payée',
            self::OrderShipped => 'Commande expédiée',
            self::OrderRefunded => 'Commande remboursée',
            self::LowStockDetected => 'Stock bas détecté',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
