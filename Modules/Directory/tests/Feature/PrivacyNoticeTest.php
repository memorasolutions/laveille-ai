<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Module « précaution données personnelles » (v1.321.0) : interrupteur runtime OFF par défaut,
 * note générale par catégorie (config), fait vérifié par outil, migration CVBooster sans publication.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\PrivacyNoticeService;
use Modules\Settings\Facades\Settings;
use Tests\Concerns\RegistersMysqlSqliteCompatFunctions;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);
uses(RegistersMysqlSqliteCompatFunctions::class);

beforeEach(function () {
    $this->registerMysqlSqliteCompatFunctions();
    config(['app.locale' => 'fr_CA']);
});

const PRIVACY_CV_TEXT = "Avant d'envoyer votre CV à un outil d'IA, transmettez seulement les renseignements nécessaires à sa révision et retirez le reste, comme votre adresse complète, votre date de naissance ou les coordonnées de vos références. Vérifiez comment le service utilise, conserve et partage les documents avant l'envoi.";

function makePrivacyTool(string $slug, ?string $categorySlug = null, array $extra = []): Tool
{
    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://'.$slug.'.example';
    $tool->pricing = 'free';
    $tool->status = 'published';
    foreach ($extra as $k => $v) {
        $tool->{$k} = $v;
    }
    $tool->save();

    if ($categorySlug !== null) {
        $category = new Category();
        $category->setTranslation('name', 'fr_CA', 'Cat '.$categorySlug);
        $category->setTranslation('slug', 'fr_CA', $categorySlug);
        $category->save();
        $tool->categories()->sync([$category->id]);
    }

    return $tool->fresh();
}

test('interrupteur OFF par défaut : rien ne s\'affiche et la fiche reste 200', function () {
    $tool = makePrivacyTool('outil-off', 'cv-candidatures', [
        'third_party_ai_note' => 'Test', 'privacy_policy_url' => 'https://p.example/privacy', 'privacy_checked_at' => '2026-10-07',
    ]);

    expect(app(PrivacyNoticeService::class)->enabled())->toBeFalse();
    $this->get(route('directory.show', $tool->slug))->assertOk()
        ->assertDontSee('Précaution pour vos renseignements personnels')
        ->assertDontSee('Selon sa politique de confidentialité');
});

test('ON + catégorie CV : texte EXACT de la note, ligne vérifiée datée et lien de la politique', function () {
    Settings::set(PrivacyNoticeService::SETTING_KEY, '1', 'boolean', 'directory');
    $tool = makePrivacyTool('outil-cv', 'cv-candidatures', [
        'third_party_ai_note' => 'Outil CV transmet le CV et l\'offre à un modèle d\'IA tiers pour les traiter',
        'privacy_policy_url' => 'https://p.example/privacy', 'privacy_checked_at' => '2026-10-07',
    ]);

    $this->get(route('directory.show', $tool->slug))->assertOk()
        ->assertSee(PRIVACY_CV_TEXT)
        ->assertSee('Selon sa politique de confidentialité (vérifiée le 7 octobre 2026), Outil CV transmet le CV')
        ->assertSee('href="https://p.example/privacy"', false);
});

test('ON + catégorie documents : variante générique « vos documents »', function () {
    Settings::set(PrivacyNoticeService::SETTING_KEY, '1', 'boolean', 'directory');
    $tool = makePrivacyTool('outil-doc', 'ecriture-ia');

    $note = app(PrivacyNoticeService::class)->generalNote($tool);
    expect($note)->toStartWith("Avant d'envoyer vos documents à un outil d'IA")
        ->and($note)->not->toContain('CV');
});

test('ON mais catégorie non listée et aucun fait vérifié : aucun bloc', function () {
    Settings::set(PrivacyNoticeService::SETTING_KEY, '1', 'boolean', 'directory');
    $tool = makePrivacyTool('outil-musique', 'musique-ia');

    expect(app(PrivacyNoticeService::class)->forTool($tool))->toBeNull();
    $this->get(route('directory.show', $tool->slug))->assertOk()->assertDontSee('Précaution pour vos renseignements');
});

test('la ligne vérifiée exige la note ET la date; une URL non http est ignorée', function () {
    $service = app(PrivacyNoticeService::class);
    $sansDate = makePrivacyTool('sans-date', null, ['third_party_ai_note' => 'x']);
    $mauvaiseUrl = makePrivacyTool('mauvaise-url', null, ['privacy_policy_url' => 'javascript:alert(1)']);

    expect($service->verifiedLine($sansDate))->toBeNull()
        ->and($service->policyUrl($mauvaiseUrl))->toBeNull();
});

test('échéance de re-vérification = date + recheck_months', function () {
    $tool = makePrivacyTool('echeance', null, ['privacy_checked_at' => '2026-10-07']);

    expect(app(PrivacyNoticeService::class)->recheckDueAt($tool)->toDateString())->toBe('2027-04-07');
});

test('commande directory:privacy-notice on/off écrit le réglage runtime', function () {
    $this->artisan('directory:privacy-notice', ['action' => 'on'])->assertSuccessful();
    expect(app(PrivacyNoticeService::class)->enabled())->toBeTrue();
    $this->artisan('directory:privacy-notice', ['action' => 'off'])->assertSuccessful();
    expect(app(PrivacyNoticeService::class)->enabled())->toBeFalse();
});

test('migration CVBooster : prépare la fiche pending sans la publier, et down() restaure', function () {
    $tool = makePrivacyTool('cvbooster', null, ['status' => 'pending', 'url' => 'https://cvbooster.ai/fr/']);
    $cat = new Category();
    $cat->setTranslation('name', 'fr_CA', 'Écriture IA');
    $cat->setTranslation('slug', 'fr_CA', 'ecriture-ia');
    $cat->save();

    $migration = require base_path('Modules/Directory/database/migrations/2026_10_07_120100_prepare_cvbooster_tool.php');
    $migration->up();
    $migration->up(); // idempotente

    $tool = $tool->fresh();
    expect($tool->status)->toBe('pending')
        ->and($tool->url)->toBe('https://cvbooster.ai/fr')
        ->and($tool->privacy_policy_url)->toBe('https://cvbooster.ai/privacy')
        ->and($tool->privacy_checked_at->toDateString())->toBe('2026-10-07')
        ->and($tool->getTranslation('short_description', 'fr_CA'))->toStartWith('CVBooster utilise l\'IA pour comparer')
        ->and($tool->categories()->count())->toBe(1);

    $migration->down();
    expect($tool->fresh()->privacy_policy_url)->toBeNull();
});
