<?php declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

namespace App\GraphQL\Mutations;

use Illuminate\Support\Str;
use Modules\Pages\Models\StaticPage;

final class PageMutations
{
    public function createPage(mixed $root, array $args): StaticPage
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $locale = app()->getLocale();

        $page = new StaticPage();
        $page->user_id = $user->id;
        $page->template = $args['template'];
        $page->status = $args['status'];
        $page->setTranslation('title', $locale, $args['title']);
        $page->setTranslation('slug', $locale, Str::slug($args['title']));
        $page->setTranslation('content', $locale, $args['content']);

        if (isset($args['excerpt'])) {
            $page->setTranslation('excerpt', $locale, $args['excerpt']);
        }

        $page->save();

        return $page->fresh();
    }

    public function updatePage(mixed $root, array $args): StaticPage
    {
        /** @var StaticPage $page */
        $page = StaticPage::findOrFail($args['id']);
        $locale = app()->getLocale();

        if (isset($args['template'])) {
            $page->template = $args['template'];
        }

        if (isset($args['status'])) {
            $page->status = $args['status'];
        }

        if (isset($args['title'])) {
            $page->setTranslation('title', $locale, $args['title']);
            $page->setTranslation('slug', $locale, Str::slug($args['title']));
        }

        if (isset($args['content'])) {
            $page->setTranslation('content', $locale, $args['content']);
        }

        if (isset($args['excerpt'])) {
            $page->setTranslation('excerpt', $locale, $args['excerpt']);
        }

        $page->save();

        return $page->fresh();
    }

    public function deletePage(mixed $root, array $args): StaticPage
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->can('manage_pages')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('This action is unauthorized.');
        }

        /** @var StaticPage $page */
        $page = StaticPage::withoutGlobalScopes()->findOrFail($args['id']);
        $page->delete();

        return $page;
    }
}
