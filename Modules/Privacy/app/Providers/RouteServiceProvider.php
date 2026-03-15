<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Privacy\Providers;

use Modules\Core\Providers\BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $name = 'Privacy';

    protected bool $mapApi = true;

    protected string $apiPrefix = 'api/privacy';

    protected string $apiNamePrefix = '';

    protected array $apiMiddleware = ['api', 'throttle:60,1'];
}
