<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Newsletter\Models\NewsletterIssue;
use Modules\Newsletter\Services\DigestContentService;

class NewsletterWebController extends Controller
{
    public function show(int $year, int $week): View
    {
        $issue = NewsletterIssue::where('year', $year)
            ->where('week_number', $week)
            ->whereNotNull('sent_at')
            ->firstOrFail();

        $data = DigestContentService::gatherFromIssue($issue);
        $data['issue'] = $issue;
        $data['subject'] = $issue->subject;
        $data['unsubscribeUrl'] = '#';

        return view('newsletter::web.show', $data);
    }

    public function latest(): View
    {
        $issue = NewsletterIssue::published()->firstOrFail();

        $data = DigestContentService::gatherFromIssue($issue);
        $data['issue'] = $issue;
        $data['subject'] = $issue->subject;
        $data['unsubscribeUrl'] = '#';

        return view('newsletter::web.show', $data);
    }

    public function welcome(): View
    {
        $data = DigestContentService::gatherFreshContent();
        $data['isWelcome'] = true;
        $data['subject'] = 'Bienvenue sur La veille IA';
        $data['issue'] = (object) ['subject' => $data['subject'], 'sent_at' => now(), 'week_number' => now()->weekOfYear];
        $data['unsubscribeUrl'] = '#';

        return view('newsletter::web.show', $data);
    }

    public function archive(): View
    {
        $issues = NewsletterIssue::published()->paginate(12);

        return view('newsletter::web.archive', compact('issues'));
    }
}
