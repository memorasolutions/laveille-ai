<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\CampaignNotification;
use Modules\Newsletter\States\SentCampaignState;

class CampaignController extends Controller
{
    public function index(): View
    {
        return view('newsletter::admin.campaigns.index');
    }

    public function create(): View
    {
        return view('newsletter::admin.campaigns.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|max:255',
            'content' => 'required',
        ]);

        Campaign::create([
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'status' => 'draft',
        ]);

        return redirect()
            ->route('admin.newsletter.campaigns.index')
            ->with('success', 'Campagne créée.');
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        if ($campaign->isSent()) {
            return redirect()
                ->back()
                ->with('error', 'Campagne déjà envoyée.');
        }

        $subscribers = Subscriber::active()->get();

        DB::transaction(function () use ($campaign, $subscribers) {
            $campaign->status->transitionTo(SentCampaignState::class);
            $campaign->update([
                'sent_at' => now(),
                'recipient_count' => $subscribers->count(),
            ]);
        });

        foreach ($subscribers as $subscriber) {
            Notification::send($subscriber, new CampaignNotification($campaign, $subscriber));
        }

        return redirect()
            ->route('admin.newsletter.campaigns.index')
            ->with('success', "Campagne envoyée à {$subscribers->count()} abonnés.");
    }
}
