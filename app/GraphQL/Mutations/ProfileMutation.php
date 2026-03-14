<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace App\GraphQL\Mutations;

use App\Models\User;

final class ProfileMutation
{
    public function updateProfile(mixed $root, array $args): User
    {
        /** @var User $user */
        $user = auth()->user();

        $user->update(array_filter([
            'name' => $args['name'] ?? null,
            'bio' => $args['bio'] ?? null,
        ], fn ($v) => $v !== null));

        return $user->fresh();
    }
}
