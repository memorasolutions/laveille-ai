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

        $data = [
            'name' => 'QT — Quotient Techno',
            'description' => 'Teste ta culture techno et découvre ton Quotient Techno : 10 questions sur l\'IA, le numérique et la cybersécurité, score façon QI.',
            'icon' => '🧠',
            'category' => 'jeux',
            'is_active' => true,
            'sort_order' => 13,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('tools', 'is_under_construction')) {
            $data['is_under_construction'] = true;
        }

        DB::table('tools')->updateOrInsert(['slug' => 'qt'], $data);
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'qt')->delete();
        }
    }
};
