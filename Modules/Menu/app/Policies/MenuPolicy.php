<?php

declare(strict_types=1);

namespace Modules\Menu\Policies;

use App\Models\User;
use Modules\Core\Shared\Policies\AdminOnlyPolicy;

class MenuPolicy extends AdminOnlyPolicy
{
    protected string $permission = 'manage_menus';
}
