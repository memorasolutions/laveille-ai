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
use Illuminate\View\View;
use Modules\Newsletter\Jobs\SendCampaignEmailJob;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Services\BrevoService;
use Modules\Newsletter\States\SentCampaignState;

class CampaignController extends Controller
{
    public function index(): View
    {
        return view('newsletter::admin.campaigns.index');
    }

    public function create(): View
    {
        return view('newsletter::admin.campaigns.create', [
            'templates' => BrevoService::availableTemplates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|max:255',
            'content' => 'required',
            'template' => 'required|in:'.implode(',', array_keys(BrevoService::availableTemplates())),
        ]);

        Campaign::create([
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'template' => $validated['template'],
            'status' => 'draft',
        ]);

        return redirect()
            ->route('admin.newsletter.campaigns.index')
            ->with('success', 'Campagne créée.');
    }

    public function send(Campaign $campaign, BrevoService $brevo): RedirectResponse
    {
        if ($campaign->isSent()) {
            return redirect()
                ->back()
                ->with('error', 'Campagne déjà envoyée.');
        }

        if (! $brevo->isConfigured()) {
            return redirect()
                ->back()
                ->with('error', 'Brevo API non configurée. Vérifiez BREVO_API_KEY dans .env.');
        }

        $subscribers = Subscriber::active()->get();

        if ($subscribers->isEmpty()) {
            return redirect()->back()->with('error', 'Aucun abonné actif.');
        }

        $template = $campaign->template ?? 'modern';
        $htmlContent = $brevo->renderTemplate($template, $campaign->subject, $campaign->content, '#');

        // Dispatch un job par abonné (queue async au lieu de synchrone)
        foreach ($subscribers as $subscriber) {
            SendCampaignEmailJob::dispatch(
                $subscriber->email,
                (string) ($subscriber->name ?? ''),
                $campaign->subject,
                $htmlContent,
                $campaign->id,
            );
        }

        DB::transaction(function () use ($campaign, $subscribers) {
            $campaign->status->transitionTo(SentCampaignState::class);
            $campaign->update([
                'sent_at' => now(),
                'recipient_count' => $subscribers->count(),
            ]);
        });

        return redirect()
            ->route('admin.newsletter.campaigns.index')
            ->with('success', "{$subscribers->count()} courriels mis en file d'attente pour envoi via Brevo.");
    }
}
