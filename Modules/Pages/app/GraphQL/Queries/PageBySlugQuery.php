<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Pages\GraphQL\Queries;

use Illuminate\Database\Eloquent\Model;
use Modules\Pages\Models\StaticPage;

final class PageBySlugQuery
{
    /**
     * @param  array{slug: string}  $args
     */
    public function __invoke(mixed $root, array $args): ?Model
    {
        $locale = app()->getLocale();

        return StaticPage::published()
            ->where("slug->{$locale}", $args['slug'])
            ->first();
    }
}
