<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ferme en BASE la classe de défaut mesurée au ticket #2436 : quatre voies d'insertion
 * différentes alimentent `directory_resources`, avec trois clés de dédoublonnage distinctes,
 * et AUCUNE contrainte ne garantissait qu'un même couple (outil, vidéo) n'entre qu'une fois.
 * Le correctif applicatif de #2436 ferme les voies connues ; cet index ferme aussi celles
 * qu'on n'a pas encore écrites.
 *
 * MESURE DU 2026-09-12, en production, AVANT de poser l'index :
 *   - 1831 lignes, dont 579 SANS video_id - toutes NULL, ZÉRO chaîne vide. C'est le point
 *     décisif : MySQL autorise plusieurs NULL dans un index UNIQUE mais refuse plusieurs
 *     chaînes vides, et 143 outils portent plusieurs ressources sans vidéo. Une seule ligne
 *     à video_id = '' aurait fait échouer la migration en production.
 *   - 2 couples en double (outil 23, vidéos rGlEuUOSdS4 et gN_cV6TT5ow), doublons STRICTS :
 *     même URL, même titre, même created_at à la seconde. Retirés à la main le 2026-09-12
 *     après sauvegarde des 4 lignes (#2465).
 *
 * POURQUOI up() NETTOIE AVANT DE POSER L'INDEX : une migration qui échoue casse le
 * déploiement entier. Si un doublon réapparaissait entre l'écriture de ce fichier et son
 * exécution, la pose de l'index échouerait. Le nettoyage garde TOUJOURS la ligne la plus
 * ANCIENNE (MIN(id)) - déterministe - et journalise ce qu'il retire avant de le retirer,
 * pour qu'aucune suppression ne soit silencieuse. Sur un couple (outil, vidéo) identique,
 * les lignes surnuméraires ne portent par construction aucune information propre.
 */
return new class extends Migration
{
    private const TABLE = 'directory_resources';

    private const INDEX = 'directory_resources_tool_video_unique';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $surnumeraires = DB::table(self::TABLE.' as d')
            ->select('d.id', 'd.directory_tool_id', 'd.video_id', 'd.url')
            ->whereNotNull('d.video_id')
            ->where('d.video_id', '<>', '')
            ->whereRaw('d.id > (SELECT MIN(m.id) FROM '.self::TABLE.' m
                WHERE m.directory_tool_id = d.directory_tool_id AND m.video_id = d.video_id)')
            ->get();

        if ($surnumeraires->isNotEmpty()) {
            Log::warning('[directory_resources] doublons (outil, vidéo) retirés avant la pose de l\'index unique', [
                'nombre' => $surnumeraires->count(),
                'lignes' => $surnumeraires->map(fn ($r) => [
                    'id' => $r->id,
                    'directory_tool_id' => $r->directory_tool_id,
                    'video_id' => $r->video_id,
                    'url' => $r->url,
                ])->all(),
            ]);

            DB::table(self::TABLE)->whereIn('id', $surnumeraires->pluck('id'))->delete();
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unique(['directory_tool_id', 'video_id'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique(self::INDEX);
        });
    }
};
