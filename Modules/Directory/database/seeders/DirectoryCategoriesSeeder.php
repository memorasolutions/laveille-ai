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
            ['name' => 'Ecriture IA', 'icon' => '✍️', 'slug' => 'ecriture-ia'],
            ['name' => "Generation d'images", 'icon' => '🎨', 'slug' => 'generation-images'],
            ['name' => 'Audio et voix', 'icon' => '🎵', 'slug' => 'audio-voix'],
            ['name' => 'Video IA', 'icon' => '🎬', 'slug' => 'video'],
            ['name' => 'Code et developpement', 'icon' => '💻', 'slug' => 'developpement'],
            ['name' => 'Productivite', 'icon' => '📊', 'slug' => 'productivite'],
            ['name' => 'Design et creation', 'icon' => '🖌️', 'slug' => 'design'],
            ['name' => 'Recherche et analyse', 'icon' => '🔍', 'slug' => 'recherche'],
            ['name' => 'SEO, GEO et AEO', 'icon' => '📈', 'slug' => 'seo-geo-aeo'],
            ['name' => 'Education et formation', 'icon' => '🎓', 'slug' => 'education'],
            ['name' => 'Agents IA', 'icon' => '🤝', 'slug' => 'agents-ia'],
            ['name' => 'Musique IA', 'icon' => '🎶', 'slug' => 'musique-ia'],
            ['name' => 'Presentations', 'icon' => '📑', 'slug' => 'presentations'],
            ['name' => 'No-code et automatisation', 'icon' => '⚡', 'slug' => 'no-code'],
        ];

        foreach ($categories as $data) {
            $category = Category::where('slug->fr_CA', $data['slug'])->first();
            if (! $category) {
                $category = new Category;
            }
            $category->icon = $data['icon'];
            $category->setTranslation('name', 'fr_CA', $data['name']);
            $category->setTranslation('slug', 'fr_CA', $data['slug']);
            $category->save();
        }

        $assignments = [
            'assistants-ia' => ['chatgpt', 'claude', 'copilot', 'gemini', 'grok', 'mistral-le-chat'],
            'generation-images' => ['midjourney', 'leonardo-ai', 'ideogram-ai', 'stability-ai', 'canva-ai'],
            'audio-voix' => ['elevenlabs'],
            'musique-ia' => ['suno', 'udio'],
            'video' => ['runway', 'heygen', 'pika'],
            'developpement' => ['cursor', 'v0', 'bolt', 'lovable'],
            'productivite' => ['notion-ai', 'notebooklm', 'napkin-ai'],
            'design' => ['canva-ai', 'napkin-ai'],
            'recherche' => ['perplexity'],
            'presentations' => ['gamma'],
            'no-code' => ['bolt', 'lovable', 'v0'],
        ];

        foreach ($assignments as $catSlug => $toolSlugs) {
            $category = Category::where('slug->fr_CA', $catSlug)->first();
            if (! $category) {
                continue;
            }

            foreach ($toolSlugs as $toolSlug) {
                $tool = Tool::where('slug->fr_CA', $toolSlug)->first();
                if ($tool) {
                    $tool->categories()->syncWithoutDetaching([$category->id]);
                }
            }
        }
    }
}
