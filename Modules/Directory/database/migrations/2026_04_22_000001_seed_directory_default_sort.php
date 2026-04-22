<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            'group' => 'directory',
            'key' => 'default_sort',
            'value' => 'random',
            'type' => 'text',
            'description' => 'Ordre par défaut des outils /annuaire. Valeurs : random | popular | recent | name',
            'is_public' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'default_sort')->where('group', 'directory')->delete();
    }
};
