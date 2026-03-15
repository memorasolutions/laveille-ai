<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Translation\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class TranslationServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Translation';

    protected string $nameLower = 'translation';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(\Modules\Translation\Services\TranslationService::class);
    }
}
