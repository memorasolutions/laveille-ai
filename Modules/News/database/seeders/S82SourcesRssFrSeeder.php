<?php

declare(strict_types=1);

namespace Modules\News\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\News\Models\NewsSource;

/**
 * 2026-05-05 #140 : Ajout 5 sources RSS FR haute frequence (note 94/100).
 * Tests live valides : tous flux 200 + items > 10 sauf Maddyness (10).
 * Pattern firstOrCreate pour idempotence et anti-doublon.
 */
class S82SourcesRssFrSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name' => 'Frenchweb',
                'url' => 'https://www.frenchweb.fr/feed/',
                'category' => 'general',
                'language' => 'fr',
                'active' => true,
            ],
            [
                'name' => 'Siècle Digital',
                'url' => 'https://siecledigital.fr/feed/',
                'category' => 'general',
                'language' => 'fr',
                'active' => true,
            ],
            [
                'name' => 'Maddyness',
                'url' => 'https://www.maddyness.com/feed/',
                'category' => 'general',
                'language' => 'fr',
                'active' => true,
            ],
            [
                'name' => '01net',
                'url' => 'https://www.01net.com/feed/',
                'category' => 'general',
                'language' => 'fr',
                'active' => true,
            ],
            [
                'name' => 'ZDNet France',
                'url' => 'https://www.zdnet.fr/actualites/rss/',
                'category' => 'analysis',
                'language' => 'fr',
                'active' => true,
            ],
        ];

        foreach ($sources as $source) {
            NewsSource::firstOrCreate(['url' => $source['url']], $source);
        }
    }
}
