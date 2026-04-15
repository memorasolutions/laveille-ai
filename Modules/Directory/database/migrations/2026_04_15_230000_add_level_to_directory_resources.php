<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_resources', function (Blueprint $table) {
            $table->string('level', 20)->nullable()->after('language');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::table('directory_resources', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropColumn('level');
        });
    }
};
