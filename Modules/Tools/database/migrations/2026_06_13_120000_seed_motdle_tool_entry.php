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
            ['slug' => 'motdle'],
            [
                'name' => 'Motdle',
                'description' => 'Le mot tech du jour à deviner en 6 essais : un Wordle français sur le vocabulaire de l\'IA et du numérique.',
                'icon' => '🔤',
                'category' => 'jeux',
                'is_active' => true,
                'sort_order' => 12,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'motdle')->delete();
        }
    }
};
