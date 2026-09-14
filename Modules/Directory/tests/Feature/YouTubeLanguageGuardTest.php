<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ce que ce test protège (#2555) : la règle de langue est la MÊME des deux côtés du flux -
 * la garde qui empêche d'ajouter une vidéo étrangère, et le filet qui dépublie celles déjà
 * en ligne. Deux copies finiraient par diverger, et une garde qui diverge de son filet laisse
 * le passif se reconstituer chaque nuit.
 *
 * Le piège que ces cas verrouillent : detectLanguage() renvoie « en » pour du hindi, elle ne
 * peut donc PAS servir de garde. Il fallait une règle distincte, sur la langue DÉCLARÉE.
 */

use Modules\Directory\Services\YouTubeService;

uses(Tests\TestCase::class);

it('accepte le français sous toutes ses formes régionales', function () {
    expect(YouTubeService::languageIsAllowed('fr'))->toBeTrue();
    expect(YouTubeService::languageIsAllowed('fr-CA'))->toBeTrue();
    expect(YouTubeService::languageIsAllowed('fr-FR'))->toBeTrue();
});

it("accepte l'anglais sous toutes ses formes régionales", function () {
    expect(YouTubeService::languageIsAllowed('en'))->toBeTrue();
    expect(YouTubeService::languageIsAllowed('en-US'))->toBeTrue();
    expect(YouTubeService::languageIsAllowed('EN-GB'))->toBeTrue();
});

// Les quatre langues réellement rencontrées dans l'annuaire le 2026-09-14.
it('écarte les langues mesurées en production', function () {
    expect(YouTubeService::languageIsAllowed('hi'))->toBeFalse();
    expect(YouTubeService::languageIsAllowed('es-mx'))->toBeFalse();
    expect(YouTubeService::languageIsAllowed('vi'))->toBeFalse();
    expect(YouTubeService::languageIsAllowed('de-DE'))->toBeFalse();
});

// Une langue absente n'est pas une preuve de langue étrangère : refuser au doute écarterait
// des vidéos légitimes, et la majorité des vidéos ne déclarent rien.
it('accepte une langue absente ou vide', function () {
    expect(YouTubeService::languageIsAllowed(null))->toBeTrue();
    expect(YouTubeService::languageIsAllowed(''))->toBeTrue();
    expect(YouTubeService::languageIsAllowed('   '))->toBeTrue();
});

// LE cas qui explique pourquoi cette règle existe séparément de detectLanguage().
it("ne se laisse pas tromper par detectLanguage, qui classe le hindi en anglais", function () {
    expect(YouTubeService::detectLanguage('Perplexity Tutorial in Hindi', 'hi'))->toBe('en');
    expect(YouTubeService::languageIsAllowed('hi'))->toBeFalse();
});
