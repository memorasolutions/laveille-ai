<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WeeklyDigestNotification;
use Modules\Newsletter\Services\DigestContentService;
use Modules\Settings\Models\Setting;

class DigestCommand extends Command
{
    protected $signature = 'newsletter:digest {--force : Send even if digest is disabled in settings}';

    protected $description = 'Send weekly digest of new articles to active subscribers';

    public function handle(): int
    {
        if (! Setting::get('newsletter.digest_enabled', false) && ! $this->option('force')) {
            $this->components->info('Digest is disabled in settings. Use --force to send anyway.');

            return self::SUCCESS;
        }

        // Collecte du contenu via le service centralise
        $data = DigestContentService::gatherFreshContent();

        if (! $data['highlight'] && $data['topNews']->isEmpty()) {
            $this->components->info('No news articles this week. Skipping digest.');

            return self::SUCCESS;
        }

        $subscribers = Subscriber::active()->get();

        if ($subscribers->isEmpty()) {
            $this->components->info('No active subscribers found.');

            return self::SUCCESS;
        }

        foreach ($subscribers as $subscriber) {
            $subscriber->notify(new WeeklyDigestNotification(
                $data['highlight'],
                $data['topNews'],
                $data['toolOfWeek'],
                $data['featuredArticle'],
                null,
                $data['weekNumber'],
                $data['aiTerm'],
                $data['interactiveTool'],
                $data['weeklyPrompt']
            ));
        }

        // Sauvegarder le numero pour l'archivage web
        if (class_exists(\Modules\Newsletter\Models\NewsletterIssue::class) && Schema::hasTable('newsletter_issues')) {
            \Modules\Newsletter\Models\NewsletterIssue::updateOrCreate(
                ['year' => (int) now()->year, 'week_number' => $data['weekNumber']],
                [
                    'subject' => 'Veille hebdo #'.$data['weekNumber'].' - '.config('app.name'),
                    'content' => [
                        'highlight_id' => $data['highlight']?->id,
                        'top_news_ids' => $data['topNews']->pluck('id')->toArray(),
                        'tool_id' => $data['toolOfWeek']?->id,
                        'article_id' => $data['featuredArticle']?->id,
                        'term_id' => $data['aiTerm']?->id,
                        'interactive_tool_id' => $data['interactiveTool']?->id,
                        'weekly_prompt' => $data['weeklyPrompt'],
                    ],
                    'subscriber_count' => $subscribers->count(),
                    'sent_at' => now(),
                ]
            );
        }

        $this->newLine();
        $this->components->twoColumnDetail('Highlight', $data['highlight']?->title ?? 'none');
        $this->components->twoColumnDetail('Top news', (string) $data['topNews']->count());
        $this->components->twoColumnDetail('Tool of week', $data['toolOfWeek']?->name ?? 'none');
        $this->components->twoColumnDetail('AI term', $data['aiTerm']?->name ?? 'none');
        $this->components->twoColumnDetail('Subscribers', (string) $subscribers->count());
        $this->components->info('Weekly digest #'.$data['weekNumber'].' sent successfully.');

        return self::SUCCESS;
    }
}
