<?php

declare(strict_types=1);

namespace Modules\Blog\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class CommentState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(PendingCommentState::class)
            ->allowTransition(PendingCommentState::class, ApprovedCommentState::class)
            ->allowTransition(PendingCommentState::class, SpamCommentState::class)
            ->allowTransition(ApprovedCommentState::class, SpamCommentState::class);
    }
}
