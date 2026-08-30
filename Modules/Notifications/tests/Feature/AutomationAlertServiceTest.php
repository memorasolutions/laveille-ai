<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif « alerte muette » (2026-08-26) : entre 15h28 Québec le 25 août (19:28 UTC) et 08h46
 * le 26 (12:46 UTC), TROIS jobs ont échoué (21h38, 01h36, 06h10 Québec) sans qu'aucun courriel
 * d'alerte ne parte, et les journaux ne permettaient pas de distinguer une alerte étouffée par le
 * régulateur anti-spam d'une alerte réellement envoyée ou d'une configuration manquante - seul
 * l'échec (exception PHP) laissait une trace, sur le canal par défaut. Ce fichier verrouille les
 * TROIS issues du service (étouffée / envoyée / échouée), ainsi que le retour anticipé
 * « superadmin_email non configuré » qui partageait le même défaut que l'étouffement.
 *
 * Remplace l'ancien fichier, qui étendait \PHPUnit\Framework\TestCase et exigeait
 * Orchestra\Testbench (jamais installé sur ce projet, composer show le confirme) : ses 7 tests
 * étaient donc TOUJOURS marqués skipped - zéro couverture réelle malgré leur présence dans le
 * dépôt, constaté en lançant `php artisan test Modules/Notifications/tests` AVANT ce correctif
 * (« 7 skipped, 30 passed »). Les autres tests Feature de ce module (NotificationsTest.php,
 * NotificationPreferenceTest.php...) prouvent que `Tests\TestCase` + RefreshDatabase démarre
 * correctement ici - c'est cette convention, réellement exécutée, qui remplace l'ancienne.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Services\AutomationAlertService;

uses(Tests\TestCase::class, RefreshDatabase::class);

// --- Canal dédié 'automation_alerts' (correctif 2026-08-26, config/logging.php) -----------------
//
// Même parade que #1840/#2026-08-22 (Modules/Directory/tests/Feature/ToolDiscoveryUrlResolutionTest.php,
// DeriveMasterFromUploadTest.php) : un test qui se contente de vérifier qu'un
// Log::channel('automation_alerts') a été appelé (mock) ne prouve PAS que le message survit à
// LOG_LEVEL=error en production - seul un niveau global forcé au plus restrictif possible puis une
// lecture du fichier RÉEL du canal dédié le prouve. C'est exactement le défaut que ce correctif
// corrige (voir docs/CONTRAINTES-SOUS-AGENTS.md, section 6). Les tests Log::spy ci-dessous vérifient
// donc le CONTRAT exact (contexte, comptage), et le dernier test de ce fichier vérifie la SURVIE
// réelle sous le pire réglage de production.

/** Chemin du fichier daily du jour pour le canal 'automation_alerts' (voir config/logging.php). */
function aasLogPath(): string
{
    return storage_path('logs/automation_alerts-'.now()->format('Y-m-d').'.log');
}

/** Chemin du fichier daily du jour pour le canal PAR DÉFAUT du projet (.env : LOG_CHANNEL=daily). */
function aasDefaultLogPath(): string
{
    return storage_path('logs/laravel-'.now()->format('Y-m-d').'.log');
}

/** Repart de fichiers de log vides pour isoler le contenu produit par CE test. */
function aasResetLogs(): void
{
    @unlink(aasLogPath());
    @unlink(aasDefaultLogPath());
}

// --- Issue « envoyée » ----------------------------------------------------------------------------

it('envoie un courriel quand la configuration est valide', function () {
    // Mail::fake() ne convient PAS ici : MailFake::raw() (vendor/laravel/framework/.../MailFake.php)
    // est un no-op qui n'alimente jamais $mailables - assertSentCount() resterait à 0 quoi qu'il
    // arrive, mesuré en isolation le 2026-08-29. Mail::shouldReceive() (déjà utilisé plus bas pour
    // le chemin d'échec) vérifie l'appel réel, lui.
    Mail::shouldReceive('raw')->once();

    AutomationAlertService::fire(
        'test-module',
        'Serveur en feu',
        'Le serveur brûle littéralement.',
        ['cpu' => '100%']
    );
});

it('journalise l\'issue "envoyee" avec la source, le titre et le destinataire', function () {
    Mail::shouldReceive('raw')->once();
    Log::spy();
    Log::shouldReceive('channel')->with('automation_alerts')->andReturnSelf();

    AutomationAlertService::fire('test-module', 'Serveur en feu', 'Le serveur brûle littéralement.');

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(function ($message, $context = null) {
            return is_string($message)
                && str_contains($message, 'envoyée')
                && is_array($context)
                && ($context['issue'] ?? null) === 'envoyee'
                && ($context['source'] ?? null) === 'test-module'
                && ($context['title'] ?? null) === 'Serveur en feu'
                && ($context['destinataire'] ?? null) === 'stephane@memora.ca';
        });
});

it('envoie de nouveau si la source ou le titre diffère (pas de faux positif du régulateur anti-spam)', function () {
    Mail::shouldReceive('raw')->twice();

    AutomationAlertService::fire('cron', 'Alerte A', 'Message A.');
    AutomationAlertService::fire('cron', 'Alerte B', 'Message B.');
});

// --- Issue « étouffée » ---------------------------------------------------------------------------

