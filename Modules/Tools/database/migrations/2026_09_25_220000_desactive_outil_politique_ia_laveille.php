<?php

/**
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Desactive l'outil « Generateur de politique IA » sur laveille.ai (decision du fondateur du
 * 2026-09-25 : l'outil n'est pas retenu pour laveille, mais son MODULE/CODE est CONSERVE pour
 * etre reemploye sur un autre site). `is_active = false` le retire de la liste publique et du
 * service (scopeActive), SANS supprimer la ligne ni aucun code : la vue politique-ia.blade.php,
 * le controleur generique et la migration de creation (2026_09_15_210000) restent intacts, donc
 * l'outil reste portable ailleurs. Geste editorial reversible : `down()` le reactive.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SLUG = 'politique-ia';

    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')->where('slug', self::SLUG)->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')->where('slug', self::SLUG)->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);
    }
};
