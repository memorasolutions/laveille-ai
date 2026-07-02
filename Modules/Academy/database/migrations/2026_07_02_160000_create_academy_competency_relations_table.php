<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22-b - GRAPHE DE COMPÉTENCES (relations pondérées entre compétences, parité
 * Moodle "competency framework relations"). Le référentiel F22 existant est PLAT
 * (Competency <-> Course/LessonItem via academy_competency_links) : cette table
 * additive introduit des ARÊTES entre compétences elles-mêmes, orientées
 * « requiert » : competency_id REQUIERT requires_competency_id à un seuil de
 * maîtrise donné (mastery_threshold, ex. 0.70 = 70 %).
 *
 * mastery_threshold (decimal 0..1) : niveau minimal de maîtrise EXIGÉ sur le
 * prérequis pour considérer la compétence dépendante DÉVERROUILLÉE (voir
 * Services\CompetencyGraphService::isUnlocked). Défaut 0.70 (70 %), aligné sur
 * le "pass_threshold" typique du module (voir Competency::pass_threshold, en %).
 *
 * weight (decimal 0..1, optionnel, défaut 1.0) : pondération RELATIVE quand une
 * compétence a PLUSIEURS prérequis (ex. mastery globale = moyenne pondérée des
 * maîtrises de prérequis) — purement informatif pour l'instant, consommé par une
 * future vue de graphe pondéré ; n'affecte PAS isUnlocked (qui reste un ET logique
 * strict par seuil, cf. docblock CompetencyGraphService).
 *
 * Anti-auto-référence : contrainte CHECK en base (MySQL >= 8.0.16 / MariaDB >=
 * 10.2 / SQLite [tests] l'honorent nativement), RENFORCÉE en profondeur par une
 * garde applicative (voir CompetencyGraphService) avant toute création via le
 * code métier. SQLite exige le CHECK au moment du CREATE TABLE (pas d'ALTER
 * TABLE ADD CONSTRAINT supporté) : on le déclare donc directement dans le
 * Schema::create ci-dessous, via un raw statement portable aux deux drivers.
 *
 * cascadeOnDelete sur les deux FK : si une compétence disparaît, toute relation
 * qui la mentionne (comme dépendante OU comme prérequis) part avec elle (aucune
 * arête orpheline). Migration ADDITIVE guardée (hasTable). RÉTROCOMPAT : aucune
 * relation créée = comportement actuel inchangé (graphe vide, tout déverrouillé).
 * down() = drop de la seule table nouvelle (réversible exact).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_competency_relations')) {
            return;
        }

        Schema::create('academy_competency_relations', function (Blueprint $table): void {
            $table->id();
            // Compétence DÉPENDANTE (celle qui requiert le prérequis).
            $table->unsignedBigInteger('competency_id');
            // Compétence PRÉREQUISE (celle qu'il faut maîtriser en premier).
            $table->unsignedBigInteger('requires_competency_id');
            // Seuil de maîtrise [0..1] exigé sur le prérequis (0.70 = 70 %).
            $table->decimal('mastery_threshold', 4, 3)->default(0.700);
            // Pondération [0..1] du prérequis parmi plusieurs (informatif, défaut 1.0).
            $table->decimal('weight', 4, 3)->default(1.000);
            $table->timestamps();

            $table->foreign('competency_id')->references('id')->on('academy_competencies')->cascadeOnDelete();
            $table->foreign('requires_competency_id')->references('id')->on('academy_competencies')->cascadeOnDelete();

            $table->index('competency_id');
            $table->index('requires_competency_id');
            // Anti-doublon : un même couple (dépendante, prérequis) ne peut exister qu'une fois.
            $table->unique(['competency_id', 'requires_competency_id'], 'academy_comp_rel_unique');
        });

        // Anti-auto-référence en base, DÉCLARÉE APRÈS le CREATE TABLE via une
        // commande dédiée par driver (SQLite n'accepte le CHECK qu'au moment du
        // CREATE, MySQL/PostgreSQL acceptent l'ALTER TABLE). Renforcée en
        // profondeur par une garde applicative (CompetencyGraphService).
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite : aucun ALTER TABLE ADD CONSTRAINT — on recrée la table avec
            // le CHECK inline (coût négligeable : table vide à ce stade).
            DB::statement('DROP TABLE academy_competency_relations');
            DB::statement(<<<'SQL'
                CREATE TABLE academy_competency_relations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    competency_id INTEGER UNSIGNED NOT NULL,
                    requires_competency_id INTEGER UNSIGNED NOT NULL,
                    mastery_threshold NUMERIC(4, 3) NOT NULL DEFAULT 0.700,
                    weight NUMERIC(4, 3) NOT NULL DEFAULT 1.000,
                    created_at DATETIME,
                    updated_at DATETIME,
                    CHECK (competency_id <> requires_competency_id),
                    FOREIGN KEY (competency_id) REFERENCES academy_competencies(id) ON DELETE CASCADE,
                    FOREIGN KEY (requires_competency_id) REFERENCES academy_competencies(id) ON DELETE CASCADE
                )
                SQL);
            DB::statement('CREATE INDEX academy_competency_relations_competency_id_index ON academy_competency_relations (competency_id)');
            DB::statement('CREATE INDEX academy_competency_relations_requires_competency_id_index ON academy_competency_relations (requires_competency_id)');
            DB::statement('CREATE UNIQUE INDEX academy_comp_rel_unique ON academy_competency_relations (competency_id, requires_competency_id)');
        } else {
            try {
                DB::statement(
                    'ALTER TABLE academy_competency_relations '
                    . 'ADD CONSTRAINT academy_comp_rel_no_self_chk '
                    . 'CHECK (competency_id <> requires_competency_id)'
                );
            } catch (\Throwable) {
                // Driver/version sans support CHECK : la garde applicative
                // (CompetencyGraphService) reste la source de vérité, cette
                // contrainte SQL n'est qu'une défense en profondeur.
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_competency_relations');
    }
};
