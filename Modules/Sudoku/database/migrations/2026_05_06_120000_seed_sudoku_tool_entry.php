<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')->updateOrInsert(
            ['slug' => 'sudoku'],
            [
                'name' => 'Sudoku quotidien',
                'description' => 'Jouez le Sudoku du jour : 5 difficultes, classements live, streak quotidien.',
                'icon' => '🔢',
                'is_active' => true,
                'sort_order' => 11,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'sudoku')->delete();
        }
    }
};
