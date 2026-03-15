<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class EcommerceServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Ecommerce';

    protected string $nameLower = 'ecommerce';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Override to register config under 'modules.ecommerce' prefix
     * for backward compatibility with all existing module code.
     */
    protected function registerConfig(): void
    {
        $path = module_path($this->name, 'config/config.php');
        $this->mergeConfigFrom($path, "modules.{$this->nameLower}");
    }
}
