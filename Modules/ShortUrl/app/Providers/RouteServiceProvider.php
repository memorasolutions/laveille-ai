<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'ShortUrl';

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->name, 'routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(module_path($this->name, 'routes/api.php'));
    }
}
