<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #190 (2026-05-06) : ajoute category + views_count pour filtre + tri
 * popularite + badge Tendance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('description');
            $table->unsignedInteger('views_count')->default(0)->after('sort_order');
            $table->index('category');
            $table->index('views_count');
        });

        // Categorisation auto des 12 outils existants
        $categories = [
            'calculatrice-taxes' => 'calcul',
            'generateur-mots-passe' => 'securite',
            'generateur-equipes' => 'generation',
            'tirage-presentations' => 'generation',
            'liens-google' => 'communication',
            'code-qr' => 'communication',
            'constructeur-prompts' => 'communication',
            'simulateur-fiscal' => 'calcul',
            'roue-tirage' => 'generation',
            'oscilloscope-rlc' => 'calcul',
            'mots-croises' => 'jeux',
            'sudoku' => 'jeux',
        ];
        foreach ($categories as $slug => $cat) {
            DB::table('tools')->where('slug', $slug)->update(['category' => $cat]);
        }
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropIndex(['views_count']);
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'views_count']);
        });
    }
};
