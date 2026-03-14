<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Observers;

use Modules\AI\Models\KnowledgeDocument;
use Modules\AI\Services\KnowledgeBaseService;

class KnowledgeSourceObserver
{
    public function __construct(
        private readonly KnowledgeBaseService $knowledgeBaseService
    ) {}

    public function saved(object $model): void
    {
        $isPublished = $this->isPublished($model);
        $sourceType = $this->getSourceType($model);

        if (! $sourceType) {
            return;
        }

        if ($isPublished) {
            $this->knowledgeBaseService->syncFromSource($sourceType);
        } else {
            $this->deleteKnowledgeDocument($model, $sourceType);
        }
    }

    public function deleted(object $model): void
    {
        $sourceType = $this->getSourceType($model);
        if ($sourceType) {
            $this->deleteKnowledgeDocument($model, $sourceType);
        }
    }

    private function isPublished(object $model): bool
    {
        if (isset($model->is_published)) {
            return (bool) $model->is_published;
        }

        if (isset($model->published_at)) {
            return $model->published_at && $model->published_at <= now();
        }

        return false;
    }

    private function getSourceType(object $model): ?string
    {
        $mapping = [
            'Modules\Blog\Models\Article' => 'article',
            'Modules\Pages\Models\Page' => 'page',
            'Modules\Pages\Models\StaticPage' => 'page',
            'Modules\Faq\Models\Faq' => 'faq',
        ];

        return $mapping[get_class($model)] ?? null;
    }

    private function deleteKnowledgeDocument(object $model, string $sourceType): void
    {
        KnowledgeDocument::where('source_type', $sourceType)
            ->where('source_id', $model->id)
            ->delete();
    }
}
