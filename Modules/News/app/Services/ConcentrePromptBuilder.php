<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\News\Models\NewsArticle;

/**
 * S90 — Génère le prompt Claude Code CLI pour la rédaction du concentré IA hebdo.
 *
 * Source du template : mail "Rappel concentré IA hebdo" envoyé chaque lundi 7h25
 * (cf gmail message 19e16c922194748c, 11 mai 2026). L'outil frontend remplace ce
 * mail par un formulaire interactif (sélection + drag-drop + URLs manuelles).
 */
class ConcentrePromptBuilder
{
    /**
     * @param  int[]  $orderedNewsIds  IDs des news dans l'ordre choisi par l'admin (drag-drop)
     * @param  string[]  $manualUrls  URLs additionnelles, 1 par ligne (ordre préservé)
     */
    public function build(
        CarbonInterface $weekStart,
        CarbonInterface $weekEnd,
        array $orderedNewsIds,
        array $manualUrls
    ): string {
        if ($weekStart->dayOfWeek !== CarbonInterface::MONDAY) {
            throw new InvalidArgumentException('weekStart doit être un lundi.');
        }

        if ($weekEnd->dayOfWeek !== CarbonInterface::SUNDAY) {
            throw new InvalidArgumentException('weekEnd doit être un dimanche.');
        }

        $periodFr = $this->formatPeriodFr($weekStart, $weekEnd);
        $slugPeriod = $this->slugPeriod($weekStart, $weekEnd);
        $urlsBlock = $this->buildUrlsBlock($orderedNewsIds, $manualUrls);

        return view('news::admin._concentre_prompt_template', [
            'periodFr' => $periodFr,
            'slugPeriod' => $slugPeriod,
            'urlsBlock' => $urlsBlock,
        ])->render();
    }

    private function buildUrlsBlock(array $orderedNewsIds, array $manualUrls): string
    {
        $lines = [];

        if (! empty($orderedNewsIds)) {
            $news = NewsArticle::query()
                ->whereIn('id', $orderedNewsIds)
                ->get()
                ->keyBy('id');

            foreach ($orderedNewsIds as $id) {
                $article = $news->get((int) $id);
                if ($article) {
                    $lines[] = url('/actualites/' . $article->slug);
                }
            }
        }

        foreach ($manualUrls as $url) {
            $url = trim((string) $url);
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $lines[] = $url;
            }
        }

        return implode("\n", $lines);
    }

    private function formatPeriodFr(CarbonInterface $start, CarbonInterface $end): string
    {
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];

        $startMonth = $months[$start->month];
        $endMonth = $months[$end->month];
        $year = $end->year;

        if ($start->month === $end->month) {
            return $start->day . ' au ' . $end->day . ' ' . $endMonth . ' ' . $year;
        }

        return $start->day . ' ' . $startMonth . ' au ' . $end->day . ' ' . $endMonth . ' ' . $year;
    }

    private function slugPeriod(CarbonInterface $start, CarbonInterface $end): string
    {
        $months = [
            1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre',
        ];

        $endMonth = $months[$end->month];

        return 'concentre-ia-semaine-' . $start->day . '-' . $end->day . '-' . $endMonth . '-' . $end->year;
    }

    public function listNewsForWeek(CarbonInterface $weekStart, CarbonInterface $weekEnd): Collection
    {
        return NewsArticle::query()
            ->where('is_published', true)
            ->whereBetween('pub_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->with('newsSource:id,name,url')
            ->orderByDesc('pub_date')
            ->get(['id', 'news_source_id', 'title', 'slug', 'url', 'summary', 'pub_date', 'category_tag', 'image_url']);
    }
}
