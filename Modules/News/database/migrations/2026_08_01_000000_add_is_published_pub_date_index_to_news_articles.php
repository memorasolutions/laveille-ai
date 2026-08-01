<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute un index composite (is_published, pub_date) sur news_articles.
 *
 * Contexte mesuré en production : la requête de la page d'accueil
 * (where is_published = ? order by pub_date desc limit 8) coûtait 1642 ms
 * et représentait 94 % du temps de rendu de la page d'accueil. L'EXPLAIN
 * de cette requête montrait type=ALL, key=NULL et Extra="Using where;
 * Using filesort" avec 19863 lignes balayées sur une table de 293,95 Mo
 * (30084 lignes dont seulement 5236 publiées). Aucun index n'existait sur
 * is_published ni sur pub_date, forçant un balayage complet suivi d'un tri
 * en mémoire/disque à chaque chargement de la page d'accueil.
 *
 * Cet index composite permet au moteur MySQL de filtrer sur is_published
 * puis de lire pub_date déjà trié, évitant à la fois le balayage complet
 * et le filesort. Opération idempotente et sans risque : un index ne
 * modifie aucune donnée existante.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    private const TABLE = 'news_articles';

    private const INDEX_NAME = 'news_articles_is_published_pub_date_index';

    public function up(): void
    {
        // Comme la migration d'indexation de Directory (add_indexes_to_directory_resources) :
        // information_schema.statistics n'existe qu'en MySQL, on ne s'exécute que sur ce
        // pilote (la suite de tests tourne en sqlite en mémoire, cf. phpunit.xml).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if ($this->indexExists()) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->index(['is_published', 'pub_date'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if (! $this->indexExists()) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropIndex(self::INDEX_NAME);
        });
    }

    /**
     * Vérifie l'existence de l'index via information_schema plutôt que par
     * essai/erreur, afin que la migration reste rejouable sans jamais échouer.
     */
    private function indexExists(): bool
    {
        $result = DB::select(
            'SELECT COUNT(1) AS total FROM information_schema.statistics '
            .'WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [self::TABLE, self::INDEX_NAME]
        );

        return (int) ($result[0]->total ?? 0) > 0;
    }
};
