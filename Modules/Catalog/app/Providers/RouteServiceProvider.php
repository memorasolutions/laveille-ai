<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

namespace Modules\Catalog\Providers;

use Modules\Core\Providers\BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $name = 'Catalog';

    protected bool $mapApi = false;
}
