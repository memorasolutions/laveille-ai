<?php

declare(strict_types=1);

namespace Modules\Backoffice\Filament\Widgets;

use Filament\Widgets\Widget;

class SystemInfo extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'filament.widgets.system-info';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'environment' => app()->environment(),
            'cache' => config('cache.default'),
            'queue' => config('queue.default'),
            'timezone' => config('app.timezone'),
        ];
    }
}
