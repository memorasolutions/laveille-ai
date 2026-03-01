<?php

declare(strict_types=1);

namespace Modules\Widget\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Widget\Models\Widget;

class WidgetService
{
    public static function getWidgetsForZone(string $zone): Collection
    {
        return Cache::remember("widgets_{$zone}", 3600, fn () => Widget::active()
            ->forZone($zone)
            ->orderBy('sort_order')
            ->get()
        );
    }

    public static function clearCache(?string $zone = null): void
    {
        if ($zone) {
            Cache::forget("widgets_{$zone}");

            return;
        }

        foreach (Widget::ZONES as $z) {
            Cache::forget("widgets_{$z}");
        }
    }
}
