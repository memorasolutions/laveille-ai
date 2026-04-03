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
use Modules\Newsletter\Notifications\WeeklyDigestNotification;
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

        // Section 1 : fait marquant (top news article de la semaine)
        $highlight = null;
        $topNews = collect();
        if (class_exists(\Modules\News\Models\NewsArticle::class)) {
            $highlight = \Modules\News\Models\NewsArticle::where('is_published', true)
                ->where('pub_date', '>=', now()->subDays(7))
                ->orderByDesc('relevance_score')
                ->first();

            $topNews = \Modules\News\Models\NewsArticle::where('is_published', true)
                ->where('pub_date', '>=', now()->subDays(7))
                ->when($highlight, fn ($q) => $q->where('id', '!=', $highlight->id))
                ->orderByDesc('relevance_score')
                ->take(5)
                ->get();
        }

        // Section 3 : outil de la semaine (rotation aleatoire)
        $toolOfWeek = null;
        if (class_exists(\Modules\Directory\Models\Tool::class)) {
            $toolOfWeek = \Modules\Directory\Models\Tool::where('status', 'published')
                ->inRandomOrder()
                ->first();
        }

        // Section 4 : article blog vedette
        $featuredArticle = null;
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $featuredArticle = \Modules\Blog\Models\Article::published()
                ->latest('published_at')
                ->first();
        }

        // Section 5 : le saviez-vous (terme glossaire ou acronyme)
        $didYouKnow = null;
        if (class_exists(\Modules\Dictionary\Models\Term::class)) {
            $didYouKnow = \Modules\Dictionary\Models\Term::where('is_published', true)
                ->inRandomOrder()
                ->first();
        }
        if (! $didYouKnow && class_exists(\Modules\Acronyms\Models\Acronym::class)) {
            $didYouKnow = \Modules\Acronyms\Models\Acronym::inRandomOrder()->first();
        }

        // Section 6 : outil interactif gratuit (rotation)
        $interactiveTool = null;
        if (class_exists(\Modules\Tools\Models\Tool::class)) {
            $interactiveTool = \Modules\Tools\Models\Tool::where('is_active', true)
                ->inRandomOrder()
                ->first();
        }

        // Section 7 : terme IA de la semaine (1 seul, hero card educative)
        $aiTerm = null;
        if (class_exists(\Modules\Dictionary\Models\Term::class)) {
            $aiTerm = \Modules\Dictionary\Models\Term::where('is_published', true)
                ->inRandomOrder()
                ->first();
        }

        $weekNumber = (int) now()->weekOfYear;

        if (! $highlight && $topNews->isEmpty()) {
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
                $highlight, $topNews, $toolOfWeek, $featuredArticle, $didYouKnow, $weekNumber, $aiTerm, $interactiveTool
            ));
        }

        $this->newLine();
        $this->components->twoColumnDetail('Highlight', $highlight?->title ?? 'none');
        $this->components->twoColumnDetail('Top news', (string) $topNews->count());
        $this->components->twoColumnDetail('Tool of week', $toolOfWeek?->name ?? 'none');
        $this->components->twoColumnDetail('Featured article', $featuredArticle?->title ?? 'none');
        $this->components->twoColumnDetail('Did you know', $didYouKnow?->term ?? $didYouKnow?->name ?? 'none');
        $this->components->twoColumnDetail('Subscribers', (string) $subscribers->count());
        $this->components->info('Weekly digest #'.$weekNumber.' sent successfully.');

        return self::SUCCESS;
    }
}
