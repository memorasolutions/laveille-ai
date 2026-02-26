<?php

declare(strict_types=1);

namespace Modules\Blog\States;

class PendingCommentState extends CommentState
{
    public static string $name = 'pending';
}
