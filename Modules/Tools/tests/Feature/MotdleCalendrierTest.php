<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Services\MotdleWordService;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// L'ordre du pool est le calendrier du jeu : today() indexe le pool (construit une seule fois,
// mis en cache 24h) par $jour % count($pool). Changer la fonction de hachage du usort (ex. md5
// remplacé par crc32) réattribue chaque numéro de jour à un mot différent, y compris pour un
// joueur en cours de partie pendant que le pool reste en cache. Ce test épingle des jours FIXES
// pour faire échouer bruyamment tout changement de cette fonction de hachage.
//
// Forme retenue : valeurs codées en dur (pas de capture-et-recompare). Vérifié : le pool
// (MotdleWordService::pool()) est construit exclusivement à partir de la constante WORDS codée
// en dur dans le service, jamais de la base de données — seuls glossary_slug/glossary_def
// (absents des assertions ci-dessous) dépendent du glossaire. Les valeurs ont été relevées en
// exécutant réellement le service (php artisan tinker) pour les jours 20000/20500/20688, jamais
// devinées.

it('pin le calendrier Motdle pour des jours fixes (régression usort/md5)', function () {
    expect(MotdleWordService::today(20000))->toMatchArray([
        'answer' => 'MODULE',
        'display' => 'MODULE',
        'length' => 6,
        'first' => 'M',
        'day' => 20000,
    ]);

    expect(MotdleWordService::today(20500))->toMatchArray([
        'answer' => 'VISUEL',
        'display' => 'VISUEL',
        'length' => 6,
        'first' => 'V',
        'day' => 20500,
    ]);

    expect(MotdleWordService::today(20688))->toMatchArray([
        'answer' => 'PORTIQUE',
        'display' => 'PORTIQUE',
        'length' => 8,
        'first' => 'P',
        'day' => 20688,
    ]);
});
