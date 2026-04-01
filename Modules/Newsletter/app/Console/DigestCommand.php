<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\DigestNotification;
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

        // Articles blog de la semaine
        $articles = collect();
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $articles = \Modules\Blog\Models\Article::published()
                ->where('published_at', '>=', now()->subDays(7))
                ->latest('published_at')
                ->get();
        }

        // Actualites IA de la semaine
        $newsArticles = collect();
        if (class_exists(\Modules\News\Models\NewsArticle::class)) {
            $newsArticles = \Modules\News\Models\NewsArticle::where('is_published', true)
                ->where('pub_date', '>=', now()->subDays(7))
                ->where('relevance_score', '>=', 8)
                ->latest('pub_date')
                ->take(5)
                ->get();
        }

        $articles = $articles->merge($newsArticles);

        if ($articles->isEmpty()) {
            $this->components->info('No new articles published in the last 7 days.');

            return self::SUCCESS;
        }

        $subscribers = Subscriber::active()->get();

        if ($subscribers->isEmpty()) {
            $this->components->info('No active subscribers found.');

            return self::SUCCESS;
        }

        foreach ($subscribers as $subscriber) {
            $subscriber->notify(new DigestNotification($articles));
        }

        $this->newLine();
        $this->components->twoColumnDetail('Articles', (string) $articles->count());
        $this->components->twoColumnDetail('Subscribers notified', (string) $subscribers->count());
        $this->components->info('Digest sent successfully.');

        return self::SUCCESS;
    }
}
