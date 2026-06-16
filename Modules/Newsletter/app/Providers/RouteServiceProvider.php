<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Providers;

use Modules\Core\Providers\BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $name = 'Newsletter';

    // Active le chargement de routes/api.php (webhook Brevo). Sans ça, la route n'était jamais enregistrée (cause du 0 événement).
    protected bool $mapApi = true;
}
