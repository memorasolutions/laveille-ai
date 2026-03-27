<?php

declare(strict_types=1);

namespace Modules\Voting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class VoteController extends Controller
{
    private const VOTABLE_TYPES = [
        'tool' => \Modules\Directory\Models\Tool::class,
        'resource' => \Modules\Directory\Models\ToolResource::class,
        'review' => \Modules\Directory\Models\ToolReview::class,
        'acronym' => \Modules\Acronyms\Models\Acronym::class,
    ];

    public function toggle(string $type, int $id): JsonResponse
    {
        $class = self::VOTABLE_TYPES[$type] ?? null;

        if (! $class || ! class_exists($class)) {
            return response()->json(['error' => 'Type invalide'], 422);
        }

        $item = $class::findOrFail($id);

        if (! method_exists($item, 'toggleVote')) {
            return response()->json(['error' => 'Vote non supporté'], 422);
        }

        $voted = $item->toggleVote(auth()->user());
        $count = $item->communityVoteCount();
        $tier = $item->getBadgeTier();

        // Points de réputation pour le votant
        if ($voted && class_exists(\Modules\Directory\Services\ReputationService::class)) {
            $reputation = app(\Modules\Directory\Services\ReputationService::class);
            $reputation->addPoints(auth()->user(), config('voting.reputation.vote_cast', 1), 'vote_cast');
        }

        // Notification seuils de votes (2, 5, 10) au créateur
        if ($voted && in_array($count, [2, 5, 10]) && isset($item->user_id) && $item->user_id) {
            $author = \App\Models\User::find($item->user_id);
            if ($author && $author->id !== auth()->id()) {
                $title = $item->title ?? $item->name ?? __('votre contenu');
                $url = match ($type) {
                    'tool' => route('directory.show', $item->slug ?? $id),
                    'acronym' => route('acronyms.show', $item->getTranslation('slug', app()->getLocale())),
                    default => url('/'),
                };
                $author->notify(new \Modules\Voting\Notifications\VoteThresholdNotification($count, $title, $url));
            }
        }

        // Auto-approbation si seuil atteint
        if ($voted && $tier === 'approved' && isset($item->is_approved) && ! $item->is_approved) {
            $item->is_approved = true;
            $item->save();

            // Points pour le créateur
            if (isset($item->user_id) && class_exists(\Modules\Directory\Services\ReputationService::class)) {
                $author = \App\Models\User::find($item->user_id);
                if ($author) {
                    $reputation = app(\Modules\Directory\Services\ReputationService::class);
                    $reputation->addPoints($author, config('voting.reputation.content_community_approved', 15), 'community_approved');
                }
            }
        }

        return response()->json([
            'voted' => $voted,
            'count' => $count,
            'tier' => $tier,
            'badge' => config("voting.badge_styles.{$tier}"),
        ]);
    }
}
