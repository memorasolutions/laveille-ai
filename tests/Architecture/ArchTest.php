<?php

declare(strict_types=1);

// Pest 3 architecture presets
arch()->preset()->php();
arch()->preset()->laravel()->ignoring('Modules\Backoffice\Providers');
arch()->preset()->security();

// Models
arch('models extend eloquent')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

// No debug calls
arch('no debug calls in app')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

arch('no debug calls in modules')
    ->expect('Modules')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

// Jobs in Auth module must be queueable
arch('auth jobs implement ShouldQueue')
    ->expect('Modules\Auth\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

// Core events use Dispatchable
arch('core events use Dispatchable')
    ->expect('Modules\Core\Events')
    ->toUseTrait('Illuminate\Foundation\Events\Dispatchable');

// Auth listeners have handle method
arch('auth listeners have handle method')
    ->expect('Modules\Auth\Listeners')
    ->toHaveMethod('handle');

// Auth policies have correct suffix
arch('auth policies have Policy suffix')
    ->expect('Modules\Auth\Policies')
    ->toHaveSuffix('Policy');

// Auth observers have correct suffix
arch('auth observers have Observer suffix')
    ->expect('Modules\Auth\Observers')
    ->toHaveSuffix('Observer');

// Auth FormRequests extend BaseFormRequest
arch('auth form requests extend base')
    ->expect('Modules\Auth\Http\Requests')
    ->toExtend('Modules\Core\Http\Requests\BaseFormRequest');

// Middleware are final-ish (have handle method)
arch('middleware have handle method')
    ->expect('Modules\Core\Http\Middleware')
    ->toHaveMethod('handle');

// Traits in Core module
arch('core traits are traits')
    ->expect('Modules\Core\Traits')
    ->toBeTraits();

// Modules should not import from each other (except Core)
arch('modules do not import from App Events')
    ->expect('Modules')
    ->not->toUse('App\Events');

arch('modules do not import from App Jobs')
    ->expect('Modules')
    ->not->toUse('App\Jobs');

arch('modules do not import from App Listeners')
    ->expect('Modules')
    ->not->toUse('App\Listeners');

arch('modules do not import from App Observers')
    ->expect('Modules')
    ->not->toUse('App\Observers');

arch('modules do not import from App Policies')
    ->expect('Modules')
    ->not->toUse('App\Policies');

arch('shared events live in Core module')
    ->expect('Modules\Core\Events')
    ->toUseTrait('Illuminate\Foundation\Events\Dispatchable');
