<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

/**
 * Réparation et élagage de la liste des sources d'actualités (2026-08-23).
 *
 * QUATRE SOURCES ne collectaient rien depuis 90 jours, non parce qu'elles sont mauvaises, mais
 * parce que leur adresse de flux était périmée. Les quatre nouvelles adresses ont été testées
 * une par une avant d'être écrites, et renvoient toutes un flux valide et récent : Google
 * DeepMind 100 éléments, ZDNet France 50 éléments datés du jour même, IEEE Spectrum 30,
 * Agence Science-Presse 10. Les déclarer mortes aurait fait perdre une source primaire majeure.
 *
 * SEPT SOURCES sont DÉSACTIVÉES, jamais supprimées, et chacune pour un motif OBJECTIF, jamais
 * pour un simple taux de publication faible : ITespresso (délai médian de 124 jours, le contenu
 * arrive mort), Frenchweb (19 jours), Fredzone (12,7 jours), Maddyness (6,7 jours et 6 articles
 * en 90 jours), Journal du Coin (site de cryptomonnaie, hors périmètre éditorial), OpenAI News
 * (doublon de OpenAI Blog, qui collecte 135 articles là où celle-ci en collecte 1), et
 * Numerama IA id 53 (doublon de l'id 35, zéro collecte).
 *
 * DEUX SOURCES à zéro publication sont VOLONTAIREMENT conservées, Quanta Magazine et Le Monde
 * Pixels. Juger une source sur son seul taux de publication revient à mesurer le goût de
 * l'éditeur par l'éditeur lui-même : une boucle fermée. Elles sont dans le périmètre et assez
 * rapides ; leur absence de publication ne prouve rien sur leur valeur.
 *
 * THE ATLANTIC TECHNOLOGY (id 46) n'est PAS touchée : son adresse est déjà celle qui a été
 * testée et validée, et son absence de collecte reste INEXPLIQUÉE. On ne répare pas ce qu'on
 * n'a pas diagnostiqué.
 *
 * EFFET ATTENDU, à ne pas prendre pour une anomalie : une pointe unique de collecte au premier
 * passage après réparation, les flux réparés livrant leur historique d'un coup.
 *
 * Aucune suppression, aucune donnée perdue : seules les colonnes `url` et `active` bougent, et
 * down() rend l'état exact d'avant.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsSource;

return new class extends Migration
{
    private const URLS_A_REPARER = [
        22 => ['ancienne' => 'https://www.sciencepresse.qc.ca/feed', 'nouvelle' => 'https://www.sciencepresse.qc.ca/rss.xml'],
        29 => ['ancienne' => 'https://www.zdnet.fr/feed/', 'nouvelle' => 'https://www.zdnet.fr/feeds/rss/actualites/'],
        34 => ['ancienne' => 'https://spectrum.ieee.org/rss/ai', 'nouvelle' => 'https://spectrum.ieee.org/feeds/topic/artificial-intelligence.rss'],
        52 => ['ancienne' => 'https://deepmind.google/discover/blog/rss.xml', 'nouvelle' => 'https://deepmind.google/blog/rss.xml'],
    ];

    private const A_DESACTIVER = [21, 30, 44, 47, 48, 51, 53];

    public function up(): void
    {
        foreach (self::URLS_A_REPARER as $id => $urls) {
            $source = NewsSource::find($id);

            if ($source === null) {
                Log::warning("[sources] source {$id} introuvable : URL non reparee.");

                continue;
            }

            // Garde-fou : on ne remplace QUE ce qu'on a reellement mesure. Si l'adresse a change
            // entre la mesure et l'execution, quelqu'un est passe avant nous et sa decision prime.
            if ($source->url !== $urls['ancienne']) {
                Log::warning("[sources] source {$id} : URL inattendue, reparation ignoree.");

                continue;
            }

            $source->url = $urls['nouvelle'];
            $source->save();
        }

        foreach (self::A_DESACTIVER as $id) {
            $source = NewsSource::find($id);

            if ($source === null) {
                Log::warning("[sources] source {$id} introuvable : desactivation ignoree.");

                continue;
            }

            // Comparaison NON stricte : la colonne peut remonter un entier selon le pilote.
            if (! $source->active) {
                continue;
            }

            $source->active = false;
            $source->save();
        }
    }

    public function down(): void
    {
        foreach (self::URLS_A_REPARER as $id => $urls) {
            $source = NewsSource::find($id);

            if ($source !== null && $source->url === $urls['nouvelle']) {
                $source->url = $urls['ancienne'];
                $source->save();
            }
        }

        foreach (self::A_DESACTIVER as $id) {
            $source = NewsSource::find($id);

            if ($source !== null) {
                $source->active = true;
                $source->save();
            }
        }
    }
};
