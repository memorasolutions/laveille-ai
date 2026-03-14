<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace App\GraphQL\Queries;

use Illuminate\Database\Eloquent\Collection;

final class MyTeamsQuery
{
    /**
     * @return Collection<int, \Modules\Team\Models\Team>
     */
    public function __invoke(mixed $root, array $args): Collection
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        /** @var Collection<int, \Modules\Team\Models\Team> $teams */
        $teams = $user->teams;

        return $teams;
    }
}
