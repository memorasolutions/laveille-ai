<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Contracts;

interface SmsDriverInterface
{
    public function send(string $to, string $message): bool;

    public function sendBulk(array $recipients, string $message): array;

    public function getBalance(): ?float;

    public function isConfigured(): bool;
}
