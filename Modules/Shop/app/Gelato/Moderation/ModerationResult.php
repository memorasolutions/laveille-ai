<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato\Moderation;

final class ModerationResult
{
    private function __construct(public readonly bool $allowed, public readonly ?string $reason = null) {}

    public static function allow(): self
    {
        return new self(true);
    }

    public static function reject(string $reason): self
    {
        return new self(false, $reason);
    }
}
