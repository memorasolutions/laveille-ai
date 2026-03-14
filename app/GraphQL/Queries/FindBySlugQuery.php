<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace App\GraphQL\Queries;

use Illuminate\Database\Eloquent\Model;

final class FindBySlugQuery
{
    /**
     * @param  array{slug: string}  $args
     */
    public function article(mixed $root, array $args): ?Model
    {
        $locale = app()->getLocale();

        return \Modules\Blog\Models\Article::published()
            ->where("slug->{$locale}", $args['slug'])
            ->first();
    }

    /**
     * @param  array{slug: string}  $args
     */
    public function page(mixed $root, array $args): ?Model
    {
        $locale = app()->getLocale();

        return \Modules\Pages\Models\StaticPage::published()
            ->where("slug->{$locale}", $args['slug'])
            ->first();
    }
}
