<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Verrou de comportement sur la migration qui reclasse Headroom de « Plus en ligne » vers
 * « Accès privé ».
 *
 * POURQUOI CE TEST EXISTE. La veille, un correctif de données a échoué QUATRE fois de suite, en
 * silence à chaque fois, parce qu'un `UPDATE ... WHERE` sans correspondance sort `DONE` en une
 * milliseconde. Le piège central se reproduit à l'identique ici : `directory_tools.slug` est
 * traduisible, donc il contient du JSON et jamais la chaîne nue. Le premier cas ci-dessous fige
 * ce piège - si quelqu'un revient un jour à un where() sur slug nu, ce test rougit.
 *
 * La fiche Headroom est ABSENTE de la base locale (elle n'existe qu'en production), et c'est
 * justement pour ça qu'un témoin fabriqué est indispensable : sans lui, la migration serait
 * « vérifiée » sur une base où elle n'a rien à faire.
 */
uses(TestCase::class, RefreshDatabase::class);

function migrationHeadroom(): object
{
    return require base_path(
        'Modules/Directory/database/migrations/2026_09_23_120000_headroom_acces_prive_et_non_ferme.php'
    );
}

function poserFicheTemoin(string $slug, string $statut): int
{
    return DB::table('directory_tools')->insertGetId([
        'slug' => json_encode(['fr_CA' => $slug, 'fr' => $slug]),
        'name' => json_encode(['fr_CA' => 'Témoin', 'fr' => 'Témoin']),
        'lifecycle_status' => $statut,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('reclasse la fiche malgré un slug traduisible, que la migration ne pourrait pas atteindre autrement', function () {
    $id = poserFicheTemoin('headroom', 'closed');

    // Le piège, figé : le slug est du JSON, donc un where() sur la chaîne nue ne trouve RIEN.
    expect(DB::table('directory_tools')->where('slug', 'headroom')->exists())->toBeFalse();

    migrationHeadroom()->up();

    expect(DB::table('directory_tools')->where('id', $id)->value('lifecycle_status'))->toBe('private');
});

it('respecte une fiche déjà reclassée à la main et ne la touche pas', function () {
    // Quelqu'un a pu corriger le statut depuis l'écran d'administration : sa décision prime.
    $id = poserFicheTemoin('headroom', 'active');

    migrationHeadroom()->up();

    expect(DB::table('directory_tools')->where('id', $id)->value('lifecycle_status'))->toBe('active');
});

it('ne touche pas une fiche voisine dont le slug contient le motif sans être la bonne', function () {
    $voisine = poserFicheTemoin('headroom-pro', 'closed');

    migrationHeadroom()->up();

    expect(DB::table('directory_tools')->where('id', $voisine)->value('lifecycle_status'))->toBe('closed');
});

it('est idempotente, et son down() remet exactement le statut d\'avant', function () {
    $id = poserFicheTemoin('headroom', 'closed');

    $m = migrationHeadroom();
    $m->up();
    $m->up(); // deuxième passage : doit être sans effet
    expect(DB::table('directory_tools')->where('id', $id)->value('lifecycle_status'))->toBe('private');

    $m->down();
    expect(DB::table('directory_tools')->where('id', $id)->value('lifecycle_status'))->toBe('closed');
});

it('ne lève aucune erreur quand la fiche visée n\'existe pas', function () {
    // C'est l'état réel de la base LOCALE, et celui de toute base neuve.
    DB::table('directory_tools')->delete();

    migrationHeadroom()->up();
    migrationHeadroom()->down();
})->throwsNoExceptions();
