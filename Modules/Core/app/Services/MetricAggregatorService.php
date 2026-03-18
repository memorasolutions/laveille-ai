<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\MetricProviderInterface;
use Modules\Core\DataTransferObjects\MetricWidget;

class MetricAggregatorService
{
    /** @return iterable<MetricProviderInterface> */
    private function getProviders(): iterable
    {
        try {
            return app()->tagged('metric_providers');
        } catch (\InvalidArgumentException) {
            return [];
        }
    }

    /** @return list<MetricWidget> */
    public function getAllWidgets(): array
    {
        $widgets = [];
        foreach ($this->getProviders() as $provider) {
            try {
                $widgets = [...$widgets, ...$provider->getWidgets()];
            } catch (\Throwable $e) {
                Log::warning("MetricProvider {$provider->getMetricName()} failed: {$e->getMessage()}");
            }
        }

        return $widgets;
    }

    /** @return array<string, mixed> */
    public function getProviderMetrics(string $providerName, Carbon $from, Carbon $to): array
    {
        foreach ($this->getProviders() as $provider) {
            if ($provider->getMetricName() === $providerName) {
                try {
                    return $provider->getMetrics($from, $to);
                } catch (\Throwable $e) {
                    Log::warning("MetricProvider {$providerName} metrics failed: {$e->getMessage()}");

                    return [];
                }
            }
        }

        return [];
    }

    /** @return list<string> */
    public function getRegisteredProviders(): array
    {
        $names = [];
        foreach ($this->getProviders() as $provider) {
            $names[] = $provider->getMetricName();
        }

        return $names;
    }
}
