<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato\Moderation;

/** Modérateur par défaut : laisse tout passer (aucun service payant, voir ContentModeratorContract). */
class NullContentModerator implements ContentModeratorContract
{
    public function checkText(string $text): ModerationResult
    {
        return ModerationResult::allow();
    }

    public function checkImage(string $binary, string $mime): ModerationResult
    {
        return ModerationResult::allow();
    }
}
