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
 * Compteur de clics SORTANTS (clic réel sur "Visiter le site"), distinct de `clicks_count`
 * (qui compte les VUES de la fiche outil, voir PublicDirectoryController::show()). Sert à bâtir
 * le dossier de négociation des programmes d'affiliation (voir plan affiliation 2026-07-24).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->unsignedBigInteger('outbound_clicks_count')->default(0)->after('clicks_count');
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropColumn('outbound_clicks_count');
        });
    }
};
