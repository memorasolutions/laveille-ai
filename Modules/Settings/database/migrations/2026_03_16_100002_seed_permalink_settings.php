<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            [
                'key' => 'permalinks.blog_prefix',
                'group' => 'permalinks',
                'type' => 'text',
                'value' => 'blog',
                'description' => 'Préfixe URL des articles (ex: blog, articles, news)',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'permalinks.page_prefix',
                'group' => 'permalinks',
                'type' => 'text',
                'value' => 'page',
                'description' => 'Préfixe URL des pages statiques (ex: page, p)',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'permalinks.category_prefix',
                'group' => 'permalinks',
                'type' => 'text',
                'value' => 'categorie',
                'description' => 'Préfixe URL des catégories',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'permalinks.tag_prefix',
                'group' => 'permalinks',
                'type' => 'text',
                'value' => 'tag',
                'description' => 'Préfixe URL des tags',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'permalinks.trailing_slash',
                'group' => 'permalinks',
                'type' => 'boolean',
                'value' => '0',
                'description' => 'Ajouter un slash final aux URLs',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('settings')->insert($settings);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'permalinks.blog_prefix',
            'permalinks.page_prefix',
            'permalinks.category_prefix',
            'permalinks.tag_prefix',
            'permalinks.trailing_slash',
        ])->delete();
    }
};
