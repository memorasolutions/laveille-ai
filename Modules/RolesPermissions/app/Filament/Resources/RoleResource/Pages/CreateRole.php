<?php

declare(strict_types=1);

namespace Modules\RolesPermissions\Filament\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\RolesPermissions\Filament\Resources\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
