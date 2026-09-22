<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Verrou de COMPORTEMENT sur la migration 2026_09_22_190000, qui retire l'affirmation de
 * conformité Loi 25 portant sur l'anonymiseur.
 *
 * POURQUOI CE FICHIER EXISTE, alors qu'un verrou existait déjà. Une deuxième passe adversariale
 * a fait remarquer que le seul test committé regardait le CONTENU de trois fichiers source, et
 * jamais le comportement de la migration elle-même. Sa démonstration tenait en une commande :
 * une recherche du nom de cette migration dans les dossiers de tests ne renvoyait rien. Autrement
 * dit, un commit
 * ultérieur pouvait casser la clause `LIKE`, la comparaison de slug ou la double représentation
 * sans que rien ne rougisse - tant que le texte des fichiers restait conforme au motif.
 *
 * C'est d'autant plus nécessaire ici que cette migration a échoué QUATRE fois en silence avant de
 * fonctionner, chaque fois pour une raison différente. Un `UPDATE ... WHERE` qui ne correspond à
 * aucune ligne sort `DONE` en une milliseconde : seul un test qui pose une donnée fautive puis
 * relit peut distinguer « corrigé » de « n'a rien trouvé ».
 *
 * La migration est instanciée directement plutôt que rejouée par Artisan : sous RefreshDatabase
 * elle est déjà appliquée, et un rollback global pour l'atteindre serait à la fois lent et
 * dépendant de l'ordre des autres migrations.
 */
uses(TestCase::class, RefreshDatabase::class);

/** Rend l'objet de migration, tel que Laravel le chargerait. */
function migrationRetraitConformite(): object
{
    return require base_path(
        'Modules/Tools/database/migrations/2026_09_22_190000_retirer_affirmation_conformite_loi25.php'
    );
}

/**
 * La FAQ d'un terme de glossaire, dans la forme EXACTE où la production la stocke : du JSON dont
 * les accents sont des séquences d'échappement, jamais des caractères littéraux. C'est ce détail
 * qui avait fait échouer la deuxième tentative de correctif.
 */
function faqAvecClauseFautive(): string
{
    return json_encode([[
        'question' => 'Peut-on anonymiser un texte avant de l\'envoyer à une IA ?',
        'answer' => 'Remplacer un nom est de la pseudonymisation. Pour les PME, laveille.ai propose '
            .'un outil d\'anonymisation conforme à la Loi 25 : /outils/anonymiseur.',
    ]]);
}

function poserTermeTemoin(string $slug = 'anonymisation'): int
{
    return DB::table('dictionary_terms')->insertGetId([
        'slug' => json_encode(['fr_CA' => $slug, 'fr' => $slug]),
        'name' => json_encode(['fr_CA' => 'Témoin', 'fr' => 'Témoin']),
        'definition' => json_encode(['fr_CA' => 'Témoin', 'fr' => 'Témoin']),
        'faq' => faqAvecClauseFautive(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('retire la clause fautive d\'un slug TRADUISIBLE, que la migration ne pouvait pas atteindre avant', function () {
    $id = poserTermeTemoin();

    // Le piège d'origine, figé ici pour qu'il ne puisse pas revenir : le slug est du JSON, donc
    // un where('slug', 'anonymisation') ne trouve RIEN. Si quelqu'un y revient, ce test rougit.
    expect(DB::table('dictionary_terms')->where('slug', 'anonymisation')->exists())->toBeFalse();

    migrationRetraitConformite()->up();

    $faq = (string) DB::table('dictionary_terms')->where('id', $id)->value('faq');

    expect($faq)->not->toContain('anonymisation conforme');
    expect($faq)->toContain('anonymisation local');
    expect(json_decode($faq, true))->toBeArray(); // le JSON n'a pas été corrompu
});

it('laisse intacte une fiche dont le slug CONTIENT le motif sans être la bonne', function () {
    // Le LIKE de la migration est volontairement large : c'est la comparaison exacte qui tranche.
    $id = poserTermeTemoin('desanonymisation');
    $avant = (string) DB::table('dictionary_terms')->where('id', $id)->value('faq');

    migrationRetraitConformite()->up();

    expect((string) DB::table('dictionary_terms')->where('id', $id)->value('faq'))->toBe($avant);
});

it('corrige la description de l\'outil sans toucher au reste du texte', function () {
    $reecritAlaMain = 'Anonymise tes textes avant de les coller dans une IA. '
        .'Tout se passe dans ton navigateur. Conforme à la Loi 25 et au RGPD.';

    DB::table('tools')->insert([
        'slug' => 'anonymiseur', 'name' => 'Anonymiseur', 'description' => $reecritAlaMain,
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    migrationRetraitConformite()->up();

    $apres = (string) DB::table('tools')->where('slug', 'anonymiseur')->value('description');

    // La garde qui a fait échouer la v1.294.1 : une réécriture éditoriale doit survivre MOT POUR
    // MOT. Seule la clause fautive bouge.
    expect($apres)->toContain('Anonymise tes textes avant de les coller dans une IA.');
    expect($apres)->toContain('Tout se passe dans ton navigateur.');
    expect($apres)->not->toContain('Conforme à la Loi 25');
    expect($apres)->toContain('Le remplacement est réversible.');
});

it('est idempotente, et son down() restaure exactement l\'état d\'avant', function () {
    $id = poserTermeTemoin();
    $origine = (string) DB::table('dictionary_terms')->where('id', $id)->value('faq');

    $m = migrationRetraitConformite();
    $m->up();
    $apresPremierUp = (string) DB::table('dictionary_terms')->where('id', $id)->value('faq');

    $m->up(); // deuxième passage : doit être sans effet
    expect((string) DB::table('dictionary_terms')->where('id', $id)->value('faq'))->toBe($apresPremierUp);

    $m->down();
    expect((string) DB::table('dictionary_terms')->where('id', $id)->value('faq'))->toBe($origine);
});

it('ne lève aucune erreur quand la ligne visée n\'existe pas', function () {
    // Cas d'une base neuve, ou d'un déploiement où le terme n'a jamais été créé : la migration
    // doit passer sans bruit plutôt que d'échouer et de bloquer tout le déploiement.
    DB::table('dictionary_terms')->delete();
    DB::table('tools')->delete();

    migrationRetraitConformite()->up();
    migrationRetraitConformite()->down();
})->throwsNoExceptions();
