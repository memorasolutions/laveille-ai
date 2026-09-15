<?php

/**
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Inscrit le générateur de politique d'utilisation de l'IA parmi les outils publics (#2584).
 *
 * L'outil naît EN CONSTRUCTION, volontairement. Le drapeau `is_under_construction` le rend visible
 * aux seuls administrateurs : le document produit sera lu par des PME qui prendront des décisions
 * d'organisation en s'y appuyant, et la conception exige qu'il soit relu avant d'être public.
 * Passer le drapeau à false est donc un geste éditorial, pas une étape technique oubliée.
 *
 * Idempotente par `updateOrInsert` : rejouer la migration ne crée jamais de doublon de slug.
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

        DB::table('tools')->updateOrInsert(
            ['slug' => self::SLUG],
            [
                'name' => 'Générateur de politique IA',
                'description' => "Crée en dix questions une politique d'utilisation de l'IA adaptée à ton entreprise. Tes réponses ne quittent jamais ton navigateur.",
                'icon' => '📋',
                'category' => 'productivite',
                'is_active' => true,
                'is_under_construction' => true,
                'sort_order' => 17,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')->where('slug', self::SLUG)->delete();
    }
};
