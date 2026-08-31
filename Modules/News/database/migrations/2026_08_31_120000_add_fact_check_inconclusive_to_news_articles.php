<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « vérification » - statut orthogonal « vérification non concluante » (2026-08-31,
 * tranché le 2026-08-27, voir docs/specs/2026-08-27-exposition-verifications-panel.md, idée de
 * Gemini « en observation »/« dossier ouvert » retenue au round 2 du panel).
 *
 * Une colonne ADDITIVE, nullable : une fiche ordinaire n'est pas concernée et reste strictement
 * inchangée.
 *
 *   fact_check_inconclusive_at - date à laquelle la fiche a été marquée « vérification non
 *                                concluante ». Une fiche qui a CHERCHÉ une affirmation sans
 *                                pouvoir trancher vers un des cinq verdicts (fact_check_verdict)
 *                                n'est pas une fiche qui n'a rien cherché - les deux doivent
 *                                rester distinguables publiquement (skill /actu2, section « Le
 *                                verdict de vérification », règle 6).
 *
 * ORTHOGONAL, jamais un sixième verdict : ce champ ne remplace jamais fact_check_verdict, il ne
 * fait qu'exposer une troisième issue légitime entre « rien examiné » et « verdict tranché ». Les
 * deux sont mutuellement exclusifs - poser un verdict efface toujours ce champ (une conclusion
 * prime sur « encore en recherche »), et la porte d'écriture (news:apply) refuse tout payload qui
 * tenterait de poser les deux à la fois. Les colonnes existantes fact_check_claim et
 * fact_check_source sont réutilisées telles quelles pour décrire ce qui a été examiné : rien de
 * nouveau à ajouter pour ça.
 *
 * Réversible : down() retire la colonne sans toucher au reste de la table.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'fact_check_inconclusive_at')) {
                $table->timestamp('fact_check_inconclusive_at')->nullable()->after('fact_check_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'fact_check_inconclusive_at')) {
                $table->dropColumn('fact_check_inconclusive_at');
            }
        });
    }
};
