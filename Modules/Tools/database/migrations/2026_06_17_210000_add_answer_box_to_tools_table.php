<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            if (! Schema::hasColumn('tools', 'answer_summary')) {
                $table->text('answer_summary')->nullable()->after('description');
            }
            if (! Schema::hasColumn('tools', 'answer_points')) {
                $table->json('answer_points')->nullable()->after('answer_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            if (Schema::hasColumn('tools', 'answer_points')) {
                $table->dropColumn('answer_points');
            }
            if (Schema::hasColumn('tools', 'answer_summary')) {
                $table->dropColumn('answer_summary');
            }
        });
    }
};
