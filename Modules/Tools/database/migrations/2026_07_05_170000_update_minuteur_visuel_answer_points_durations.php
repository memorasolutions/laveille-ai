<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #751-758 : le 45 min est retiré des présélections (demande utilisateur) et remplacé par
 * jusqu'à 2 durées personnalisées épinglables pour les utilisateurs connectés. Met à jour le
 * bullet answer_points concerné (les autres champs ne mentionnent aucun chiffre de durée).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tools') || ! Schema::hasColumn('tools', 'answer_points')) {
            return;
        }

        $tool = DB::table('tools')->where('slug', 'minuteur-visuel')->first();
        if (! $tool || ! $tool->answer_points) {
            return;
        }

        $points = json_decode($tool->answer_points, true) ?: [];
        foreach ($points as $i => $point) {
            if (str_starts_with($point, 'Présélections 5/10/15/25/45')) {
                $points[$i] = 'Présélections 5/10/15/25 minutes + Pomodoro 25/5, et jusqu\'à 2 durées personnalisées épinglées pour les utilisateurs connectés';
            }
        }

        DB::table('tools')->where('slug', 'minuteur-visuel')->update([
            'answer_points' => json_encode($points, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools') || ! Schema::hasColumn('tools', 'answer_points')) {
            return;
        }

        $tool = DB::table('tools')->where('slug', 'minuteur-visuel')->first();
        if (! $tool || ! $tool->answer_points) {
            return;
        }

        $points = json_decode($tool->answer_points, true) ?: [];
        foreach ($points as $i => $point) {
            if (str_starts_with($point, 'Présélections 5/10/15/25 minutes')) {
                $points[$i] = 'Présélections 5/10/15/25/45 minutes + Pomodoro 25/5';
            }
        }

        DB::table('tools')->where('slug', 'minuteur-visuel')->update([
            'answer_points' => json_encode($points, JSON_UNESCAPED_UNICODE),
        ]);
    }
};
