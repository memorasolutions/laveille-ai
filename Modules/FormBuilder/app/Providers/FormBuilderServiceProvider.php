<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FormBuilder\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class FormBuilderServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'FormBuilder';

    protected string $nameLower = 'formbuilder';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
