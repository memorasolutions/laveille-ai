<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Testimonials\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class TestimonialsServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Testimonials';

    protected string $nameLower = 'testimonials';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
