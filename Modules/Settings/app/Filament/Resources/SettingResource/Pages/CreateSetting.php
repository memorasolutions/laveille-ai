<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources\SettingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Settings\Filament\Resources\SettingResource;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;
}
