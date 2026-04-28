<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (!Schema::hasColumn('news_articles', 'canonical_url')) {
                $table->string('canonical_url', 2048)->nullable()->after('resolved_url');
                $table->index('canonical_url');
            }
            if (!Schema::hasColumn('news_articles', 'is_potential_duplicate_of')) {
                $table->unsignedBigInteger('is_potential_duplicate_of')->nullable()->after('canonical_url');
                $table->foreign('is_potential_duplicate_of')->references('id')->on('news_articles')->onDelete('set null');
                $table->index('is_potential_duplicate_of');
            }
            if (!Schema::hasColumn('news_articles', 'dedup_score')) {
                $table->decimal('dedup_score', 4, 3)->nullable()->after('is_potential_duplicate_of');
            }
            if (!Schema::hasColumn('news_articles', 'dedup_reason')) {
                $table->string('dedup_reason', 64)->nullable()->after('dedup_score');
            }
        });

        if (!Schema::hasTable('news_dedup_log')) {
            Schema::create('news_dedup_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('new_article_id');
                $table->foreign('new_article_id')->references('id')->on('news_articles')->onDelete('cascade');
                $table->index('new_article_id');
                $table->unsignedBigInteger('matched_article_id')->nullable();
                $table->foreign('matched_article_id')->references('id')->on('news_articles')->onDelete('set null');
                $table->index('matched_article_id');
                $table->decimal('score', 4, 3);
                $table->string('reason', 64);
                $table->json('signals')->nullable();
                $table->string('action', 32);
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            });
        }
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'is_potential_duplicate_of')) {
                $table->dropForeign(['is_potential_duplicate_of']);
            }
            $table->dropColumn([
                'canonical_url',
                'is_potential_duplicate_of',
                'dedup_score',
                'dedup_reason',
            ]);
        });

        Schema::dropIfExists('news_dedup_log');
    }
};
