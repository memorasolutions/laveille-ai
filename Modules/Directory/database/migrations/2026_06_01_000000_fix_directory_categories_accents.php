<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige les coquilles (accents manquants) dans les libellés `name` des catégories
 * de l'annuaire. Seule la colonne `name` est modifiée — les slugs (clés de filtrage
 * URL + Alpine) restent intacts. Idempotent : relancer la migration ne cause aucun effet
 * de bord (un UPDATE sur le même slug avec la même valeur est sans danger).
 */
return new class extends Migration
{
    /**
     * La colonne `name` est de type JSON (translatable Spatie).
     * On cible la locale fr_CA via le chemin JSON `name->fr_CA`.
     */
    public function up(): void
    {
        $corrections = [
            // slug (inchangé)    => name corrigé (fr_CA)
            'ecriture-ia'         => 'Écriture IA',
            'generation-images'   => "Génération d'images",
            'video'               => 'Vidéo IA',
            'developpement'       => 'Code et développement',
            'productivite'        => 'Productivité',
            'design'              => 'Design et création',
            'education'           => 'Éducation et formation',
            'presentations'       => 'Présentations',
        ];

        foreach ($corrections as $slug => $correctedName) {
            DB::table('directory_categories')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.fr_CA')) = ?", [$slug])
                ->update([
                    'name' => DB::raw(
                        "JSON_SET(name, '$.fr_CA', " .
                        DB::connection()->getPdo()->quote($correctedName) .
                        ')'
                    ),
                ]);
        }
    }

    /**
     * On ne ré-introduit pas délibérément une faute typographique.
     * Le down() est un no-op volontaire et documenté.
     */
    public function down(): void
    {
        // No-op intentionnel : on ne remet pas les fautes d'accent.
        // Pour revenir en arrière, re-seeder avec DirectoryCategoriesSeeder
        // (ancienne version) ou corriger manuellement via tinker.
    }
};
