<?php

declare(strict_types=1);

namespace Modules\Authors\Contracts;

interface NewsletterProvider
{
    public function connect(array $credentials): bool;

    public function disconnect(): bool;

    public function listAudiences(): array;

    public function createCampaign(string $title, string $htmlContent, ?string $audienceId = null): string;

    public function sendCampaign(string $campaignId): bool;

    public function healthCheck(): bool;

    public function getProviderName(): string;
}
