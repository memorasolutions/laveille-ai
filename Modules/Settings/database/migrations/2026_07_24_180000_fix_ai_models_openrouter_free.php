<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $keys = [
            'ai.default_model',
            'ai.chatbot_model',
            'ai.content_model',
            'ai.moderation_model',
            'ai.seo_model',
            'ai.translation_model',
        ];

        foreach ($keys as $key) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['group' => 'ai', 'value' => 'openrouter/free', 'type' => 'string']
            );
        }
    }

    public function down(): void
    {
        // Correctif de donnees (bug service IA indisponible) : pas de retour arriere volontaire.
    }
};
