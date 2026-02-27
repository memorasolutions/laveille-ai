<?php

declare(strict_types=1);

namespace Modules\Menu\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Menu\Models\Menu;

class MenuService
{
    public function getByLocation(string $location): ?Menu
    {
        return Cache::remember("menu_{$location}", now()->addDay(), function () use ($location) {
            $menu = Menu::active()->byLocation($location)->first();

            if ($menu) {
                $menu->setRelation('items', $this->buildTree($menu));
            }

            return $menu;
        });
    }

    public function getAvailableLocations(): array
    {
        return config('menu.locations', [
            'header' => 'Navigation principale',
            'footer' => 'Pied de page',
        ]);
    }

    public function clearCache(?string $location = null): void
    {
        if ($location) {
            Cache::forget("menu_{$location}");

            return;
        }

        foreach (array_keys($this->getAvailableLocations()) as $loc) {
            Cache::forget("menu_{$loc}");
        }

        $dbLocations = Menu::distinct()->pluck('location')->filter();
        foreach ($dbLocations as $loc) {
            Cache::forget("menu_{$loc}");
        }
    }

    public function buildTree(Menu $menu): Collection
    {
        $items = $menu->allItems()->where('enabled', true)->orderBy('order')->get();
        $grouped = $items->groupBy('parent_id');

        foreach ($items as $item) {
            $item->setRelation('children', $grouped->get($item->id, collect()));
        }

        return $grouped->get(null, collect())->values();
    }
}
