<?php

declare(strict_types=1);

namespace Modules\Privacy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Privacy\Models\LegalPage;

class LegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Politique de confidentialité',
                'is_active' => false,
            ],
            [
                'slug' => 'terms-of-use',
                'title' => 'Conditions d\'utilisation',
                'is_active' => false,
            ],
            [
                'slug' => 'cookie-policy',
                'title' => 'Politique de cookies',
                'is_active' => false,
            ],
        ];

        foreach ($pages as $page) {
            LegalPage::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'content' => $page['content'] ?? '<p>Contenu à personnaliser depuis l\'administration.</p>',
                    'is_active' => $page['is_active'],
                ]
            );
        }
    }
}
