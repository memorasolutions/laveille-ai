<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Faq\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class FaqServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Faq';

    protected string $nameLower = 'faq';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
