<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ABTest\Services;

use Modules\ABTest\Models\ABParticipation;
use Modules\ABTest\Models\Experiment;

class ABTestService
{
    public function assignVariant(Experiment $experiment, ?int $userId = null, ?string $sessionId = null): string
    {
        $query = ABParticipation::where('experiment_id', $experiment->id);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $existing = $query->first();

        if ($existing) {
            return $existing->variant;
        }

        $variants = $experiment->variants;
        $variant = $variants[array_rand($variants)];

        ABParticipation::create([
            'experiment_id' => $experiment->id,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'variant' => $variant,
        ]);

        return $variant;
    }

    public function convert(Experiment $experiment, ?int $userId = null, ?string $sessionId = null): bool
    {
        $query = ABParticipation::where('experiment_id', $experiment->id);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $participation = $query->first();

        if ($participation && ! $participation->converted_at) {
            $participation->update(['converted_at' => now()]);

            return true;
        }

        return false;
    }

    /** @return array<string, array{participants: int, conversions: int, rate: float}> */
    public function getResults(Experiment $experiment): array
    {
        return $experiment->getResults();
    }
}
