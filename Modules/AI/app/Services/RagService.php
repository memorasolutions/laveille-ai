<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Services;

class RagService
{
    public function __construct(
        private readonly KnowledgeBaseService $knowledgeBaseService
    ) {}

    public function getRelevantContext(string $query, int $maxResults = 5): string
    {
        // Recherche dans la KB (embeddings + fulltext)
        $kbResults = $this->knowledgeBaseService->search($query, $maxResults);

        if (! empty($kbResults)) {
            $contexts = [];
            foreach ($kbResults as $result) {
                $label = ucfirst($result['source_type']);
                $content = mb_substr(strip_tags($result['content']), 0, 500);
                $contexts[] = "{$label} ({$result['title']}): {$content}";
            }

            return implode("\n", $contexts);
        }

        // Fallback LIKE si KB vide (installations sans KB)
        return $this->fallbackLikeSearch($query, $maxResults);
    }

    public function buildSystemPrompt(string $basePrompt, string $userQuery): string
    {
        $context = $this->getRelevantContext($userQuery);

        if ($context === '') {
            return $basePrompt;
        }

        $prompt = $basePrompt."\n\nVoici des informations du site qui peuvent aider:\n{$context}";

        // Ajouter les noms de sources cachées
        $hiddenNames = \Modules\AI\Models\KnowledgeUrl::where('is_active', true)
            ->whereNotNull('hidden_source_name')
            ->pluck('hidden_source_name')
            ->unique()
            ->filter()
            ->all();

        if (! empty($hiddenNames)) {
            $names = implode(', ', $hiddenNames);
            $prompt .= "\n\nIMPORTANT : Ne mentionne JAMAIS les noms suivants dans tes réponses : {$names}. Si on te demande d'où viennent tes informations, dis que c'est basé sur ta base de connaissances interne.";
        }

        return $prompt;
    }

    private function fallbackLikeSearch(string $query, int $maxResults): string
    {
        $contexts = [];

        if (class_exists(\Modules\Faq\Models\Faq::class)) {
            $faqs = \Modules\Faq\Models\Faq::where('is_published', true)
                ->where(fn ($q) => $q->where('question', 'LIKE', "%{$query}%")
                    ->orWhere('answer', 'LIKE', "%{$query}%"))
                ->take($maxResults)
                ->get();

            foreach ($faqs as $faq) {
                $contexts[] = "FAQ: Q: {$faq->question} A: ".strip_tags($faq->answer);
            }
        }

        if (class_exists(\Modules\Pages\Models\StaticPage::class)) {
            $pages = \Modules\Pages\Models\StaticPage::where('is_published', true)
                ->where(fn ($q) => $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('content', 'LIKE', "%{$query}%"))
                ->take($maxResults)
                ->get();

            foreach ($pages as $page) {
                $contexts[] = "Page: {$page->title}: ".mb_substr(strip_tags($page->content), 0, 500);
            }
        }

        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $articles = \Modules\Blog\Models\Article::where('status', 'published')
                ->where(fn ($q) => $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('content', 'LIKE', "%{$query}%"))
                ->take($maxResults)
                ->get();

            foreach ($articles as $article) {
                $contexts[] = "Article: {$article->title}: ".mb_substr(strip_tags($article->content), 0, 500);
            }
        }

        return implode("\n", $contexts);
    }
}
