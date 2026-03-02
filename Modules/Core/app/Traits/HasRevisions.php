<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Models\ContentRevision;

/**
 * Adds content revision tracking to any Eloquent model.
 *
 * Usage: use HasRevisions; and optionally define:
 *   protected array $revisionable = ['title', 'content', 'status'];
 *   protected int $maxRevisions = 50;
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasRevisions
{
    public static function bootHasRevisions(): void
    {
        static::updating(function (self $model): void {
            $trackedFields = $model->getRevisionableFields();
            $hasChanges = false;

            foreach ($trackedFields as $field) {
                if ($model->isDirty($field)) {
                    $hasChanges = true;

                    break;
                }
            }

            if ($hasChanges) {
                $model->snapshotRevision();
            }
        });
    }

    /** @return MorphMany<ContentRevision, $this> */
    public function revisions(): MorphMany
    {
        return $this->morphMany(ContentRevision::class, 'revisionable')
            ->orderByDesc('revision_number');
    }

    /**
     * Snapshot current (pre-update) values into a revision.
     */
    public function snapshotRevision(?string $summary = null): ?ContentRevision
    {
        $trackedFields = $this->getRevisionableFields();
        $originalData = [];

        foreach ($trackedFields as $field) {
            $originalData[$field] = $this->getOriginal($field);
        }

        $lastRevNumber = (int) $this->revisions()->max('revision_number');

        /** @var ContentRevision $revision */
        $revision = $this->revisions()->create([
            'user_id' => auth()->id() ?? $this->getAttribute('user_id'),
            'data' => $originalData,
            'revision_number' => $lastRevNumber + 1,
            'summary' => $summary,
        ]);

        $this->pruneOldRevisions();

        return $revision;
    }

    /**
     * Restore model fields from a revision snapshot.
     */
    public function restoreRevision(ContentRevision $revision): static
    {
        $data = $revision->data;
        $trackedFields = $this->getRevisionableFields();
        $restoreData = [];

        foreach ($trackedFields as $field) {
            if (array_key_exists($field, $data)) {
                $restoreData[$field] = $data[$field];
            }
        }

        $this->update($restoreData);

        return $this->fresh();
    }

    /**
     * @return list<string>
     */
    public function getRevisionableFields(): array
    {
        return $this->revisionable ?? ['title', 'content', 'status'];
    }

    public function getMaxRevisions(): int
    {
        return $this->maxRevisions ?? 50;
    }

    public function pruneOldRevisions(): void
    {
        $max = $this->getMaxRevisions();
        $keepIds = $this->revisions()
            ->orderByDesc('revision_number')
            ->limit($max)
            ->pluck('id');

        $this->revisions()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
