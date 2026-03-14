<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Menu\Policies;

use Modules\Core\Shared\Policies\AdminOnlyPolicy;

class MenuPolicy extends AdminOnlyPolicy
{
    protected string $permission = 'manage_menus';
}
