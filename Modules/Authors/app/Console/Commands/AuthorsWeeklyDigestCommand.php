<?php

declare(strict_types=1);

namespace Modules\Authors\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Authors\Mail\AuthorWeeklyDigestMail;
use Modules\Authors\Models\AuthorAffiliateLink;
use Modules\Authors\Models\AuthorComment;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorSubscriber;
use Modules\Authors\Models\AuthorWebmention;

class AuthorsWeeklyDigestCommand extends Command
{
    protected $signature = 'authors:digest {--author-id= : Send to one author only}';

    protected $description = 'Envoie digest hebdomadaire à tous auteurs actifs';

    public function handle(): int
    {
        $weekStart = now()->subDays(7)->startOfDay();
        $weekEnd = now()->endOfDay();

        $query = AuthorProfile::with('user')->whereNull('archived_at');
        if ($this->option('author-id')) {
            $query->where('id', (int) $this->option('author-id'));
        }

        $authors = $query->get();
        $sent = 0;

        foreach ($authors as $author) {
            if (! $author->user?->email) {
                continue;
            }

            $postIds = AuthorPost::where('author_profile_id', $author->id)->pluck('id');

            $stats = [
                'posts_published' => AuthorPost::where('author_profile_id', $author->id)
                    ->where('status', 'published')
                    ->whereBetween('published_at', [$weekStart, $weekEnd])
                    ->count(),
                'comments_received' => AuthorComment::where('author_profile_id', $author->id)
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->count(),
                'subscribers_gained' => AuthorSubscriber::where('author_profile_id', $author->id)
                    ->whereBetween('confirmed_at', [$weekStart, $weekEnd])
                    ->count(),
                'webmentions_verified' => class_exists(AuthorWebmention::class)
                    ? AuthorWebmention::whereIn('author_post_id', $postIds)
                        ->whereNotNull('verified_at')
                        ->whereBetween('verified_at', [$weekStart, $weekEnd])
                        ->count()
                    : 0,
                'tips_count' => 0,
                'affiliate_clicks' => class_exists(AuthorAffiliateLink::class)
                    ? (int) AuthorAffiliateLink::where('author_profile_id', $author->id)
                        ->sum('clicks_count')
                    : 0,
            ];

            if (array_sum($stats) === 0) {
                continue;
            }

            try {
                Mail::to($author->user->email)->queue(
                    new AuthorWeeklyDigestMail($author, $stats, $weekStart, $weekEnd)
                );
                $sent++;
                $this->line("✅ Digest envoyé à {$author->slug}");
            } catch (\Throwable $e) {
                $this->error("❌ {$author->slug} : ".$e->getMessage());
            }
        }

        $this->info("📬 {$sent} digests envoyés.");

        return self::SUCCESS;
    }
}
