<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2524, étape 3 bis : introduit `disambiguation_note`, le champ « À ne pas confondre
 * avec » affiché en haut d'une fiche de glossaire, juste après la Définition.
 *
 * POURQUOI ce champ existe et POURQUOI il est borné à cinq cas mesurés : une mesure du
 * 2026-09-11 a montré que certains termes du glossaire sont écrasés, dans les résultats de
 * recherche vidéo, par un homonyme externe plus gros. Taux de faux mesuré terme par terme :
 * Hub 100 %, Perplexité 100 %, Époque 100 %, Batch 100 % (sur un échantillon court), Socket
 * 80 %. La même mesure a aussi montré que l'intuition se trompe : Docker et Latence,
 * anticipés comme risqués, sont à 0 % de faux - preuve qu'une règle par CATÉGORIE (ex. « tout
 * terme au nom court ») produirait de faux avertissements sans rapport avec un risque réel.
 *
 * NE PAS ÉTENDRE ce champ à d'autres termes sans nouvelle mesure, ni maintenant ni « pendant
 * qu'on y est » : étendre par catégorie plutôt que par mesure détruirait la confiance dans les
 * 22 correspondances par ailleurs justes du glossaire, pour un besoin qui n'a jamais été prouvé.
 *
 * Colonne ADDITIVE, TEXT nullable : une migration pose une structure, elle ne porte pas de
 * contenu éditorial - le remplissage des cinq valeurs vit dans
 * Modules\Dictionary\Database\Seeders\DisambiguationNoteSeeder (idempotent, à lancer
 * explicitement), jamais ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dictionary_terms')) {
            return;
        }

        Schema::table('dictionary_terms', function (Blueprint $table) {
            if (! Schema::hasColumn('dictionary_terms', 'disambiguation_note')) {
                $table->text('disambiguation_note')->nullable()->after('definition');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dictionary_terms')) {
            return;
        }

        Schema::table('dictionary_terms', function (Blueprint $table) {
            if (Schema::hasColumn('dictionary_terms', 'disambiguation_note')) {
                $table->dropColumn('disambiguation_note');
            }
        });
    }
};
