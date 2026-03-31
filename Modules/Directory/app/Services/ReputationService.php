<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Settings\Facades\Settings;

class ReputationService
{
    // Points
    public const REVIEW_APPROVED = 10;

    public const DISCUSSION_APPROVED = 5;

    public const RESOURCE_APPROVED = 8;

    public const REPLY_APPROVED = 3;

    public const LIKE_RECEIVED = 1;

    public const REPORT_CONFIRMED = 5;

    public const CONTENT_REJECTED = -10;

    public const VOTE_CAST = 1;

    public const CONTENT_COMMUNITY_APPROVED = 15;

    public const STREAK_BONUS_7 = 10;

    // Niveaux
    public const LEVEL_NOUVEAU = 0;

    public const LEVEL_CONTRIBUTEUR = 1;

    public const LEVEL_VERIFIE = 2;

    public const LEVEL_EXPERT = 3;

    private static function thresholdContributeur(): int
    {
        return (int) (class_exists(Settings::class) ? Settings::get('reputation.threshold_contributeur', 15) : 15);
    }

    private static function thresholdVerifie(): int
    {
        return (int) (class_exists(Settings::class) ? Settings::get('reputation.threshold_verifie', 50) : 50);
    }

    private static function thresholdExpert(): int
    {
        return (int) (class_exists(Settings::class) ? Settings::get('reputation.threshold_expert', 150) : 150);
    }

    private function getMultipliers(): array
    {
        return [
            self::LEVEL_NOUVEAU => 1.0,
            self::LEVEL_CONTRIBUTEUR => (float) (class_exists(Settings::class) ? Settings::get('reputation.multiplier_contributeur', 1.25) : 1.25),
            self::LEVEL_VERIFIE => (float) (class_exists(Settings::class) ? Settings::get('reputation.multiplier_verifie', 1.5) : 1.5),
            self::LEVEL_EXPERT => (float) (class_exists(Settings::class) ? Settings::get('reputation.multiplier_expert', 2.0) : 2.0),
        ];
    }

    public function addPoints(User $user, int $points, string $reason = ''): void
    {
        $finalPoints = $points;
        if ($points > 0) {
            $multipliers = $this->getMultipliers();
            $finalPoints = (int) round($points * ($multipliers[$user->trust_level] ?? 1.0));
        }

        $user->reputation_points = max(0, $user->reputation_points + $finalPoints);
        $user->save();

        // Logger pour leaderboard mensuel
        if (Schema::hasTable('reputation_logs') && $finalPoints !== 0) {
            DB::table('reputation_logs')->insert([
                'user_id' => $user->id,
                'points' => $finalPoints,
                'reason' => $reason ?: 'general',
                'created_at' => now(),
            ]);
        }

        $this->checkLevelUp($user);
        $this->checkBadges($user);
    }

    public function getMultiplier(int $level): float
    {
        return $this->getMultipliers()[$level] ?? 1.0;
    }

    public function checkLevelUp(User $user): void
    {
        $pts = $user->reputation_points;
        $newLevel = match (true) {
            $pts >= self::thresholdExpert() => self::LEVEL_EXPERT,
            $pts >= self::thresholdVerifie() => self::LEVEL_VERIFIE,
            $pts >= self::thresholdContributeur() => self::LEVEL_CONTRIBUTEUR,
            default => self::LEVEL_NOUVEAU,
        };

        if ($newLevel !== $user->trust_level) {
            $user->trust_level = $newLevel;
            $user->save();
        }
    }

    public function shouldAutoApprove(User $user, string $type): bool
    {
        return match ($user->trust_level) {
            self::LEVEL_EXPERT => true,
            self::LEVEL_VERIFIE => in_array($type, ['discussion', 'review', 'resource']),
            self::LEVEL_CONTRIBUTEUR => $type === 'discussion',
            default => false,
        };
    }

    public function canVoteRoadmap(User $user): bool
    {
        return $user->trust_level >= self::LEVEL_VERIFIE;
    }

    public function canModerateContent(User $user): bool
    {
        return $user->trust_level >= self::LEVEL_EXPERT;
    }

    public function isFeaturedContributor(User $user): bool
    {
        return $user->trust_level >= self::LEVEL_VERIFIE;
    }

