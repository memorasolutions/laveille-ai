<?php

declare(strict_types=1);

namespace Modules\Settings\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Settings\Services\SettingsService;

class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingsService::class;
    }
}
