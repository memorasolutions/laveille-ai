<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Corrige un « plus » FAUX de la fiche annuaire LucidNest : « Conçu et hébergé au Québec ».
 * LucidNest n'est PAS hébergé au Québec (l'hébergement est assuré par une entreprise canadienne,
 * cf. la FAQ de la fiche); il est conçu par une entreprise du Québec et conforme à la Loi 25. On
 * remplace donc ce « plus » par « Conçu par une entreprise du Québec » (la conformité Loi 25 est
 * déjà portée par le « plus » Données sous contrôle). Les « plus » sont séparés par des virgules à
 * l'affichage : le nouveau libellé n'en contient aucune. On ne touche NI au `description` (« conçu
 * et développé au Québec » = exact, lieu de conception) NI à la FAQ.
 * Réversible : down() rétablit l'ancien libellé.
 */
return new class extends Migration
{
    private const URL_LIKE = '%lucidnest.io%';

    private const OLD_PROS = "Conçu pour le fonctionnement TDAH, par un fondateur TDAH, Capture ultra-rapide sans obligation de classer, Palier gratuit généreux sans carte de crédit et qui n'expire jamais, Données sous contrôle (export total, authentification à deux facteurs, chiffrement, Loi 25 et RGPD), Conçu et hébergé au Québec";

    private const NEW_PROS = "Conçu pour le fonctionnement TDAH, par un fondateur TDAH, Capture ultra-rapide sans obligation de classer, Palier gratuit généreux sans carte de crédit et qui n'expire jamais, Données sous contrôle (export total, authentification à deux facteurs, chiffrement, Loi 25 et RGPD), Conçu par une entreprise du Québec";

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        DB::table('directory_tools')
            ->where('url', 'like', self::URL_LIKE)
            ->update([
                'pros' => json_encode(['fr_CA' => self::NEW_PROS], JSON_UNESCAPED_UNICODE),
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
                'pros' => json_encode(['fr_CA' => self::OLD_PROS], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }
};
