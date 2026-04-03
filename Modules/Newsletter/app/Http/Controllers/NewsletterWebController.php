<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NewsletterWebController extends Controller
{
    public function show(int $year, int $week)
    {
        $highlight = null;
        $topNews = collect();
        $toolOfWeek = null;
        $featuredArticle = null;
        $aiTerm = null;
        $interactiveTool = null;
        $weeklyPrompt = null;

        // Recuperer les items envoyes pour cette semaine
        $sentItems = [];
        if (Schema::hasTable('newsletter_sent_items')) {
            $sentItems = DB::table('newsletter_sent_items')
                ->where('week_number', $week)
                ->where('year', $year)
                ->pluck('item_id', 'type')
                ->toArray();
        }

        // Outil de la semaine (depuis tracking)
        if (isset($sentItems['tool']) && class_exists(\Modules\Directory\Models\Tool::class)) {
            $toolOfWeek = \Modules\Directory\Models\Tool::find($sentItems['tool']);
        }

        // Terme IA (depuis tracking)
        if (isset($sentItems['term']) && class_exists(\Modules\Dictionary\Models\Term::class)) {
            $aiTerm = \Modules\Dictionary\Models\Term::find($sentItems['term']);
        }

        // Outil interactif (depuis tracking)
        if (isset($sentItems['interactive_tool']) && class_exists(\Modules\Tools\Models\Tool::class)) {
            $interactiveTool = \Modules\Tools\Models\Tool::find($sentItems['interactive_tool']);
        }

        // Fait marquant + top news (pas trackes, requete directe)
        if (class_exists(\Modules\News\Models\NewsArticle::class)) {
            $highlight = \Modules\News\Models\NewsArticle::where('is_published', true)
                ->where('pub_date', '>=', now()->setISODate($year, $week)->startOfWeek())
                ->where('pub_date', '<=', now()->setISODate($year, $week)->endOfWeek())
                ->orderByDesc('relevance_score')
                ->first();

            $topNews = \Modules\News\Models\NewsArticle::where('is_published', true)
                ->where('pub_date', '>=', now()->setISODate($year, $week)->startOfWeek())
                ->where('pub_date', '<=', now()->setISODate($year, $week)->endOfWeek())
                ->when($highlight, fn ($q) => $q->where('id', '!=', $highlight->id))
                ->orderByDesc('relevance_score')
                ->take(5)
                ->get();
        }

        // Article blog vedette
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $featuredArticle = \Modules\Blog\Models\Article::published()
                ->latest('published_at')
                ->first();
        }

        // Prompt de la quinzaine
        if ($week % 2 === 0 && $aiTerm) {
            $ref = new \ReflectionMethod(\Modules\Newsletter\Console\DigestCommand::class, 'generateWeeklyPrompt');
            $ref->setAccessible(true);
            $weeklyPrompt = $ref->invoke(null, $aiTerm->name ?? '', $aiTerm->type ?? null);
        }

        $subject = 'Veille hebdo #'.$week.' - '.config('app.name');

        return view('newsletter::emails.digest-weekly', [
            'subject' => $subject,
            'highlight' => $highlight,
            'topNews' => $topNews,
            'toolOfWeek' => $toolOfWeek,
            'featuredArticle' => $featuredArticle,
            'aiTerm' => $aiTerm,
            'interactiveTool' => $interactiveTool,
            'weeklyPrompt' => $weeklyPrompt,
            'weekNumber' => $week,
            'unsubscribeUrl' => '#',
        ]);
    }

    public function latest()
    {
        $week = (int) now()->weekOfYear;
        $year = (int) now()->year;

        return $this->show($year, $week);
    }
}
