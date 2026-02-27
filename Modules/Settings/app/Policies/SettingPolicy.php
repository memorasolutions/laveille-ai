<?php

declare(strict_types=1);

namespace Modules\Settings\Policies;

use Modules\Core\Shared\Policies\AdminOnlyPolicy;

class SettingPolicy extends AdminOnlyPolicy
{
    protected string $permission = 'manage_settings';
}
