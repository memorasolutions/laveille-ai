<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Drivers;

use Modules\Notifications\Contracts\SmsDriverInterface;

class NullSmsDriver implements SmsDriverInterface
{
    public function send(string $to, string $message): bool
    {
        return true;
    }

    public function sendBulk(array $recipients, string $message): array
    {
        return array_fill_keys($recipients, true);
    }

    public function getBalance(): ?float
    {
        return null;
    }

    public function isConfigured(): bool
    {
        return false;
    }
}
