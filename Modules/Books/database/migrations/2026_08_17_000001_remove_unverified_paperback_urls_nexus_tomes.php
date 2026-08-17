<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correction de données réversible - mise en ligne publique de /livres (2026-08-17).
 *
 * Bloquant vérifié par curl sur les fiches produit Amazon.ca réelles (h1 productTitle / <title>) :
 * les liens PAPIER des tomes 2 et 3 de Nexus Neural pointent vers de MAUVAIS livres.
 *
 *   - Tome 2 (amazon_url_paperback = https://amazon.ca/dp/B0GPP4ZYJG) :
 *     la fiche réelle affiche « Nexus Neural: Tome 2 : L'Échiquier », alors que le site vend
 *     « Tome 2 : L'Expansion ».
 *   - Tome 3 (amazon_url_paperback = https://amazon.ca/dp/B0GPPHGB7V) :
 *     la fiche réelle affiche « Nexus Neural: Tome 3 : Convergence », alors que le site vend
 *     « Tome 3 : La Singularité ».
 *
 * Recherche d'un ASIN papier correctement titré : les éditions Kindle correctement titrées
 * (asin_kindle B0GPPGK36T « L'Expansion » et B0GPPNWV6X « La Singularité », toutes deux
 * vérifiées correctes) renvoient elles-mêmes, via leur propre sélecteur de format Amazon
 * (« Broché à partir de... »), vers CES MÊMES ASIN mal titrés (B0GPP4ZYJG et B0GPPHGB7V) - il ne
 * s'agit donc pas d'une erreur de copier-coller côté site, mais d'un mauvais appariement des
 * éditions sur Amazon (KDP) lui-même. Aucune édition papier correctement titrée n'a pu être
 * localisée pour ces deux tomes (recherche Amazon.ca par titre exact, 2026-08-17). Un lien
 * d'achat faux étant pire qu'un lien absent, cette migration RETIRE le lien papier (met
 * amazon_url_paperback à NULL) pour ces deux livres uniquement - le bouton Blade correspondant
 * disparaît alors automatiquement (@if($book->amazon_url_paperback)). Les liens Kindle, vérifiés
 * corrects, ne sont pas touchés. isbn_paperback et price_paperback restent en base (données
 * internes potentiellement utiles le jour où le propriétaire republie/corrige l'édition papier).
 *
 * Réversible : down() restaure les URL d'origine (rollback uniquement - à ne pas utiliser en
 * production sans avoir d'abord confirmé/corrigé le bon ASIN auprès du propriétaire).
 */
return new class extends Migration
{
    /** @return array<string, string> slug => amazon_url_paperback original (mal titré) */
    private function originalUrls(): array
    {
        return [
            'nexus-neural-tome-2' => 'https://amazon.ca/dp/B0GPP4ZYJG',
            'nexus-neural-tome-3' => 'https://amazon.ca/dp/B0GPPHGB7V',
        ];
    }

    public function up(): void
    {
        foreach (array_keys($this->originalUrls()) as $slug) {
            DB::table('books')->where('slug', $slug)->update([
                'amazon_url_paperback' => null,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->originalUrls() as $slug => $url) {
            DB::table('books')->where('slug', $slug)->update([
                'amazon_url_paperback' => $url,
                'updated_at' => now(),
            ]);
        }
    }
};
