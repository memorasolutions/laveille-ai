<?php

declare(strict_types=1);

namespace Modules\News\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\News\Models\NewsSource;

class S70SourcesAlternativesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name' => 'TechCrunch AI',
                'url' => 'https://techcrunch.com/category/artificial-intelligence/feed/',
                'category' => 'breaking',
                'language' => 'en',
                'active' => true,
            ],
            [
                'name' => 'VentureBeat AI',
                'url' => 'https://venturebeat.com/category/ai/feed/',
                'category' => 'breaking',
                'language' => 'en',
                'active' => true,
            ],
            [
                'name' => 'MIT Technology Review AI',
                'url' => 'https://www.technologyreview.com/topic/artificial-intelligence/feed/',
                'category' => 'analysis',
                'language' => 'en',
                'active' => true,
            ],
            [
                'name' => 'IEEE Spectrum AI',
                'url' => 'https://spectrum.ieee.org/rss/ai',
                'category' => 'analysis',
                'language' => 'en',
                'active' => true,
            ],
            [
                'name' => 'Numerama IA',
                'url' => 'https://www.numerama.com/categorie/intelligence-artificielle/feed/',
                'category' => 'general',
                'language' => 'fr',
                'active' => true,
            ],
        ];

        foreach ($sources as $source) {
            NewsSource::firstOrCreate(['url' => $source['url']], $source);
        }
    }
}
