<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Observers;

use Illuminate\Support\Facades\Log;
use Modules\AI\Services\AiService;
use Modules\Blog\Models\Article;
use Modules\SEO\Models\MetaTag;
use Modules\Settings\Models\Setting;

class ArticleSeoObserver
{
    public function created(Article $article): void
    {
        $this->generateSeoIfPublished($article);
    }

    public function updated(Article $article): void
    {
        if ($article->isDirty('status')) {
            $this->generateSeoIfPublished($article);
        }
    }

    private function generateSeoIfPublished(Article $article): void
    {
        if ($article->status->getValue() !== 'published') {
            return;
        }

        if ((bool) Setting::get('ai.seo_auto_generate', false)) {
            try {
                $service = app(AiService::class);
                $seoData = $service->generateSeoMeta($article->title, strip_tags($article->content));

                MetaTag::updateOrCreate(
                    ['url_pattern' => '/blog/'.$article->slug],
                    array_merge($seoData, ['is_active' => true])
                );
            } catch (\Exception $e) {
                Log::warning('AI SEO generation failed for article #'.$article->id.': '.$e->getMessage());
            }
        }

        $this->generateExcerptIfEmpty($article);
    }

    private function generateExcerptIfEmpty(Article $article): void
    {
        if (! (bool) Setting::get('ai.auto_summary', false)) {
            return;
        }

        $currentExcerpt = $article->getTranslation('excerpt', app()->getLocale(), false);

        if (! empty($currentExcerpt)) {
            return;
        }

        try {
            $service = app(AiService::class);
            $summary = $service->generateSummary($article->content, app()->getLocale());

            $article->setTranslation('excerpt', app()->getLocale(), $summary);
            $article->saveQuietly();
        } catch (\Exception $e) {
            Log::warning('AI summary generation failed for article #'.$article->id.': '.$e->getMessage());
        }
    }
}
