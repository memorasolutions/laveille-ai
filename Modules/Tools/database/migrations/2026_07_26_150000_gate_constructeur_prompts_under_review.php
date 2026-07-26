<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Tools\Models\Tool;

/**
 * Remise en révision de /outils/constructeur-prompts (2026-07-26) : la refonte "objectif
 * d'abord" (v1.131.0-1.132.0) a retiré des options existantes (type de prompt zero-shot/
 * few-shot/chain-of-thought en sélection explicite, demande de sortie en canvas/artefact)
 * et l'utilisateur juge le résultat régressif par rapport à l'outil précédent. Gate
 * superadmin-only (même pattern que Décido/Minuteur visuel/Anonymiseur/QT en développement)
 * le temps de rebâtir une version conforme aux meilleures pratiques 2026 + charte complète.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            return;
        }

        Tool::where('slug', 'constructeur-prompts')->update(['is_under_construction' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            return;
        }

        Tool::where('slug', 'constructeur-prompts')->update(['is_under_construction' => false]);
    }
};