it('étouffe un deuxième appel identique dans la fenêtre anti-spam, et journalise le temps restant', function () {
    // Un seul Mail::raw() attendu : le deuxième fire() doit être intercepté par le régulateur
    // anti-spam AVANT d'atteindre Mail::raw() - Mockery ->once() échouerait sinon (comptage strict).
    Mail::shouldReceive('raw')->once();
    Log::spy();
    Log::shouldReceive('channel')->with('automation_alerts')->andReturnSelf();

    AutomationAlertService::fire('cron', 'Doublon détecté', 'Premier appel.');
    AutomationAlertService::fire('cron', 'Doublon détecté', 'Deuxième appel identique.');

    $cacheKey = 'automation_alert:'.md5('cron:Doublon détecté');
    expect(Cache::has($cacheKey))->toBeTrue();

    // Le premier appel a bien journalisé son envoi...
    Log::shouldHaveReceived('info')
        ->withArgs(fn ($message, $context = null) => is_array($context) && ($context['issue'] ?? null) === 'envoyee')
        ->once();

    // ...et le deuxième, étouffé, journalise la clé et un temps restant proche de 15 minutes
    // (900 secondes) plutôt qu'un simple booléen : c'est ce qui rend l'étouffement AUDITABLE.
    Log::shouldHaveReceived('info')
        ->withArgs(function ($message, $context = null) use ($cacheKey) {
            return is_string($message)
                && str_contains($message, 'étouffée')
                && is_array($context)
                && ($context['issue'] ?? null) === 'etouffee'
                && ($context['source'] ?? null) === 'cron'
                && ($context['title'] ?? null) === 'Doublon détecté'
                && ($context['cache_key'] ?? null) === $cacheKey
                && is_int($context['secondes_avant_expiration'] ?? null)
                && $context['secondes_avant_expiration'] > 890
                && $context['secondes_avant_expiration'] <= 900;
        })
        ->once();
});

// --- Issue « superadmin_email non configuré » (partageait le défaut de l'étouffement) -------------

it('journalise "superadmin_email_manquant" sur le canal dédié et n\'envoie rien, quand la config est vide', function () {
    Mail::fake();
    Log::spy();
    Log::shouldReceive('channel')->with('automation_alerts')->andReturnSelf();

    Config::set('app.superadmin_email', '');

    AutomationAlertService::fire('monitor', 'Alerte orpheline', 'Personne ne recevra ceci.');

    Mail::assertNothingSent();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function ($message, $context = null) {
            return is_string($message)
                && str_contains($message, 'superadmin_email')
                && is_array($context)
                && ($context['issue'] ?? null) === 'superadmin_email_manquant'
                && ($context['source'] ?? null) === 'monitor';
        });
});

it('n\'envoie rien quand la config superadmin_email est null', function () {
    Mail::fake();

    Config::set('app.superadmin_email', null);

    AutomationAlertService::fire('scheduler', 'Config absente', 'Null aussi doit être ignoré.');

    Mail::assertNothingSent();
});

// --- Issue « échouée » (déjà journalisée avant ce correctif - canal par défaut, INCHANGÉ) ---------

it('journalise Log::error sur le canal par défaut quand Mail::raw lève une exception', function () {
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
        ->withArgs(function ($message, $context = null) {
            return is_string($message)
                && str_contains($message, 'Impossible')
                && is_array($context)
                && isset($context['error'])
                && str_contains((string) $context['error'], 'SMTP timeout');
        });
});

it('ne met rien en cache quand l\'envoi du courriel échoue', function () {
    Log::spy();

    Mail::shouldReceive('raw')
        ->once()
        ->andThrow(new \RuntimeException('Connection refused'));

    AutomationAlertService::fire('batch', 'Import rate', 'Erreur lors de l\'import.');

    $cacheKey = 'automation_alert:'.md5('batch:Import rate');
    expect(Cache::has($cacheKey))->toBeFalse();
});

// --- Preuve de survie à LOG_LEVEL=error en production (le piège documenté de ce projet) -----------

it('#2026-08-26 : étouffement, envoi et configuration manquante laissent chacun une trace sur automation_alerts, même avec un niveau de log global très restrictif, jamais sur le canal par défaut', function () {
    // Simule la config de PRODUCTION diagnostiquée (LOG_LEVEL=error) - ici encore plus restrictif
    // ('emergency') - pour prouver que SEUL le hard-code 'level' => 'info' du canal
    // 'automation_alerts' (config/logging.php) rend ces trois issues observables, indépendamment
    // de tout réglage global. Les tests Log::spy ci-dessus prouvent l'appel ; celui-ci prouve la
    // survie réelle - c'est exactement la distinction qui manquait la nuit du 25 au 26 août 2026.
    config([
        'logging.channels.daily.level' => 'emergency',
        'logging.channels.single.level' => 'emergency',
    ]);

    aasResetLogs();

    Mail::fake();

    AutomationAlertService::fire('cron', 'Purge orpheline', 'Premier appel : doit être envoyé.');
    AutomationAlertService::fire('cron', 'Purge orpheline', 'Deuxième appel : doit être étouffé.');

    Config::set('app.superadmin_email', '');
    AutomationAlertService::fire('monitor', 'Config absente', 'Troisième appel : superadmin manquant.');

    expect(file_exists(aasLogPath()))->toBeTrue();
    $dedicated = file_get_contents(aasLogPath());

    expect($dedicated)->toContain('Alerte envoyée')
        ->and($dedicated)->toContain('Purge orpheline')
        ->and($dedicated)->toContain('Alerte étouffée')
        ->and($dedicated)->toContain('cache_key')
        ->and($dedicated)->toContain('superadmin_email non configuré')
        ->and($dedicated)->toContain('Config absente');

    // Canal par défaut : rien de tout cela n'y a fuité (fichier absent, ou présent mais muet
    // sur ces trois issues - seul un Log::error non lié à ce test pourrait y écrire).
    if (file_exists(aasDefaultLogPath())) {
        $default = file_get_contents(aasDefaultLogPath());
        expect($default)->not->toContain('Purge orpheline')
            ->and($default)->not->toContain('Alerte étouffée')
            ->and($default)->not->toContain('Config absente');
    }
});
