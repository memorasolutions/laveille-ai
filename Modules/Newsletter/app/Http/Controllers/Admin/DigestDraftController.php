<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Newsletter\Models\NewsletterIssue;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WeeklyDigestNotification;
use Modules\Newsletter\Services\DigestContentService;

class DigestDraftController extends Controller
{
    public function edit()
    {
        $issue = NewsletterIssue::whereIn('status', ['draft', 'ready'])
            ->orderByDesc('id')
            ->firstOrFail();

        $data = DigestContentService::gatherFromIssue($issue);

        return view('newsletter::admin.digest-draft-edit', compact('issue', 'data'));
    }

    public function update(Request $request, NewsletterIssue $issue)
    {
        $validated = $request->validate([
            'editorial_edited' => 'nullable|string|max:500',
            'subject' => 'required|string|max:255',
        ]);

        $issue->update(array_merge($validated, ['edited_at' => now()]));

        return redirect()->back()->with('success', 'Brouillon mis à jour.');
    }

    public function preview(NewsletterIssue $issue)
    {
        $data = DigestContentService::gatherFromIssue($issue);

        if ($issue->editorial_edited) {
            $data['editorial'] = $issue->editorial_edited;
        }

        $data['subject'] = $issue->subject;
        $data['unsubscribeUrl'] = '#';

        return view('newsletter::emails.digest-weekly', $data);
    }

    public function sendNow(NewsletterIssue $issue)
    {
        $data = DigestContentService::gatherFromIssue($issue);

        if ($issue->editorial_edited) {
            $data['editorial'] = $issue->editorial_edited;
        }

        $subscribers = Subscriber::active()->get();

        foreach ($subscribers as $subscriber) {
            $subscriber->notify(new WeeklyDigestNotification(
                $data['highlight'], $data['topNews'], $data['toolOfWeek'] ?? null,
                $data['featuredArticle'] ?? null, null, $data['weekNumber'],
                $data['aiTerm'] ?? null, $data['interactiveTool'] ?? null,
                $data['weeklyPrompt'] ?? null, $data['editorial'] ?? null
            ));
        }

        $issue->update([
            'status' => 'sent',
            'sent_at' => now(),
            'subscriber_count' => $subscribers->count(),
        ]);

        return redirect()->back()->with('success', 'Newsletter envoyée à '.$subscribers->count().' abonnés.');
    }
}
