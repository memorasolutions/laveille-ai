<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * POURQUOI CETTE MIGRATION EXISTE.
 *
 * La description de l'outil « Anonymiseur de texte » affirmait « conforme Loi 25 et RGPD ».
 * C'est FAUX au sens juridique, et le site se contredisait lui-même sur la même page : son
 * JSON-LD déclare « Pseudonymisation RÉVERSIBLE avec table de correspondance locale », et son
 * propre glossaire rappelle que la pseudonymisation reste un renseignement personnel, donc
 * soumise à la Loi 25 - contrairement à l'anonymisation, qui est irréversible (art. 23 P-39.1).
 *
 * Un visiteur pouvait donc croire que ses données sortaient du champ de la Loi 25 en passant par
 * l'outil. Elles n'en sortent pas. La valeur réelle de l'outil est ailleurs, et elle est vraie :
 * l'IA ne voit jamais les vraies données.
 *
 * Le seeder porte déjà le texte corrigé, mais un seeder ne se rejoue pas en production : sans
 * cette migration, la base garderait l'ancienne description. C'est le piège connu de « corriger
 * le code sans corriger la donnée ».
 *
 * RÉVERSIBLE : down() restaure la description d'origine, à l'identique.
 */
return new class extends Migration
{
    private const SLUG = 'anonymiseur';

    private const ANCIENNE = 'Anonymisez vos textes avant de les envoyer à une IA. 100 % local, conforme Loi 25 et RGPD.';

    private const NOUVELLE = "Remplacez les données personnelles avant d'envoyer un texte à une IA, puis restaurez-les dans la réponse. 100 % local, réversible par conception.";

    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        // On ne touche QUE la ligne dont la description est encore l'ancienne : une
        // description déjà retouchée à la main dans l'admin ne doit pas être écrasée.
        DB::table('tools')
            ->where('slug', self::SLUG)
            ->where('description', self::ANCIENNE)
            ->update(['description' => self::NOUVELLE]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')
            ->where('slug', self::SLUG)
            ->where('description', self::NOUVELLE)
            ->update(['description' => self::ANCIENNE]);
    }
};
