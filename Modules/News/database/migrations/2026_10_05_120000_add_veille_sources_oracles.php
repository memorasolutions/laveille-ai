<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Réactive la source Radio-Canada Sciences et ajoute 19 flux RSS/Atom de veille
 * (labos, Québec/Canada, revue, analyse spécialisée, régional non-US, francophone).
 *
 * Tous les flux ont été vérifiés vivants (HTTP 200 + vrai RSS/Atom) le 2026-10-05.
 *
 * IDEMPOTENTE : chaque flux est cherché par URL exacte et n'est inséré que s'il est absent
 * (une source déjà présente n'est jamais modifiée, son statut actif/inactif reste intact).
 * La réactivation de Radio-Canada est un simple UPDATE rejouable.
 *
 * RÉVERSIBLE : down() supprime les URLs ajoutées et remet Radio-Canada à active = 0.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    private const RADIO_CANADA_SCIENCES = 'https://ici.radio-canada.ca/rss/4159';

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
            // Labos / primaire
            $s('Mistral AI', 'https://mistral.ai/rss.xml', 'official', 'en', 1, 'Mistral AI'),
            $s('NVIDIA Blog', 'https://blogs.nvidia.com/feed/', 'official', 'en', 1, 'NVIDIA'),
            $s('Google Research', 'https://research.google/blog/rss/', 'official', 'en', 1, 'Google Research'),
            $s('Apple Machine Learning', 'https://machinelearning.apple.com/rss.xml', 'official', 'en', 1, 'Apple Machine Learning'),
            $s('Microsoft Research', 'https://www.microsoft.com/en-us/research/feed/', 'official', 'en', 1, 'Microsoft Research'),
            $s('AWS Machine Learning', 'https://aws.amazon.com/blogs/machine-learning/feed/', 'official', 'en', 1, 'AWS Machine Learning'),
            // Québec / Canada
            $s('IVADO', 'https://ivado.ca/feed/', 'official', 'fr', 1),
            $s('Vector Institute', 'https://vectorinstitute.ai/feed/', 'official', 'en', 1),
            // Recherche / revue
            $s('Nature Machine Intelligence', 'https://www.nature.com/natmachintell.rss', 'official', 'en', 1),
            // Analyse spécialisée
            $s('SemiAnalysis', 'https://www.semianalysis.com/feed', 'analysis', 'en', 0),
            $s('Import AI', 'https://importai.substack.com/feed', 'analysis', 'en', 0),
            $s('Interconnects', 'https://www.interconnects.ai/feed', 'analysis', 'en', 0),
            $s('Simon Willison', 'https://simonwillison.net/atom/everything/', 'analysis', 'en', 0),
            $s('Latent Space', 'https://www.latent.space/feed', 'analysis', 'en', 0),
            // Régional non-US
            $s('Sifted', 'https://sifted.eu/feed', 'general', 'en', 0),
            $s('ITmedia AI+', 'https://rss.itmedia.co.jp/rss/2.0/aiplus.xml', 'general', 'ja', 0),
            $s('Pandaily', 'https://pandaily.com/feed/', 'general', 'en', 0),
            $s('Rest of World', 'https://restofworld.org/feed/latest/', 'general', 'en', 0),
            // Francophone
            $s('ActuIA', 'https://www.actuia.com/feed/', 'general', 'fr', 0),
        ];
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $reactivees = DB::table('news_sources')
            ->where('url', self::RADIO_CANADA_SCIENCES)
            ->update(['active' => 1, 'updated_at' => now()]);
        echo "[actus] Radio-Canada Sciences réactivée : {$reactivees} ligne(s)\n";

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

        DB::table('news_sources')
            ->where('url', self::RADIO_CANADA_SCIENCES)
            ->update(['active' => 0, 'updated_at' => now()]);
    }
};
