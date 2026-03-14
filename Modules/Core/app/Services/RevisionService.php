<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\ContentRevision;

class RevisionService
{
    /**
     * Get revision history for a model.
     *
     * @return Collection<int, ContentRevision>
     */
    public function getRevisions(Model $model, int $limit = 20): Collection
    {
        if (! method_exists($model, 'revisions')) {
            return new Collection;
        }

        return $model->revisions()->with('user')->limit($limit)->get();
    }

    /**
     * Restore a model to a specific revision.
     */
    public function restore(Model $model, ContentRevision $revision): Model
    {
        if (! method_exists($model, 'restoreRevision')) {
            throw new \BadMethodCallException('Model does not use HasRevisions trait.');
        }

        return $model->restoreRevision($revision);
    }

    /**
     * Compare current model state with a revision.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function diff(Model $model, ContentRevision $revision): array
    {
        if (! method_exists($model, 'getRevisionableFields')) {
            return [];
        }

        $trackedFields = $model->getRevisionableFields();
        $differences = [];

        foreach ($trackedFields as $field) {
            $currentValue = $model->getAttribute($field);
            $revisionValue = $revision->data[$field] ?? null;

            if ($currentValue !== $revisionValue) {
                $differences[$field] = [
                    'old' => $revisionValue,
                    'new' => $currentValue,
                ];
            }
        }

        return $differences;
    }
}
