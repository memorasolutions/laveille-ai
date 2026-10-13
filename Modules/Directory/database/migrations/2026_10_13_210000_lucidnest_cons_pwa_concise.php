<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Fiche annuaire LucidNest, section « Les moins » : le 2e item comparait la PWA a une
 * « application native dans les magasins », ce qui pouvait induire en erreur (laisse croire que
 * l'application devrait se trouver dans un magasin d'applications, ou que c'est un manque).
 * Decision du fondateur (2026-10-13) : retirer la comparaison aux magasins, garder « PWA » et
 * l'expliquer tres brievement. La section « Les moins » est separee par des VIRGULES a
 * l'affichage : le nouvel item ne contient donc AUCUNE virgule interne, sinon il serait coupe en
 * plusieurs puces. Overwrite complet du champ `cons` (robuste). Reversible : down() retablit la
 * valeur precedente.
 */
return new class extends Migration
{
    private const URL_LIKE = '%lucidnest.io%';

    private const OLD_CONS = "Jeune plateforme lancée en 2026 et encore en développement actif, Offerte en application web progressive (PWA) installable sur mobile et ordinateur plutôt qu'en application native dans les magasins";

    private const NEW_CONS = "Jeune plateforme lancée en 2026 et encore en développement actif, Offerte en application web progressive (PWA) : elle s'installe et s'utilise directement depuis le navigateur sur mobile comme sur ordinateur";

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        DB::table('directory_tools')
            ->where('url', 'like', self::URL_LIKE)
            ->update([
                'cons' => json_encode(['fr_CA' => self::NEW_CONS], JSON_UNESCAPED_UNICODE),
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
                'updated_at' => now(),
            ]);
    }
};
