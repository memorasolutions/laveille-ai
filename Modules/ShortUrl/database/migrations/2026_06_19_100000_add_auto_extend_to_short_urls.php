<?php

/**
 * Migration idempotente : ajoute la colonne `auto_extend` (boolean, default TRUE)
 * sur la table `short_urls`.
 *
 * DEFAULT TRUE = comportement ACTUEL inchangé pour tous les liens existants
 * (le raccourcisseur prolonge expires_at de +12 mois à chaque scan).
 *
 * Les liens créés par QrDynamicLinkController posent auto_extend=false :
 * leur date d'expiration reste FIXE — elle n'est jamais prolongée.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('short_urls', 'auto_extend')) {
            return;
        }

        Schema::table('short_urls', function (Blueprint $table) {
            $table->boolean('auto_extend')->default(true)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropColumnIfExists('auto_extend');
        });
    }
};
