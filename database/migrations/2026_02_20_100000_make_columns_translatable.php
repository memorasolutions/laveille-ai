<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'articles' => ['title', 'slug', 'content', 'excerpt'],
        'blog_categories' => ['name', 'slug', 'description'],
        'static_pages' => ['title', 'slug', 'content', 'excerpt', 'meta_title', 'meta_description'],
        'seo_meta_tags' => ['title', 'description', 'keywords', 'og_title', 'og_description'],
    ];

    private array $uniqueIndexes = [
        'articles' => ['articles_slug_unique' => 'slug'],
        'blog_categories' => ['blog_categories_name_unique' => 'name', 'blog_categories_slug_unique' => 'slug'],
        'static_pages' => ['static_pages_slug_unique' => 'slug'],
    ];

    public function up(): void
    {
        $isMySQL = DB::connection()->getDriverName() === 'mysql';

        // 1. Drop unique indexes (can't have unique on JSON columns)
        foreach ($this->uniqueIndexes as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $indexName => $column) {
                    $blueprint->dropUnique($indexName);
                }
            });
        }

        // MySQL only: wrap existing data and convert columns to JSON
        if ($isMySQL) {
            foreach ($this->tables as $table => $columns) {
                foreach ($columns as $column) {
                    DB::statement("
                        UPDATE {$table}
                        SET {$column} = JSON_OBJECT('en', {$column})
                        WHERE {$column} IS NOT NULL
                    ");
                }
            }

            foreach ($this->tables as $table => $columns) {
                foreach ($columns as $column) {
                    DB::statement("ALTER TABLE {$table} MODIFY {$column} JSON");
                }
            }
        }
        // SQLite: columns stay TEXT, HasTranslations handles JSON encoding/decoding
    }

    public function down(): void
    {
        $isMySQL = DB::connection()->getDriverName() === 'mysql';

        if ($isMySQL) {
            foreach ($this->tables as $table => $columns) {
                foreach ($columns as $column) {
                    DB::statement("ALTER TABLE {$table} MODIFY {$column} TEXT");

                    DB::statement("
                        UPDATE {$table}
                        SET {$column} = JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.en'))
                        WHERE {$column} IS NOT NULL
                        AND JSON_VALID({$column}) = 1
                    ");
                }
            }
        }

        // Restore unique indexes
        foreach ($this->uniqueIndexes as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $indexName => $column) {
                    $blueprint->unique($column, $indexName);
                }
            });
        }
    }
};
