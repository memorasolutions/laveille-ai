<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_conversations') && ! $this->hasIndex('ai_conversations', 'agent_id')) {
            Schema::table('ai_conversations', function (Blueprint $table) {
                $table->index('agent_id');
            });
        }

        if (Schema::hasTable('articles') && ! $this->hasIndex('articles', 'category_id')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->index('category_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_conversations')) {
            Schema::table('ai_conversations', function (Blueprint $table) {
                $table->dropIndex(['agent_id']);
            });
        }

        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropIndex(['category_id']);
            });
        }
    }

    private function hasIndex(string $table, string $column): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if (in_array($column, $index['columns'], true)) {
                return true;
            }
        }

        return false;
    }
};
