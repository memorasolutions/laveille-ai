<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Carbon\Carbon;
use Modules\Core\DataTransferObjects\MetricWidget;

interface MetricProviderInterface
{
    public function getMetricName(): string;

    /** @return list<MetricWidget> */
    public function getWidgets(): array;

    /** @return array<string, mixed> */
    public function getMetrics(Carbon $from, Carbon $to): array;
}
