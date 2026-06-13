<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Image de marque de l'outil QT — Quotient Techno : sert d'og:image (partage social,
 * en JPG car les réseaux sociaux n'acceptent pas WebP/AVIF) + de vignette dans l'annuaire.
 * Fichier : public/images/tools/qt.jpg (+ qt.webp pour l'affichage). Réversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'featured_image')) {
            DB::table('tools')->where('slug', 'qt')->update([
                'featured_image' => 'images/tools/qt.jpg',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'featured_image')) {
            DB::table('tools')->where('slug', 'qt')->update([
                'featured_image' => null,
                'updated_at' => now(),
            ]);
        }
    }
};
