<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->timestamp('tutorials_last_scanned_at')->nullable()->after('updated_at');
            $table->index('tutorials_last_scanned_at', 'idx_tools_tutorials_last_scanned_at');
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropIndex('idx_tools_tutorials_last_scanned_at');
            $table->dropColumn('tutorials_last_scanned_at');
        });
    }
};
