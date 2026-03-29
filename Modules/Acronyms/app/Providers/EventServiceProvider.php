<?php

declare(strict_types=1);

namespace Modules\Acronyms\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    protected static $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
