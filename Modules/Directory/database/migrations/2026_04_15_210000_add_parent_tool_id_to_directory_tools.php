<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_tool_id')->nullable()->after('id');
            $table->string('ecosystem_tag', 50)->nullable()->after('parent_tool_id');

            $table->foreign('parent_tool_id')
                  ->references('id')
                  ->on('directory_tools')
                  ->onDelete('set null');

            $table->index('ecosystem_tag');
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropForeign(['parent_tool_id']);
            $table->dropIndex(['ecosystem_tag']);
            $table->dropColumn(['parent_tool_id', 'ecosystem_tag']);
        });
    }
};
