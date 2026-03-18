<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Providers;

use Carbon\Carbon;
use Modules\Core\Contracts\MetricProviderInterface;
use Modules\Core\DataTransferObjects\MetricWidget;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;

class NewsletterMetricProvider implements MetricProviderInterface
{
    public function getMetricName(): string
    {
        return 'newsletter';
    }

    /** @return list<MetricWidget> */
    public function getWidgets(): array
    {
        $metrics = $this->getMetrics(now()->startOfMonth(), now()->endOfMonth());

        return [
            new MetricWidget(name: 'Abonnés actifs', value: (string) $metrics['active_subscribers'], type: 'number', icon: 'mail'),
            new MetricWidget(name: 'Campagnes envoyées', value: (string) $metrics['sent_campaigns'], type: 'number', icon: 'send'),
            new MetricWidget(name: 'Nouveaux abonnés', value: (string) $metrics['new_subscribers'], type: 'number', icon: 'user-plus'),
        ];
    }

    /** @return array<string, mixed> */
    public function getMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'active_subscribers' => Subscriber::active()->count(),
            'sent_campaigns' => Campaign::where('status', 'sent')->whereBetween('created_at', [$from, $to])->count(),
            'new_subscribers' => Subscriber::whereBetween('created_at', [$from, $to])->count(),
        ];
    }
}
