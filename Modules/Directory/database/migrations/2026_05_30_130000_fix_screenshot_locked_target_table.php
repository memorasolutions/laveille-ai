<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correctif : la colonne screenshot_locked avait été ajoutée par erreur à la table `tools`
 * (convention) alors que le modèle Tool utilise `directory_tools`. On répare ici :
 * - ajoute la colonne à `directory_tools` si absente ;
 * - retire la colonne parasite de `tools` si elle y avait été créée.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('directory_tools') && ! Schema::hasColumn('directory_tools', 'screenshot_locked')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->boolean('screenshot_locked')->default(false);
            });
        }

        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'screenshot_locked')) {
            Schema::table('tools', function (Blueprint $table): void {
                $table->dropColumn('screenshot_locked');
            });
        }
    }

    public function down(): void
    {
        // Pas de rollback destructif : on conserve la colonne sur directory_tools.
    }
};
