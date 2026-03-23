<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

class DirectoryCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Assistants IA', 'icon' => '🤖', 'slug' => 'assistants-ia'],
            ['name' => "Génération d'images", 'icon' => '🎨', 'slug' => 'generation-images'],
            ['name' => 'Audio et voix', 'icon' => '🎵', 'slug' => 'audio-voix'],
            ['name' => 'Vidéo', 'icon' => '🎬', 'slug' => 'video'],
            ['name' => 'Développement', 'icon' => '💻', 'slug' => 'developpement'],
            ['name' => 'Productivité', 'icon' => '📊', 'slug' => 'productivite'],
            ['name' => 'Design', 'icon' => '🖌️', 'slug' => 'design'],
            ['name' => 'Recherche', 'icon' => '🔍', 'slug' => 'recherche'],
        ];

        foreach ($categories as $data) {
            $category = Category::whereRaw("JSON_EXTRACT(slug, '$.fr_CA') = ?", ['"' . $data['slug'] . '"'])->first();
            if (! $category) {
                $category = new Category();
            }
            $category->icon = $data['icon'];
            $category->setTranslation('name', 'fr_CA', $data['name']);
            $category->setTranslation('slug', 'fr_CA', $data['slug']);
            $category->save();
        }

        $assignments = [
            'assistants-ia' => ['chatgpt', 'claude', 'copilot', 'gemini'],
            'generation-images' => ['midjourney', 'leonardo-ai', 'ideogram-ai'],
            'audio-voix' => ['suno', 'elevenlabs'],
            'video' => ['runway', 'heygen'],
            'developpement' => ['cursor', 'v0', 'bolt', 'lovable'],
            'productivite' => ['notion-ai', 'gamma', 'notebooklm', 'napkin-ai'],
            'design' => ['canva-ai'],
            'recherche' => ['perplexity'],
        ];

        foreach ($assignments as $catSlug => $toolSlugs) {
            $category = Category::whereRaw("JSON_EXTRACT(slug, '$.fr_CA') = ?", ['"' . $catSlug . '"'])->first();
            if (! $category) {
                continue;
            }

            foreach ($toolSlugs as $toolSlug) {
                $tool = Tool::where('slug->fr_CA', $toolSlug)->first();
                if ($tool) {
                    $tool->categories()->sync([$category->id]);
                }
            }
        }
    }
}
