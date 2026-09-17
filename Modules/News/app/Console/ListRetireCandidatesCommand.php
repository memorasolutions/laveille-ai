<?php

declare(strict_types=1);

namespace Modules\News\Console;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION : chantier retrait AdSense « faible valeur » (2026-09-17) - identifie les fiches
 *          CANDIDATES au retrait 410 (RetireArticlesCommand) : publiées, non déjà retirées,
 *          plus anciennes qu'une date donnée. LECTURE SEULE STRICTE - aucune écriture, aucun
 *          impact sur aucune donnée. Sert uniquement à construire, une fois croisé avec le
 *          trafic RÉEL (Search Console), le fichier JSON {"ids":[...]} exigé par l'option
 *          --ids-file de RetireArticlesCommand - jamais un mécanisme de retrait parallèle.
 *
 * MCP: SELF (<5 lignes utiles)
 * RAISON: aucune commande existante n'exporte id+slug+pub_date filtré par date ; nécessaire
 *         pour établir la liste sur des faits plutôt qu'à l'aveugle, sans jamais toucher au
 *         mécanisme de retrait lui-même (RetireArticlesCommand reste l'unique porte d'écriture).
 */

use Illuminate\Console\Command;
use Modules\News\Models\NewsArticle;

class ListRetireCandidatesCommand extends Command
{
    protected $signature = 'news:list-retire-candidates
        {--before= : Date ISO (AAAA-MM-JJ) - ne liste que les fiches publiées avant cette date}
        {--seo-status= : Filtre optionnel sur seo_status (index|noindex|gone) - vide = tous}';

    protected $description = 'Liste (JSON, LECTURE SEULE) les fiches publiées/non retirées plus anciennes que --before : id, slug, pub_date, title, seo_status';

    public function handle(): int
    {
        $before = (string) $this->option('before');
        if ($before === '') {
            $this->error('L\'option --before est obligatoire (AAAA-MM-JJ).');

            return self::FAILURE;
        }

        $seoStatus = (string) $this->option('seo-status');

        $articles = NewsArticle::query()
            ->where('is_published', true)
            ->whereNull('retired_at')
            ->where('pub_date', '<', $before)
            ->when($seoStatus !== '', fn ($q) => $q->where('seo_status', $seoStatus))
            ->orderBy('id')
            ->get(['id', 'slug', 'pub_date', 'title', 'seo_status']);

        $this->line($articles->map(fn (NewsArticle $a) => [
            'id' => $a->id,
            'slug' => $a->slug,
            'pub_date' => $a->pub_date?->toDateString(),
            'title' => $a->title,
            'seo_status' => $a->seo_status,
        ])->values()->toJson());

        return self::SUCCESS;
    }
}
