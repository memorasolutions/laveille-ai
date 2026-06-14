<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Remise EN CONSTRUCTION de QT le temps de développer les nouveaux types de questions.
    public function up(): void
    {
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'is_under_construction')) {
            DB::table('tools')->where('slug', 'qt')->update([
                'is_under_construction' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'is_under_construction')) {
            DB::table('tools')->where('slug', 'qt')->update([
                'is_under_construction' => false,
                'updated_at' => now(),
            ]);
        }
    }
};
