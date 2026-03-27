<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AutoApproveService
{
    public static function checkAndApprove(Model $item, string $type): bool
    {
        $threshold = self::getThreshold();

        if (($item->upvotes ?? 0) >= $threshold && $item->is_approved === false) {
            $item->is_approved = true;
            $item->save();

            Log::info("AutoApprove: {$type} #{$item->id} auto-approuvé ({$item->upvotes} votes, seuil: {$threshold})");

            return true;
        }

        return false;
    }

    public static function getThreshold(): int
    {
        if (class_exists(\Modules\Settings\Facades\Settings::class)) {
            return (int) \Modules\Settings\Facades\Settings::get('community_auto_approve_threshold', 5);
        }

        return 5;
    }
}