    public function checkBadges(User $user): void
    {
        $uid = $user->id;
        $badges = [];

        $reviews = DB::table('directory_reviews')->where('user_id', $uid)->where('is_approved', true)->count();
        $resources = DB::table('directory_resources')->where('user_id', $uid)->where('is_approved', true)->count();
        $discussions = DB::table('directory_discussions')->where('user_id', $uid)->where('is_approved', true)->whereNull('parent_id')->count();
        $replies = DB::table('directory_discussions')->where('user_id', $uid)->where('is_approved', true)->whereNotNull('parent_id')->count();

        // Likes reçus = sum des upvotes sur tout le contenu de l'utilisateur
        $likesOnReviews = (int) DB::table('directory_reviews')->where('user_id', $uid)->sum('upvotes');
        $likesOnDiscussions = (int) DB::table('directory_discussions')->where('user_id', $uid)->sum('upvotes');
        $likesOnResources = (int) DB::table('directory_resources')->where('user_id', $uid)->sum('upvotes');
        $totalLikes = $likesOnReviews + $likesOnDiscussions + $likesOnResources;

        if (($reviews + $resources + $discussions) >= 1) {
            $badges[] = 'first_contribution';
        }
        if ($reviews >= 5) {
            $badges[] = 'critic';
        }
        if ($resources >= 5) {
            $badges[] = 'curator';
        }
        if (($discussions + $replies) >= 10) {
            $badges[] = 'animator';
        }
        if ($totalLikes >= 20) {
            $badges[] = 'popular';
        }
        if ($user->trust_level >= self::LEVEL_EXPERT) {
            $badges[] = 'expert';
        }

        // Badge auteur publié (si module Blog actif)
        if (class_exists('Modules\\Blog\\Models\\Article')) {
            $publishedArticles = DB::table('articles')
                ->where('submitted_by', $uid)
                ->where('submission_status', 'approved')
                ->count();
            if ($publishedArticles >= 1) {
                $badges[] = 'published_author';
            }
        }

        // Badges vote (si module Voting actif)
        if (class_exists('Modules\\Voting\\Models\\Vote')) {
            $votesGiven = DB::table('community_votes')->where('user_id', $uid)->count();
            if ($votesGiven >= 50) {
                $badges[] = 'voter';
            }
        }

        // Badge correcteur (suggestions acceptées)
        if (class_exists('Modules\\Directory\\Models\\ToolSuggestion')) {
            $suggestionsAccepted = DB::table('directory_suggestions')->where('user_id', $uid)->where('status', 'approved')->count();
            if ($suggestionsAccepted >= 10) {
                $badges[] = 'corrector';
            }
        }

        $now = now();
        foreach ($badges as $badge) {
            DB::table('user_badges')->insertOrIgnore([
                'user_id' => $uid,
                'badge_key' => $badge,
                'earned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function getUserBadges(User $user): array
    {
        return DB::table('user_badges')->where('user_id', $user->id)->pluck('badge_key')->toArray();
    }

    public static function getLevelInfo(int $level): array
    {
        return match ($level) {
            self::LEVEL_NOUVEAU => [
                'name' => 'Nouveau', 'emoji' => '🌱', 'next_threshold' => self::thresholdContributeur(), 'multiplier' => 'x1',
                'privileges' => ['Soumettre du contenu (modéré)', 'Participer aux discussions'],
            ],
            self::LEVEL_CONTRIBUTEUR => [
                'name' => 'Contributeur', 'emoji' => '🌿', 'next_threshold' => self::thresholdVerifie(), 'multiplier' => 'x1.25',
                'privileges' => ['Auto-approbation des discussions', 'Badge Contributeur visible', 'Points x1.25'],
            ],
            self::LEVEL_VERIFIE => [
                'name' => 'Membre vérifié', 'emoji' => '🌳', 'next_threshold' => self::thresholdExpert(), 'multiplier' => 'x1.5',
                'privileges' => ['Auto-approbation avis et ressources', 'Badge Vérifié visible', 'Voter sur la roadmap', 'Contributeur mis en avant', 'Points x1.5'],
            ],
            self::LEVEL_EXPERT => [
                'name' => 'Expert', 'emoji' => '⭐', 'next_threshold' => null, 'multiplier' => 'x2',
                'privileges' => ['Auto-approbation de tout contenu', 'Badge Expert doré', 'Modérer le contenu signalé', 'Nom mis en avant', 'Points x2'],
            ],
            default => ['name' => 'Inconnu', 'emoji' => '❓', 'next_threshold' => null, 'multiplier' => 'x1', 'privileges' => []],
        };
    }

    public static function getBadgeInfo(string $key): array
    {
        return match ($key) {
            'first_contribution' => ['name' => 'Premier pas', 'emoji' => '🎉', 'description' => 'Première contribution approuvée'],
            'critic' => ['name' => 'Critique', 'emoji' => '⭐', 'description' => '5 avis approuvés'],
            'curator' => ['name' => 'Curateur', 'emoji' => '📚', 'description' => '5 ressources approuvées'],
            'animator' => ['name' => 'Animateur', 'emoji' => '💬', 'description' => '10 discussions créées'],
            'popular' => ['name' => 'Populaire', 'emoji' => '❤️', 'description' => '20 likes reçus'],
            'expert' => ['name' => 'Expert', 'emoji' => '🏆', 'description' => 'Niveau expert atteint'],
            'published_author' => ['name' => 'Auteur publié', 'emoji' => '✍️', 'description' => 'Premier article publié sur le blog'],
            'voter' => ['name' => 'Votant actif', 'emoji' => '👍', 'description' => '50 votes donnés'],
            'corrector' => ['name' => 'Correcteur', 'emoji' => '📋', 'description' => '10 suggestions acceptées'],
            'streak_7' => ['name' => 'Assidu', 'emoji' => '🔥', 'description' => '7 jours consécutifs de visite'],
            'streak_30' => ['name' => 'Marathonien', 'emoji' => '💎', 'description' => '30 jours consécutifs de visite'],
            default => ['name' => $key, 'emoji' => '🏅', 'description' => ''],
        };
    }
}
