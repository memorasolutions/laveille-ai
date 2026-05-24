<?php

declare(strict_types=1);

namespace Modules\Authors\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Authors\Models\AuthorAffiliateLink;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;
use Modules\Authors\Models\AuthorWebmention;

final class AuthorsReportCommand extends Command
{
    protected $signature = 'authors:report {--since=30 : Days since to include in stats}';

    protected $description = 'Rapport stats all-authors Memora';

    public function handle(): int
    {
        $since = Carbon::now()->subDays((int) $this->option('since'));

        $authors = AuthorProfile::with('user')
            ->whereNull('archived_at')
            ->get();

        if ($authors->isEmpty()) {
            $this->warn('Aucun auteur actif.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($authors as $author) {
            $postsBase = AuthorPost::where('author_profile_id', $author->id);
            $postsCount = (clone $postsBase)->count();
            $publishedCount = (clone $postsBase)->published()->count();
            $postIds = (clone $postsBase)->pluck('id');

            $commentsCount = AuthorComment::where('author_profile_id', $author->id)->count();
            $subscribersCount = AuthorSubscriber::where('author_profile_id', $author->id)->confirmed()->count();

            $webmentionsCount = class_exists(AuthorWebmention::class)
                ? AuthorWebmention::whereIn('author_post_id', $postIds)->count()
                : 0;

            $affiliateClicksTotal = class_exists(AuthorAffiliateLink::class)
                ? (int) AuthorAffiliateLink::where('author_profile_id', $author->id)->sum('clicks_count')
                : 0;

            $rows[] = [
                $author->id,
                $author->slug,
                $author->display_name ?? $author->slug,
                $author->tier,
                "{$postsCount} ({$publishedCount} pub)",
                $commentsCount,
                $subscribersCount,
                $webmentionsCount,
                $affiliateClicksTotal,
            ];
        }

        $this->info("📊 Rapport Authors Memora — {$this->option('since')} derniers jours");
        $this->newLine();

        $this->table(
            ['ID', 'Slug', 'Nom', 'Tier', 'Posts', 'Comments', 'Abonnés', 'Webmentions', 'Affiliate clicks'],
            $rows
        );

        $this->newLine();
        $this->line('Total auteurs actifs : '.$authors->count());

        return self::SUCCESS;
    }
}
