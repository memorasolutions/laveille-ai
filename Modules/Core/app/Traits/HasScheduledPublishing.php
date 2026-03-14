<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Adds scheduled publishing (published_at / expired_at) to any Eloquent model.
 *
 * Usage: use HasScheduledPublishing; and optionally define:
 *   protected string $publishedColumn = 'is_published';  // default: 'status'
 *   protected mixed  $publishedValue  = true;             // default: 'published'
 *
 * @method static Builder<static> publishedNow()
 * @method static Builder<static> scheduled()
 * @method static Builder<static> expired()
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasScheduledPublishing
{
    protected function getPublishedColumn(): string
    {
        return $this->publishedColumn ?? 'status';
    }

    protected function getPublishedValue(): mixed
    {
        return $this->publishedValue ?? 'published';
    }

    /**
     * Only currently visible content: published status + not future + not expired.
     */
    public function scopePublishedNow(Builder $query): void
    {
        $query->where($this->getPublishedColumn(), $this->getPublishedValue())
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            });
    }

    /**
     * Content scheduled for the future.
     */
    public function scopeScheduled(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '>', now());
    }

    /**
     * Content past its expiration date.
     */
    public function scopeExpired(Builder $query): void
    {
        $query->whereNotNull('expired_at')
            ->where('expired_at', '<=', now());
    }

    public function isPublishedNow(): bool
    {
        $isPublishedStatus = $this->getAttribute($this->getPublishedColumn()) == $this->getPublishedValue();

        $publishedAt = $this->getAttribute('published_at');
        $isPastPublishDate = $publishedAt === null || Carbon::parse($publishedAt)->lte(now());

        $expiredAt = $this->getAttribute('expired_at');
        $isNotExpired = $expiredAt === null || Carbon::parse($expiredAt)->gt(now());

        return $isPublishedStatus && $isPastPublishDate && $isNotExpired;
    }

    public function isScheduled(): bool
    {
        $publishedAt = $this->getAttribute('published_at');

        return $publishedAt !== null && Carbon::parse($publishedAt)->gt(now());
    }

    public function isExpired(): bool
    {
        $expiredAt = $this->getAttribute('expired_at');

        return $expiredAt !== null && Carbon::parse($expiredAt)->lte(now());
    }

    /**
     * Publish immediately: set published_at to now and status to published.
     */
    public function publishNow(): static
    {
        $this->setAttribute('published_at', now());
        $this->setAttribute($this->getPublishedColumn(), $this->getPublishedValue());
        $this->save();

        return $this;
    }

    /**
     * Schedule publication for a future date with optional expiry.
     */
    public function schedule(Carbon $publishAt, ?Carbon $expireAt = null): static
    {
        $this->setAttribute('published_at', $publishAt);
        $this->setAttribute('expired_at', $expireAt);
        $this->save();

        return $this;
    }
}
