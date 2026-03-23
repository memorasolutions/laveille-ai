<?php

declare(strict_types=1);

namespace Modules\Community\Traits;

use Modules\Community\Models\Review;

trait HasReviews
{
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function averageRating(): float
    {
        return (float) $this->reviews()->avg('rating');
    }

    public function approvedReviews()
    {
        return $this->reviews()->approved();
    }
}
