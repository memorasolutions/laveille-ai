<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads_placements', function (Blueprint $table) {
            // Identifiant d'unité d'annonce AdSense (data-ad-slot). Vide = pub directe maison.
            $table->string('ad_slot', 32)->nullable()->after('ad_code');
            $table->string('ad_format', 20)->nullable()->default('auto')->after('ad_slot');
            // Hauteur réservée en pixels (anti-saut de page / CLS).
            $table->unsignedSmallInteger('min_height')->nullable()->after('ad_format');
            // Chargement différé à l'approche du viewport.
            $table->boolean('lazy')->default(true)->after('min_height');

            // Un emplacement AdSense pur n'a pas de code de pub directe : la colonne devient facultative.
            $table->text('ad_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rétablir NOT NULL exige des valeurs : les lignes AdSense pures reçoivent une chaîne vide
        // (aucune donnée supprimée), puis les colonnes ajoutées sont retirées.
        DB::table('ads_placements')->whereNull('ad_code')->update(['ad_code' => '']);

        Schema::table('ads_placements', function (Blueprint $table) {
            $table->dropColumn(['ad_slot', 'ad_format', 'min_height', 'lazy']);
            $table->text('ad_code')->nullable(false)->change();
        });
    }
};
