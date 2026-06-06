<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit SEO 2026-06-06 (P2) : la méta-description du constructeur de prompts ne faisait
 * que 53 caractères (« Construisez des prompts IA structurés et efficaces. »).
 * On l'enrichit à ~165 car. (getShareData dérive meta_description de tools.description).
 * Réversible via down(). Idempotent (WHERE slug).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tools', 'description')) {
            return;
        }
        DB::table('tools')->where('slug', 'constructeur-prompts')->update([
            'description' => 'Créez des prompts IA optimisés pour ChatGPT, Claude, Gemini et Mistral : choisissez persona, tâche, audience, format et techniques avancées (few-shot, chaîne de pensée). Gratuit, sans inscription.',
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tools', 'description')) {
            return;
        }
        DB::table('tools')->where('slug', 'constructeur-prompts')->update([
            'description' => 'Construisez des prompts IA structurés et efficaces.',
        ]);
    }
};
