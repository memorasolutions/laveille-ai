<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retrait de Motdle (remplacé par QT) : la carte n'apparaît plus dans /outils.
 * Réversible (down réactive). Code/route conservés (route redirigée).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'motdle')->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'motdle')->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }
    }
};
