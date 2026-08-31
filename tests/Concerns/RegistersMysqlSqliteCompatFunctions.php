<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Trait Pest : comble en environnement de test (sqlite :memory:, phpunit.xml) les fonctions SQL
 * propres à MySQL qu'utilise le code de production - jamais l'inverse, ce trait ne change AUCUN
 * comportement en production (MySQL), où ces fonctions existent nativement.
 *
 * Mesuré le 2026-08-27 (glossaire) puis reconfirmé le 2026-08-31 (annuaire, mandat #1939) :
 * sans ce comble, /glossaire (dictionary.index) et certaines requêtes de /annuaire/{slug}
 * (directory.show, tri des ressources) sont IMPOSSIBLES à atteindre en HTTP dans la suite de
 * tests - « no such function: JSON_UNQUOTE » / « no such function: FIELD » - alors qu'elles
 * fonctionnent en production sur MySQL, plus permissif sur ces deux noms de fonction précis.
 *
 * Centralise ce qui vivait AVANT en 3 copies identiques (AffiliateLinkTest, ThinContentNoindexTest,
 * DirectoryViewCounterTest - toutes dans Modules/Directory/tests/Feature) : un seul mécanisme,
 * DRY (règle CLAUDE.md #11), utilisable par n'importe quel module.
 *
 * Usage dans un fichier Pest (après uses()) :
 *   uses(\Tests\Concerns\RegistersMysqlSqliteCompatFunctions::class);
 *   beforeEach(fn () => $this->registerMysqlSqliteCompatFunctions());
 */
trait RegistersMysqlSqliteCompatFunctions
{
    public function registerMysqlSqliteCompatFunctions(): void
    {
        $pdo = DB::connection()->getPdo();

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            return;
        }

        // FIELD(needle, ...haystack) - MySQL retourne la position 1-based de needle dans la
        // liste, 0 si absent. Utilisé par les tris "ordre de préférence" (ex. langue fr avant en).
        $pdo->sqliteCreateFunction('FIELD', function (...$args) {
            $needle = array_shift($args);
            foreach ($args as $i => $value) {
                if ($needle === $value) {
                    return $i + 1;
                }
            }

            return 0;
        });

        // JSON_UNQUOTE(JSON_EXTRACT(col, path)) - sous MySQL, JSON_EXTRACT seul renvoie une
        // valeur JSON encore quotée ("texte"), JSON_UNQUOTE retire les guillemets. Le
        // json_extract() natif de SQLite fait DÉJÀ les deux à la fois pour un chemin scalaire
        // (retourne "texte" nu, jamais quoté - vérifié : SELECT json_extract('{"a":"x"}','$.a')
        // renvoie x, pas "x"), donc un simple passe-plat reproduit exactement le résultat MySQL
        // pour le seul usage réel de ce projet : JSON_UNQUOTE ne s'utilise jamais ici SANS
        // JSON_EXTRACT en argument direct (cf. memory/feedback_json_extract_unquote_translatable.md).
        $pdo->sqliteCreateFunction('JSON_UNQUOTE', fn ($value) => $value);
    }
}
