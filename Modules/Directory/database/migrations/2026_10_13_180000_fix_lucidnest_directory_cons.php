<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Corrige la fiche annuaire LucidNest : la section « Les moins » décrivait une plateforme en bêta
 * sans application, ce qui ne correspond plus à la réalité (plateforme en version A1 en service,
 * application web progressive installable, produit surtout textuel pour qui l'OCR n'est pas
 * pertinent). On met à jour `cons` et on fait passer `lifecycle_status` de « beta » à « active ».
 * La section « Les moins » est séparée par des virgules à l'affichage : chaque moins ne contient
 * donc AUCUNE virgule interne, sinon il serait coupé en plusieurs puces.
 * Idempotente de fait (met à jour la ligne si elle existe). Réversible : down() rétablit les
 * anciennes valeurs.
 */
return new class extends Migration
{
    private const URL_LIKE = '%lucidnest.io%';

    private const OLD_CONS = "En bêta ouverte : jeune et en évolution, Application web sans application native iOS ou Android annoncée, Interface d'abord en français, Certaines fonctions (images dans les notes, OCR, assistant vocal) encore en pilote";

    private const NEW_CONS = "Jeune plateforme lancée en 2026 et encore en développement actif, Offerte en application web progressive (PWA) installable sur mobile et ordinateur plutôt qu'en application native dans les magasins";

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        DB::table('directory_tools')
            ->where('url', 'like', self::URL_LIKE)
            ->update([
                'cons' => json_encode(['fr_CA' => self::NEW_CONS], JSON_UNESCAPED_UNICODE),
                'lifecycle_status' => 'active',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        DB::table('directory_tools')
            ->where('url', 'like', self::URL_LIKE)
            ->update([
                'cons' => json_encode(['fr_CA' => self::OLD_CONS], JSON_UNESCAPED_UNICODE),
                'lifecycle_status' => 'beta',
                'updated_at' => now(),
            ]);
    }
};
