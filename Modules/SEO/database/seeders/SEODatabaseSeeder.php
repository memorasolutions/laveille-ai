<?php

declare(strict_types=1);

namespace Modules\SEO\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SEO\Models\MetaTag;

class SEODatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'url_pattern' => '/',
                'title' => 'Accueil - Laravel Core',
                'description' => 'Application Laravel Core modulaire',
                'robots' => 'index, follow',
                'twitter_card' => 'summary',
                'is_active' => true,
            ],
            [
                'url_pattern' => '/contact',
                'title' => 'Contact - Laravel Core',
                'description' => 'Contactez-nous pour toute question',
                'robots' => 'index, follow',
                'twitter_card' => 'summary',
                'is_active' => true,
            ],
            [
                'url_pattern' => '/blog/*',
                'title' => 'Blog - Laravel Core',
                'description' => 'Articles et actualités',
                'robots' => 'index, follow',
                'twitter_card' => 'summary_large_image',
                'is_active' => true,
            ],
        ];

        foreach ($defaults as $metaTag) {
            MetaTag::firstOrCreate(
                ['url_pattern' => $metaTag['url_pattern']],
                $metaTag
            );
        }
    }
}
