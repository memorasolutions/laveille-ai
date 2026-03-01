<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\States;

class ArchivedArticleState extends ArticleState
{
    public static string $name = 'archived';
}
