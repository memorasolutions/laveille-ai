<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'answer_summary')) {
                $table->text('answer_summary')->nullable()->after('video_summary');
            }
            if (! Schema::hasColumn('articles', 'answer_points')) {
                $table->json('answer_points')->nullable()->after('answer_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'answer_points')) {
                $table->dropColumn('answer_points');
            }
            if (Schema::hasColumn('articles', 'answer_summary')) {
                $table->dropColumn('answer_summary');
            }
        });
    }
};
