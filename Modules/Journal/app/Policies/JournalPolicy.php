<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 */

declare(strict_types=1);

namespace Modules\Journal\Policies;

use App\Models\User;
use Modules\Journal\Models\Journal;

class JournalPolicy
{
    public function view(?User $user, Journal $journal): bool
    {
        return $journal->isPublished() || ($user !== null && $user->id === $journal->user_id);
    }

    public function update(User $user, Journal $journal): bool
    {
        return $user->id === $journal->user_id;
    }

    public function delete(User $user, Journal $journal): bool
    {
        return $user->id === $journal->user_id;
    }
}
