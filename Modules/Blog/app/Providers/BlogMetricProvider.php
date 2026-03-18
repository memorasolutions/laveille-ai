<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Carbon\Carbon;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\Core\Contracts\MetricProviderInterface;
use Modules\Core\DataTransferObjects\MetricWidget;

class BlogMetricProvider implements MetricProviderInterface
{
    public function getMetricName(): string
    {
        return 'blog';
    }

    /** @return list<MetricWidget> */
    public function getWidgets(): array
    {
        $metrics = $this->getMetrics(now()->startOfMonth(), now()->endOfMonth());

        return [
            new MetricWidget(name: 'Articles publies', value: (string) $metrics['articles_published'], type: 'number', icon: 'file-text'),
            new MetricWidget(name: 'Commentaires', value: (string) $metrics['comments'], type: 'number', icon: 'message-square'),
            new MetricWidget(name: 'Categories', value: (string) $metrics['categories'], type: 'number', icon: 'folder'),
        ];
    }

    /** @return array<string, mixed> */
    public function getMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'articles_published' => Article::where('status', 'published')
                ->whereBetween('created_at', [$from, $to])->count(),
            'comments' => Comment::whereBetween('created_at', [$from, $to])->count(),
            'categories' => Category::count(),
        ];
    }
}
