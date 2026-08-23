<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\NewsSource;

/**
 * Désactive la source d'actualités « Les numériques » (demande du fondateur, 2026-08-23).
 *
 * DÉSACTIVATION, JAMAIS SUPPRESSION. Mettre `active` à false suffit à retirer la source du
 * flux de collecte (`NewsSource::scopeActive`), et laisse intactes les actualités déjà
 * collectées, qui portent une clé étrangère vers cette ligne. Supprimer la source les
 * orphelinerait sans rien gagner - et la règle du projet est de ne jamais détruire ce qu'on
 * peut simplement éteindre.
 *
 * CORRESPONDANCE EXACTE, jamais un motif large : `LIKE '%numérique%'` attraperait des sources
 * légitimes dont le nom contient le mot. On vise les graphies réellement possibles de ce nom
 * précis, accents et casse compris, et rien d'autre. La migration ÉNUMÈRE ce qu'elle a trouvé
 * dans sa sortie, pour que le journal de déploiement montre exactement ce qui a changé.
 *
 * IDEMPOTENTE : rejouer la migration ne fait rien si la source est déjà éteinte ou absente
 * (la base locale n'a que deux sources, la production en a beaucoup plus).
 *
 * RÉVERSIBLE : down() réactive UNIQUEMENT les lignes que up() a effectivement éteintes.
 */
return new class extends Migration
{
    /** Graphies visées, comparées en minuscules. */
    private const NOMS = [
        'les numériques',
        'les numeriques',
        'lesnumeriques',
        'lesnumeriques.com',
    ];

    /** @return \Illuminate\Support\Collection<int, NewsSource> */
    private function sourcesVisees(bool $actives)
    {
        return NewsSource::query()
            ->where('active', $actives)
            ->get()
            ->filter(fn (NewsSource $s) => in_array(mb_strtolower(trim((string) $s->name)), self::NOMS, true));
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! class_exists(NewsSource::class)) {
            return;
        }

        $sources = $this->sourcesVisees(true);

        if ($sources->isEmpty()) {
            echo "[actus] aucune source « Les numériques » active à désactiver\n";

            return;
        }

        foreach ($sources as $source) {
            $source->active = false;
            $source->save();
            echo "[actus] source désactivée : #{$source->id} « {$source->name} » ({$source->url})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(NewsSource::class)) {
            return;
        }

        foreach ($this->sourcesVisees(false) as $source) {
            $source->active = true;
            $source->save();
            echo "[actus] source réactivée : #{$source->id} « {$source->name} »\n";
        }
    }
};
