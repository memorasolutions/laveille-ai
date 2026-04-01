<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_resources', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_keys($sm->listTableIndexes('directory_resources'));
            if (!in_array('idx_dr_tool_approved', $indexes)) {
                $table->index(['directory_tool_id', 'is_approved'], 'idx_dr_tool_approved');
            }
            if (!in_array('idx_dr_user', $indexes)) {
                $table->index(['user_id', 'created_at'], 'idx_dr_user');
            }
            if (!in_array('idx_dr_approved_date', $indexes)) {
                $table->index(['is_approved', 'created_at'], 'idx_dr_approved_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('directory_resources', function (Blueprint $table) {
            $table->dropIndex('idx_dr_tool_approved');
            $table->dropIndex('idx_dr_user');
            $table->dropIndex('idx_dr_approved_date');
        });
    }
};
