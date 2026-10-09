<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses(\Modules\Idp\Tests\Concerns\SkipsWhenIdpDisabled::class);

beforeEach(function (): void {
    test()->skipIfIdpModuleDisabled();
});

it('OFF : /api/oauth/userinfo renvoie 404 (route non enregistrée) et aucune route oauth n existe', function (): void {
    expect(config('idp.enabled'))->toBeFalse();
    expect(collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($r) => str_contains($r->uri(), 'oauth'))->count())->toBe(0);

    $this->getJson('/api/oauth/userinfo')->assertNotFound();
});
