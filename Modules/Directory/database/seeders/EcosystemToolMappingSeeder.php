<?php

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EcosystemToolMappingSeeder extends Seeder
{
    public function run(): void
    {
        $slugExpr = "JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"'))";

        $exactMappings = [
            'anthropic' => ['claude'],
            'openai' => ['chatgpt', 'dall-e', 'sora'],
            'google' => ['gemini', 'notebooklm'],
            'stability-ai' => ['stability-ai'],
            'mistral' => ['mistral-le-chat'],
            'adobe' => ['adobe-firefly'],
            'elevenlabs' => ['elevenlabs'],
            'runway' => ['runway'],
            'notion' => ['notion-ai'],
            'jasper' => ['jasper'],
            'midjourney' => ['midjourney'],
        ];

        foreach ($exactMappings as $tag => $slugs) {
            DB::table('directory_tools')
                ->whereRaw("{$slugExpr} IN (" . implode(',', array_fill(0, count($slugs), '?')) . ')', $slugs)
                ->update(['ecosystem_tag' => $tag]);
        }

        $likeMappings = [
            'openai' => ['%gpt%'],
            'google' => ['google%'],
            'meta' => ['%llama%'],
            'microsoft' => ['%copilot%'],
            'adobe' => ['adobe%'],
        ];

        foreach ($likeMappings as $tag => $patterns) {
            foreach ($patterns as $pattern) {
                DB::table('directory_tools')
                    ->whereNull('ecosystem_tag')
                    ->whereRaw("{$slugExpr} LIKE ?", [$pattern])
                    ->update(['ecosystem_tag' => $tag]);
            }
        }
    }
}
