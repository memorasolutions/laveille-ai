<?php

declare(strict_types=1);

namespace Modules\RolesPermissions\Filament\Resources\RoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\RolesPermissions\Filament\Resources\RoleResource;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn () => in_array($this->record->getAttribute('name'), ['super_admin', 'admin'])),
        ];
    }
}
