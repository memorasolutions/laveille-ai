<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->boolean('is_under_construction')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tools', 'is_under_construction')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->dropColumn('is_under_construction');
            });
        }
    }
};
