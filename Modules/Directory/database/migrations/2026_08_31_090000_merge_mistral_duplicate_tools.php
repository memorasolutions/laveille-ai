<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Fusion du doublon annuaire "Mistral" (#875) / "Mistral Le Chat" (#23) - ticket #2076. Vérifié
 * AVANT toute action que ce n'est PAS une distinction éditeur/produit légitime comme celle créée
 * volontairement au glossaire le 2026-08-30 (migration
 * Modules/Dictionary/database/migrations/2026_08_30_120000_add_mistral_term.php, fiche "Mistral" =
 * l'entreprise, "Mistral (Le Chat)" = son produit) : les DEUX fiches annuaire pointent vers la
 * même URL (https://chat.mistral.ai) et décrivent le MÊME produit - la fiche "Mistral" (#875,
 * seedée le 2026-05-09 par MissingPopularToolsSeeder, sans vérification anti-doublon contre la
 * fiche "Mistral Le Chat" déjà présente depuis le 2026-03-25 via DirectorySession127ToolsSeeder)
 * s'ouvre elle-même sur « Le Chat de Mistral AI (disponible à l'adresse https://chat.mistral.ai)
 * n'est pas un simple chatbot conversationnel... ». Aucune des deux ne décrit l'entreprise Mistral
 * AI en tant que telle (financement, effectifs, positionnement indépendant du produit) - deux
 * fiches OUTIL pour le même outil, nées de deux ajouts successifs indépendants.
 *
 * Réutilise le mécanisme déjà en place (`lifecycle_status=archived` + `lifecycle_replacement_tool_id`,
 * redirection 301 automatique dans PublicDirectoryController::show(), commentaire "Doublon
 * fusionné : rediriger définitivement vers l'outil canonique") et la MÊME règle de départage déjà
 * appliquée aux 5 fusions précédentes de ce projet (Jasper #27→#878, LayerGen AI #97→#139, MiniAi
 * #101→#140, Copy.ai #879→#28, Fathom #887→#583, vérifiées le 2026-08-31 - dans les 5 cas la fiche
 * gardée a STRICTEMENT plus de clics que celle archivée, indépendamment de laquelle est la plus
 * ancienne) : la fiche avec le plus de clics cumulés (`clicks_count`) devient canonique. Mesuré le
 * 2026-08-31, chiffre reproductible par la requête SQL de ce fichier : "Mistral" (#875,
 * clicks_count=447, outbound_clicks_count=24) l'emporte sur "Mistral Le Chat" (#23,
 * clicks_count=368, outbound_clicks_count=14).
 *
 * ÉCART PAR RAPPORT AU PRÉCÉDENT JASPER (2026_07_24_120100), volontaire : celui-ci ne copiait
 * aucune donnée de la fiche archivée vers la fiche canonique. Ici, la fiche canonique #875 n'a
 * AUCUNE catégorie assignée (table directory_category_tool vide pour cet id) alors que la fiche
 * archivée #23 est assignée à "Assistants IA" - sans copie, la fusion ferait RÉGRESSER la fiche
 * survivante : elle disparaîtrait du parcours par catégorie où "Mistral Le Chat" était visible
 * avant fusion. La copie est strictement additive (insertOrIgnore() du query builder Laravel sur
 * la clé composite directory_category_id+directory_tool_id, portable MySQL/SQLite) : jamais de
 * suppression, jamais d'écrasement d'une catégorie déjà présente sur la fiche canonique.
 *
 * Aucune ligne supprimée (zéro perte de données, données de la fiche archivée intactes en base).
 * Ne fait rien si moins de 2 fiches correspondent (ex. environnement local sans les données de
 * production) - idempotent, skip si déjà fusionné.
 */
return new class extends Migration
{
    private const MARKER = '[migration 2026_08_31_090000_merge_mistral_duplicate_tools]';

    public function up(): void
    {
        $candidates = DB::table('directory_tools')
            ->where('url', 'like', '%chat.mistral.ai%')
            ->where('name', 'like', '%Mistral%')
            ->orderByDesc('clicks_count')
            ->get(['id', 'clicks_count', 'lifecycle_status', 'lifecycle_replacement_tool_id']);

        if ($candidates->count() < 2) {
            return;
        }

        $canonical = $candidates->first();

        foreach ($candidates->skip(1) as $duplicate) {
            if ($duplicate->lifecycle_status === 'archived' && $duplicate->lifecycle_replacement_tool_id) {
                continue; // déjà fusionné (idempotence)
            }

            DB::table('directory_tools')->where('id', $duplicate->id)->update([
                'lifecycle_status' => 'archived',
                'lifecycle_replacement_tool_id' => $canonical->id,
                'lifecycle_notes' => DB::raw("CONCAT(COALESCE(lifecycle_notes, ''), '".self::MARKER."')"),
                'updated_at' => now(),
            ]);

            // Copie additive des catégories de la fiche archivée vers la fiche canonique - voir
            // docblock "ÉCART PAR RAPPORT AU PRÉCÉDENT JASPER" ci-dessus. insertOrIgnore() (query
            // builder Laravel, PAS de SQL brut) : ne touche jamais une ligne déjà présente sur la
            // fiche canonique, ne modifie rien sur la fiche archivée. Portable MySQL/SQLite -
            // un `INSERT IGNORE` brut casse la suite de tests (sqlite :memory:, syntaxe MySQL only).
            $categoriesACopier = DB::table('directory_category_tool')
                ->where('directory_tool_id', $duplicate->id)
                ->pluck('directory_category_id');

            if ($categoriesACopier->isNotEmpty()) {
                DB::table('directory_category_tool')->insertOrIgnore(
                    $categoriesACopier->map(fn ($categoryId) => [
                        'directory_category_id' => $categoryId,
                        'directory_tool_id' => $canonical->id,
                    ])->all()
                );
            }
        }
    }

    public function down(): void
    {
        // La copie de catégories (up(), additive) n'est délibérément PAS reprise ici : elle ne
        // supprime jamais rien, et distinguer après coup une catégorie "déjà présente avant
        // fusion" d'une catégorie "copiée par cette migration" n'est pas fiable sans avoir
        // dupliqué l'état d'origine. Laisser cette association en place au rollback est sans
        // danger : c'est une donnée additive, jamais une suppression.
        DB::table('directory_tools')
            ->where('lifecycle_notes', 'like', '%'.self::MARKER.'%')
            ->update([
                'lifecycle_status' => 'active',
                'lifecycle_replacement_tool_id' => null,
                'lifecycle_notes' => DB::raw("NULLIF(REPLACE(lifecycle_notes, '".self::MARKER."', ''), '')"),
                'updated_at' => now(),
            ]);
    }
};
