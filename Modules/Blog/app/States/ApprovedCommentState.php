<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\States;

class ApprovedCommentState extends CommentState
{
    public static string $name = 'approved';
}
