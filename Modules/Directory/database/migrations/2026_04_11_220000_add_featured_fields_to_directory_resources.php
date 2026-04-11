<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            if (! Schema::hasColumn('directory_tools', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }
            $table->dateTime('featured_until')->nullable()->default(null);
            $table->unsignedInteger('featured_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropColumn(['featured_until', 'featured_order']);
        });
    }
};
