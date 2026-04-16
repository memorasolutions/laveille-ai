<?php

declare(strict_types=1);

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Services\AutomationAlertService;
use PHPUnit\Framework\TestCase;

/**
 * Tests AutomationAlertService en isolation complète.
 *
 * Requiert Orchestra\Testbench (composer require --dev orchestra/testbench)
 * pour booter un container Laravel minimal sans les modules métiers
 * (Voting / Settings qui plantent avant migrations avec Tests\TestCase).
 *
 * Tant que Testbench n'est pas installé, ces tests sont skipped mais
 * restent commités comme documentation vivante des invariants du service.
 */
final class AutomationAlertServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Orchestra\Testbench\TestCase::class)) {
            $this->markTestSkipped(
                'Orchestra\Testbench non installé. Exécuter : composer require --dev orchestra/testbench '
                .'puis changer l\'extends de PHPUnit\\Framework\\TestCase vers Orchestra\\Testbench\\TestCase.'
            );
        }
    }

    public function test_fire_envoie_un_mail(): void
    {
        Mail::fake();

        AutomationAlertService::fire(
            'test-module',
            'Serveur en feu',
            'Le serveur brûle littéralement.',
            ['cpu' => '100%']
        );

        Mail::assertSentCount(1);
    }

    public function test_fire_respecte_le_rate_limit(): void
    {
        Mail::fake();

        AutomationAlertService::fire('cron', 'Doublon détecté', 'Premier appel.');
        AutomationAlertService::fire('cron', 'Doublon détecté', 'Deuxième appel identique.');

        Mail::assertSentCount(1);

        $cacheKey = 'automation_alert:'.md5('cron:Doublon détecté');
        $this->assertTrue(Cache::has($cacheKey), 'La clé de rate-limit doit exister.');
    }

    public function test_fire_envoie_si_source_ou_titre_different(): void
    {
        Mail::fake();

        AutomationAlertService::fire('cron', 'Alerte A', 'Message A.');
        AutomationAlertService::fire('cron', 'Alerte B', 'Message B.');

        Mail::assertSentCount(2);
    }

    public function test_fire_ignore_si_superadmin_email_vide(): void
    {
        Mail::fake();
        Log::spy();

        Config::set('app.superadmin_email', '');

        AutomationAlertService::fire('monitor', 'Alerte orpheline', 'Personne ne recevra ceci.');

        Mail::assertNothingSent();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($msg, $ctx = null) {
                return is_string($msg)
                    && str_contains($msg, 'superadmin_email');
            });
    }

    public function test_fire_ignore_si_superadmin_email_null(): void
    {
        Mail::fake();
        Log::spy();

        Config::set('app.superadmin_email', null);

        AutomationAlertService::fire('scheduler', 'Config absente', 'Null aussi doit être ignoré.');

        Mail::assertNothingSent();
    }

    public function test_fire_log_error_si_mail_throws(): void
    {
        Log::spy();

        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new \RuntimeException('SMTP timeout'));

        AutomationAlertService::fire(
            'api-externe',
            'Webhook echoue',
            'Le webhook X a retourné 500.',
            ['url' => 'https://example.com/hook']
        );

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($msg, $ctx = null) {
                return is_string($msg)
                    && str_contains($msg, 'Impossible')
                    && is_array($ctx)
                    && isset($ctx['error'])
                    && str_contains((string) $ctx['error'], 'SMTP timeout');
            });
    }

    public function test_fire_ne_cache_pas_si_mail_echoue(): void
    {
        Log::spy();

        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        AutomationAlertService::fire('batch', 'Import rate', 'Erreur lors de l\'import.');

        $cacheKey = 'automation_alert:'.md5('batch:Import rate');
        $this->assertFalse(
            Cache::has($cacheKey),
            'Le cache ne doit pas être alimenté quand l\'envoi du mail échoue.'
        );
    }
}
