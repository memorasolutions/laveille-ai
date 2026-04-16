<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('directory_tools')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"')) = ?", ['notion-ai'])
            ->update([
                'has_education_pricing' => true,
                'education_pricing_type' => 'free',
                'education_pricing_details' => json_encode([
                    'fr_CA' => "Notion pour l'éducation — plan Plus gratuit pour les étudiants et enseignants avec courriel institutionnel. Espace de travail complet, IA incluse.",
                ]),
                'education_pricing_url' => 'https://www.notion.com/product/notion-for-education',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('directory_tools')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"')) = ?", ['notion-ai'])
            ->update([
                'has_education_pricing' => false,
                'education_pricing_type' => null,
                'education_pricing_details' => null,
                'education_pricing_url' => null,
                'updated_at' => now(),
            ]);
    }
};
