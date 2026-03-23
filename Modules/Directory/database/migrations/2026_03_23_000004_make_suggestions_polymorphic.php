<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_suggestions', function (Blueprint $table) {
            $table->string('suggestable_type', 255)->nullable()->after('id');
            $table->unsignedBigInteger('suggestable_id')->nullable()->after('suggestable_type');
            $table->index(['suggestable_type', 'suggestable_id']);
        });

        DB::table('directory_suggestions')
            ->whereNotNull('directory_tool_id')
            ->update([
                'suggestable_type' => 'Modules\\Directory\\Models\\Tool',
                'suggestable_id' => DB::raw('directory_tool_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('directory_suggestions', function (Blueprint $table) {
            $table->dropIndex(['suggestable_type', 'suggestable_id']);
            $table->dropColumn(['suggestable_type', 'suggestable_id']);
        });
    }
};
