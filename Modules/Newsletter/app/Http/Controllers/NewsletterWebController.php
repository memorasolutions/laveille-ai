<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Newsletter\Models\NewsletterIssue;

class NewsletterWebController extends Controller
{
    public function show(int $year, int $week): View
    {
        $issue = NewsletterIssue::where('year', $year)
            ->where('week_number', $week)
            ->whereNotNull('sent_at')
            ->firstOrFail();

        $data = $this->buildContentFromIssue($issue);

        return view('newsletter::web.show', $data);
    }

    public function latest(): View
    {
        $issue = NewsletterIssue::published()->firstOrFail();
        $data = $this->buildContentFromIssue($issue);

        return view('newsletter::web.show', $data);
    }

    public function archive(): View
    {
        $issues = NewsletterIssue::published()->paginate(12);

        return view('newsletter::web.archive', compact('issues'));
    }

    private function buildContentFromIssue(NewsletterIssue $issue): array
    {
        $content = $issue->content ?? [];

        $highlight = null;
        $topNews = collect();
        $toolOfWeek = null;
        $featuredArticle = null;
        $aiTerm = null;
        $interactiveTool = null;

        if (($content['highlight_id'] ?? null) && class_exists(\Modules\News\Models\NewsArticle::class)) {
            $highlight = \Modules\News\Models\NewsArticle::find($content['highlight_id']);
        }

        if (! empty($content['top_news_ids']) && class_exists(\Modules\News\Models\NewsArticle::class)) {
            $topNews = \Modules\News\Models\NewsArticle::whereIn('id', $content['top_news_ids'])->get();
        }

        if (($content['tool_id'] ?? null) && class_exists(\Modules\Directory\Models\Tool::class)) {
            $toolOfWeek = \Modules\Directory\Models\Tool::find($content['tool_id']);
        }

        if (($content['article_id'] ?? null) && class_exists(\Modules\Blog\Models\Article::class)) {
            $featuredArticle = \Modules\Blog\Models\Article::find($content['article_id']);
        }

        if (($content['term_id'] ?? null) && class_exists(\Modules\Dictionary\Models\Term::class)) {
            $aiTerm = \Modules\Dictionary\Models\Term::find($content['term_id']);
        }

        if (($content['interactive_tool_id'] ?? null) && class_exists(\Modules\Tools\Models\Tool::class)) {
            $interactiveTool = \Modules\Tools\Models\Tool::find($content['interactive_tool_id']);
        }

        return [
            'issue' => $issue,
            'subject' => $issue->subject,
            'highlight' => $highlight,
            'topNews' => $topNews,
            'toolOfWeek' => $toolOfWeek,
            'featuredArticle' => $featuredArticle,
            'aiTerm' => $aiTerm,
            'interactiveTool' => $interactiveTool,
            'weeklyPrompt' => $content['weekly_prompt'] ?? null,
            'weekNumber' => $issue->week_number,
            'unsubscribeUrl' => '#',
        ];
    }
}
