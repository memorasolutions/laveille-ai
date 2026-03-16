<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Models\Article;

class OEmbedController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'maxwidth' => 'nullable|integer',
            'maxheight' => 'nullable|integer',
            'format' => 'nullable|string|in:json',
        ]);

        $path = parse_url($validated['url'], PHP_URL_PATH);
        $slug = basename((string) $path);

        $locale = app()->getLocale();
        $article = Article::where("slug->{$locale}", $slug)
            ->published()
            ->firstOrFail();

        $width = min($validated['maxwidth'] ?? 800, 800);
        $height = min($validated['maxheight'] ?? 400, 400);

        $embedUrl = config('app.url').'/blog/'.$slug;

        return response()->json([
            'version' => '1.0',
            'type' => 'rich',
            'title' => $article->title,
            'author_name' => $article->user?->name,
            'author_url' => config('app.url'),
            'provider_name' => config('app.name'),
            'provider_url' => config('app.url'),
            'html' => '<iframe src="'.e($embedUrl).'" width="'.$width.'" height="'.$height.'" frameborder="0" allowfullscreen></iframe>',
            'width' => $width,
            'height' => $height,
            'thumbnail_url' => $article->featured_image,
        ]);
    }
}
