<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pivot liant les fiches de glossaire (dictionary_terms) aux outils de l'annuaire
 * (directory_tools). Nom de table conforme à la convention Laravel de nommage automatique des
 * pivots : les deux noms de modèle au singulier (Term, Tool), snake_case, triés par ordre
 * alphabétique - jamais un nom inventé.
 *
 * Divergence volontaire avec news_article_term (2026_09_13_000000) : NI colonne `source`, NI
 * colonne d'approbation. Ce pivot est CURATÉ - un humain associe un outil à un terme dans
 * l'admin, il n'existe aucun détecteur automatique qui écrit ici (règle explicite du panel dès
 * son premier tour : « via champ structuré validé, jamais texte promotionnel »). Une ligne posée
 * ici est donc validée PAR CONSTRUCTION, contrairement à news_article_term où la majorité des
 * lignes viennent d'une détection automatique (GlossaryLinkifier) et ont besoin d'un filtre
 * d'approbation a posteriori. Ajouter une colonne d'approbation à un pivot déjà vrai à 100 %
 * n'ajouterait qu'un état inutile à maintenir.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('term_tool')) {
            return;
        }

        Schema::create('term_tool', function (Blueprint $table) {
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('tool_id');
            $table->timestamps();

            $table->unique(['term_id', 'tool_id']);

            $table->index('term_id');
            $table->index('tool_id');

            $table->foreign('term_id')
                ->references('id')->on('dictionary_terms')
                ->cascadeOnDelete();

            $table->foreign('tool_id')
                ->references('id')->on('directory_tools')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_tool');
    }
};
