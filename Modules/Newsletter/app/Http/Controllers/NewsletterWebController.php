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

        return $this->renderWebFromEmail($issue);
    }

    public function latest(): View
    {
        $issue = NewsletterIssue::published()->firstOrFail();

        return $this->renderWebFromEmail($issue);
    }

    public function welcome(): View
    {
        // Cherche l'issue welcome sauvegardée en DB
        $issue = NewsletterIssue::whereJsonContains('content->is_welcome', true)
            ->whereNotNull('sent_at')
            ->latest()
            ->first();

        if ($issue) {
            return $this->renderWebFromEmail($issue, true);
        }

        // Fallback preview avant premier envoi
        $data = DigestContentService::gatherFreshContent();
        $data['subject'] = 'Bienvenue sur La veille IA';
        $data['isWelcome'] = true;
        $data['unsubscribeUrl'] = '#';

        $emailHtml = view('newsletter::emails.digest-weekly', $data)->render();

        return view('newsletter::web.embed', [
            'emailHtml' => $emailHtml,
            'subject' => $data['subject'],
            'issue' => (object) ['subject' => $data['subject'], 'sent_at' => now(), 'week_number' => now()->weekOfYear],
        ]);
    }

    private function renderWebFromEmail(NewsletterIssue $issue, bool $isWelcome = false): View
    {
        $data = DigestContentService::gatherFromIssue($issue);
        $data['subject'] = $issue->subject;
        $data['issue'] = $issue;
        $data['unsubscribeUrl'] = '#';
        $data['isWelcome'] = $isWelcome || ($issue->content['is_welcome'] ?? false);

        // Rendre le template email et l'injecter dans le layout web
        $emailHtml = view('newsletter::emails.digest-weekly', $data)->render();

        return view('newsletter::web.embed', [
            'emailHtml' => $emailHtml,
            'subject' => $issue->subject,
            'issue' => $issue,
        ]);
    }

    public function archive(): View
    {
        $issues = NewsletterIssue::published()->paginate(12);

        return view('newsletter::web.archive', compact('issues'));
    }
}
