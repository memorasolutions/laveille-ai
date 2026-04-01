<?php

declare(strict_types=1);

namespace Modules\Community\Traits;

use Illuminate\Support\Collection;
use Modules\Community\Models\CategorySubscription;

trait HasCategorySubscriptions
{
    public function categorySubscriptions()
    {
        return $this->hasMany(CategorySubscription::class, 'user_id');
    }

    public function subscribeTo(string $categoryTag, string $module): bool
    {
        $subscription = $this->categorySubscriptions()
            ->forModule($module)
            ->forCategory($categoryTag)
            ->first();

        if ($subscription) {
            $subscription->delete();

            return false;
        }

        $this->categorySubscriptions()->create([
            'category_tag' => $categoryTag,
            'module' => $module,
        ]);

        return true;
    }

    public function isSubscribedTo(string $categoryTag, string $module): bool
    {
        return $this->categorySubscriptions()
            ->forModule($module)
            ->forCategory($categoryTag)
            ->exists();
    }

    public function subscribedCategories(string $module): Collection
    {
        return $this->categorySubscriptions()
            ->forModule($module)
            ->pluck('category_tag');
    }
}
