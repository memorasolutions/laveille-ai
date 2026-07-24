<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Fusion du doublon "Jasper AI" / "Jasper" trouvé pendant la recherche affiliation 2026-07-24
 * (les deux fiches pointent vers jasper.ai — issues de deux seeders différents :
 * DirectoryLotAWritingSeeder et MissingPopularToolsSeeder). Conserve la fiche avec le plus de
 * clics cumulés comme canonique, archive l'autre en réutilisant le mécanisme déjà en place
 * (`lifecycle_status=archived` + `lifecycle_replacement_tool_id`), qui déclenche déjà une
 * redirection 301 automatique vers la fiche canonique dans
 * PublicDirectoryController::show() (ligne ~163). Aucune ligne supprimée (zéro perte de
 * données). Ne fait rien si moins de 2 fiches correspondent (ex. environnement local sans les
 * données de production).
 */
return new class extends Migration
{
    private const MARKER = '[migration 2026_07_24_120100_merge_jasper_duplicate_tools]';

    public function up(): void
    {
        $candidates = DB::table('directory_tools')
            ->where('url', 'like', '%jasper.ai%')
            ->where('name', 'like', '%Jasper%')
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
        }
    }

    public function down(): void
    {
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
