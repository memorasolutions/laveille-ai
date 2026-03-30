<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

trait HasModerationStatus
{
    public function scopeApproved(Builder $query): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'status')) {
            return $query->where('status', 'approved');
        }

        return $query;
    }

    public function scopePending(Builder $query): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'status')) {
            return $query->where('status', 'pending');
        }

        return $query;
    }

    public function scopeRejected(Builder $query): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'status')) {
            return $query->where('status', 'rejected');
        }

        return $query;
    }

    public function approve(): static
    {
        if (Schema::hasColumn($this->getTable(), 'status')) {
            $this->status = 'approved';
            $this->save();

            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->log('approved');
        }

        return $this;
    }

    public function reject(?string $reason = null): static
    {
        if (Schema::hasColumn($this->getTable(), 'status')) {
            $this->status = 'rejected';
            $this->save();

            $properties = [];
            if ($reason) {
                $properties['reason'] = $reason;
            }

            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->withProperties($properties)
                ->log('rejected');
        }

        return $this;
    }

    public function pin(): static
    {
        if (Schema::hasColumn($this->getTable(), 'is_pinned')) {
            $this->is_pinned = ! $this->is_pinned;
            $this->save();

            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->withProperties(['is_pinned' => $this->is_pinned])
                ->log($this->is_pinned ? 'pinned' : 'unpinned');
        }

        return $this;
    }

    public function softDeleteModerated(): void
    {
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('deleted');

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($this))) {
            $this->delete();
        } else {
            $this->forceDelete();
        }
    }

    public function isApproved(): bool
    {
        return Schema::hasColumn($this->getTable(), 'status') && $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return Schema::hasColumn($this->getTable(), 'status') && $this->status === 'pending';
    }

    public function getModerationHistory()
    {
        return Activity::where('subject_type', static::class)
            ->where('subject_id', $this->id)
            ->latest()
            ->get();
    }
}
