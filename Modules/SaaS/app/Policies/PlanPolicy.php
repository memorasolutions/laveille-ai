<?php

declare(strict_types=1);

namespace Modules\SaaS\Policies;

use Modules\Core\Shared\Policies\AdminOnlyPolicy;

class PlanPolicy extends AdminOnlyPolicy
{
    protected string $permission = 'manage_plans';
}
