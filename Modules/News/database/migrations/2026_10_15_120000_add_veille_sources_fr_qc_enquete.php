<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute 3 flux RSS/Atom de veille, trous de couverture relevés par le club des sages
 * (audit #3126, 2026-10-15) : un média techno francophone québécois mainstream, et deux
 * contrepoids journalistiques (enquête / affaires-techno canadien).
 *
 * Tous les flux ont été vérifiés vivants le 2026-10-15 (HTTP 200 + vrai RSS/Atom, items > 0)
 * par un contrôle déterministe au curl, avant insertion (zéro-casse, leçon de la migration
 * du 2026-10-05 : ne jamais ajouter une source dont on n'a pas prouvé qu'elle s'ingère).
 *
 * IDEMPOTENTE : chaque flux est cherché par URL exacte et n'est inséré que s'il est absent
 * (une source déjà présente n'est jamais modifiée). RÉVERSIBLE : down() supprime les URLs ajoutées.
 *
 * Les sources SANS RSS exploitable (Chine en direct, sécurité/agents, gouvernemental québécois,
 * Hugging Face Hub, GitHub releases) restent en VÉRIFICATION CIBLÉE dans le skill /actu2, pas en
 * ingestion continue, pour ne pas noyer la file de composition.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    /** @return list<array{name:string,url:string,category:string,language:string,is_official:int,company:?string}> */
    private function sources(): array
    {
        $s = static fn (string $name, string $url, string $category, string $language, int $official, ?string $company = null): array => [
            'name' => $name,
            'url' => $url,
            'category' => $category,
            'language' => $language,
            'is_official' => $official,
            'company' => $company,
        ];

        return [
            // Techno francophone québécoise mainstream (trou évident pour un public QC)
            $s('La Presse techno', 'https://www.lapresse.ca/affaires/techno/rss', 'general', 'fr', 0),
            // Contrepoids journalistiques / enquête
            $s('404 Media', 'https://www.404media.co/rss/', 'general', 'en', 0),
            $s('The Logic', 'https://thelogic.co/feed/', 'general', 'en', 0),
        ];
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $ajoutees = 0;
        foreach ($this->sources() as $source) {
            if (DB::table('news_sources')->where('url', $source['url'])->exists()) {
                echo "[actus] déjà présente, inchangée : {$source['name']}\n";

                continue;
            }

            DB::table('news_sources')->insert($source + [
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ajoutees++;
            echo "[actus] source ajoutée : {$source['name']}\n";
        }
        echo "[actus] {$ajoutees} source(s) ajoutée(s)\n";
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('news_sources')
            ->whereIn('url', array_column($this->sources(), 'url'))
            ->delete();
    }
};
