<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * D02 - Interrupteur MAÎTRE des notifications pilotable depuis l'admin (table
 * settings), avec repli .env. Prouve :
 *  - le réglage admin « academy_notifications_enabled » PRIME sur le défaut config ;
 *  - en l'absence de réglage admin, le défaut config/.env reste la source (FALSE) ;
 *  - setMasterEnabled() persiste et bascule réellement l'état lu par le service.
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Services\AcademyNotificationService;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! class_exists(AcademyNotificationService::class)) {
        $this->markTestSkipped('Module Academy désactivé.');
    }
});

function d02Service(): AcademyNotificationService
{
    return app(AcademyNotificationService::class);
}

test('le défaut config FALSE est respecté quand aucun réglage admin', function (): void {
    config()->set('academy.notifications.enabled', false);
    Setting::where('key', AcademyNotificationService::SETTING_KEY)->delete();

    expect(d02Service()->isMasterEnabled())->toBeFalse();
});

test('le réglage admin true PRIME sur le défaut config false', function (): void {
    config()->set('academy.notifications.enabled', false);
    Setting::set(AcademyNotificationService::SETTING_KEY, true, 'boolean', 'academy');

    expect(d02Service()->isMasterEnabled())->toBeTrue();
});

test('le réglage admin false PRIME sur le défaut config true', function (): void {
    config()->set('academy.notifications.enabled', true);
    Setting::set(AcademyNotificationService::SETTING_KEY, false, 'boolean', 'academy');

    expect(d02Service()->isMasterEnabled())->toBeFalse();
});

test('setMasterEnabled persiste et bascule réellement l\'état', function (): void {
    config()->set('academy.notifications.enabled', false);
    Setting::where('key', AcademyNotificationService::SETTING_KEY)->delete();

    $service = d02Service();
    expect($service->isMasterEnabled())->toBeFalse();

    expect($service->setMasterEnabled(true))->toBeTrue();
    expect(d02Service()->isMasterEnabled())->toBeTrue();

    expect($service->setMasterEnabled(false))->toBeTrue();
    expect(d02Service()->isMasterEnabled())->toBeFalse();
});
