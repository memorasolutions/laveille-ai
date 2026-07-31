<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('saved_prompts', 'tags')) {
            Schema::table('saved_prompts', function (Blueprint $table) {
                $table->json('tags')->nullable()->after('params');
            });
        }

        if (! Schema::hasColumn('saved_prompts', 'is_favorite')) {
            Schema::table('saved_prompts', function (Blueprint $table) {
                $table->boolean('is_favorite')->default(false)->after('is_public');
                $table->index('is_favorite');
            });
        }
    }

    public function down(): void
    {
        Schema::table('saved_prompts', function (Blueprint $table) {
            if (Schema::hasColumn('saved_prompts', 'is_favorite')) {
                $table->dropIndex(['is_favorite']);
                $table->dropColumn('is_favorite');
            }
            if (Schema::hasColumn('saved_prompts', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};
