<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\States;

use Modules\Blog\States\Transitions\PublishTransition;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ArticleState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(DraftArticleState::class)
            ->allowTransition(DraftArticleState::class, PublishedArticleState::class, PublishTransition::class)
            ->allowTransition(PublishedArticleState::class, DraftArticleState::class)
            ->allowTransition([DraftArticleState::class, PublishedArticleState::class], ArchivedArticleState::class);
    }
}
