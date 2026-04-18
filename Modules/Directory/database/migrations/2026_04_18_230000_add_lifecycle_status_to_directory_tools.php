<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->string('lifecycle_status', 20)->default('active')->index()->after('status');
            $table->date('lifecycle_date')->nullable()->after('lifecycle_status');
            $table->string('lifecycle_replacement_url', 500)->nullable()->after('lifecycle_date');
            $table->unsignedBigInteger('lifecycle_replacement_tool_id')->nullable()->after('lifecycle_replacement_url');
            $table->text('lifecycle_notes')->nullable()->after('lifecycle_replacement_tool_id');

            $table->foreign('lifecycle_replacement_tool_id')
                ->references('id')
                ->on('directory_tools')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropForeign(['lifecycle_replacement_tool_id']);
            $table->dropColumn([
                'lifecycle_status',
                'lifecycle_date',
                'lifecycle_replacement_url',
                'lifecycle_replacement_tool_id',
                'lifecycle_notes',
            ]);
        });
    }
};
