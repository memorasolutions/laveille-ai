<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-08-02 : ménage dette technique - `is_public` sur ces 4 tables était accepté en
 * validation et persisté (store/update) mais JAMAIS lu nulle part pour filtrer/exposer
 * publiquement quoi que ce soit (aucune route publique, aucun scopePublic() appelé, aucune
 * vue) - confirmé par grep exhaustif app/Modules/routes/resources/tests avant suppression.
 *
 * NOTE : `saved_crossword_presets.is_public` n'est PAS touché ici - ce modèle-là utilise
 * réellement la colonne (route /jeumc, PublicCrosswordController, toggle public/privé UI).
 * `saved_prompts.is_public` et `avatar_configs.is_public` non plus - hors périmètre de ce
 * ménage, laissés intacts sans jugement sur leur propre statut.
 */
return new class extends Migration
{
    private const TABLES = [
        'saved_draw_presets',
        'saved_wheel_presets',
        'saved_qr_presets',
        'saved_team_presets',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_public')->default(false)->after('params');
            });
        }
    }
};
