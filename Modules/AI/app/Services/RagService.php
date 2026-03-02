<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Services;

class RagService
{
    public function getRelevantContext(string $query, int $maxResults = 3): string
    {
        $contexts = [];

        if (class_exists(\Modules\Faq\Models\Faq::class)) {
            $faqs = \Modules\Faq\Models\Faq::where('is_published', true)
                ->where(function ($q) use ($query) {
                    $q->where('question', 'LIKE', "%{$query}%")
                        ->orWhere('answer', 'LIKE', "%{$query}%");
                })
                ->take($maxResults)
                ->get();

            foreach ($faqs as $faq) {
                $contexts[] = "FAQ: Q: {$faq->question} A: ".strip_tags($faq->answer);
            }
        }

        if (class_exists(\Modules\Pages\Models\StaticPage::class)) {
            $pages = \Modules\Pages\Models\StaticPage::where('is_published', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->take($maxResults)
                ->get();

            foreach ($pages as $page) {
                $content = mb_substr(strip_tags($page->content), 0, 500);
                $contexts[] = "Page: {$page->title}: {$content}";
            }
        }

        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $articles = \Modules\Blog\Models\Article::where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->take($maxResults)
                ->get();

            foreach ($articles as $article) {
                $content = mb_substr(strip_tags($article->content), 0, 500);
                $contexts[] = "Article: {$article->title}: {$content}";
            }
        }

        return implode("\n", $contexts);
    }

    public function buildSystemPrompt(string $basePrompt, string $userQuery): string
    {
        $context = $this->getRelevantContext($userQuery);

        if ($context === '') {
            return $basePrompt;
        }

        return $basePrompt."\n\nVoici des informations du site qui peuvent aider:\n{$context}";
    }
}
