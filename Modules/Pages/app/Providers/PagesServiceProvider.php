<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Pages\Providers;

use Livewire\Livewire;
use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Pages\Livewire\StaticPagesTable;

class PagesServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Pages';

    protected string $nameLower = 'pages';

    public function boot(): void
    {
        $this->bootModule();

        Livewire::component('static-pages-table', StaticPagesTable::class);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
