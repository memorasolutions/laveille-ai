<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DirectorySeeder extends Seeder
{
    public function run(): void
    {
        $catId = DB::table('directory_categories')->insertGetId([
            'name' => json_encode(['fr_CA' => 'Assistants IA', 'fr' => 'Assistants IA']),
            'slug' => json_encode(['fr_CA' => 'assistants-ia', 'fr' => 'assistants-ia']),
            'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $tools = [
            ['name' => 'ChatGPT', 'slug' => 'chatgpt', 'short' => 'Assistant IA polyvalent par OpenAI.', 'desc' => 'ChatGPT est un assistant conversationnel basé sur GPT-4, capable de rédiger du texte, analyser des données, générer du code et bien plus.', 'url' => 'https://chat.openai.com', 'pricing' => 'freemium'],
            ['name' => 'Claude', 'slug' => 'claude', 'short' => 'Assistant IA par Anthropic, axé sécurité.', 'desc' => 'Claude est un assistant IA développé par Anthropic, reconnu pour son raisonnement, sa fenêtre de contexte étendue et son approche constitutionnelle de la sécurité.', 'url' => 'https://claude.ai', 'pricing' => 'freemium'],
            ['name' => 'Midjourney', 'slug' => 'midjourney', 'short' => 'Génération d\'images par IA.', 'desc' => 'Midjourney est un outil de génération d\'images par IA qui crée des visuels artistiques à partir de descriptions textuelles.', 'url' => 'https://midjourney.com', 'pricing' => 'paid'],
            ['name' => 'Cursor', 'slug' => 'cursor', 'short' => 'Éditeur de code assisté par IA.', 'desc' => 'Cursor est un éditeur de code intelligent qui intègre l\'IA pour aider les développeurs à écrire, déboguer et refactorer du code plus efficacement.', 'url' => 'https://cursor.com', 'pricing' => 'freemium'],
            ['name' => 'Perplexity', 'slug' => 'perplexity', 'short' => 'Moteur de recherche IA avec sources.', 'desc' => 'Perplexity est un moteur de recherche alimenté par l\'IA qui fournit des réponses sourcées et vérifiables à vos questions.', 'url' => 'https://perplexity.ai', 'pricing' => 'freemium'],
            ['name' => 'Gemini', 'slug' => 'gemini', 'short' => 'Assistant IA multimodal par Google.', 'desc' => 'Gemini est le modèle IA multimodal de Google, capable de traiter du texte, des images, du code et des données, intégré à l\'écosystème Google.', 'url' => 'https://gemini.google.com', 'pricing' => 'freemium'],
        ];

        foreach ($tools as $t) {
            $toolId = DB::table('directory_tools')->insertGetId([
                'name' => json_encode(['fr_CA' => $t['name'], 'fr' => $t['name']]),
                'slug' => json_encode(['fr_CA' => $t['slug'], 'fr' => $t['slug']]),
                'description' => json_encode(['fr_CA' => $t['desc'], 'fr' => $t['desc']]),
                'short_description' => json_encode(['fr_CA' => $t['short'], 'fr' => $t['short']]),
                'url' => $t['url'], 'pricing' => $t['pricing'], 'status' => 'published',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('directory_category_tool')->insert([
                'directory_category_id' => $catId, 'directory_tool_id' => $toolId,
            ]);
        }
    }
}
