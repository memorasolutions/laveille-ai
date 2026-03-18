<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Providers;

use Carbon\Carbon;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\Ticket;
use Modules\Core\Contracts\MetricProviderInterface;
use Modules\Core\DataTransferObjects\MetricWidget;

class AIMetricProvider implements MetricProviderInterface
{
    public function getMetricName(): string
    {
        return 'ai';
    }

    /** @return list<MetricWidget> */
    public function getWidgets(): array
    {
        $metrics = $this->getMetrics(now()->startOfMonth(), now()->endOfMonth());

        return [
            new MetricWidget(name: 'Conversations', value: (string) $metrics['conversations'], type: 'number', icon: 'message-circle'),
            new MetricWidget(name: 'Tickets ouverts', value: (string) $metrics['open_tickets'], type: 'number', icon: 'ticket'),
            new MetricWidget(name: 'Taux résolution', value: $metrics['resolution_rate'].'%', type: 'percent', icon: 'check-circle'),
        ];
    }

    /** @return array<string, mixed> */
    public function getMetrics(Carbon $from, Carbon $to): array
    {
        $total = Ticket::whereBetween('created_at', [$from, $to])->count();
        $resolved = Ticket::where('status', 'resolved')->whereBetween('created_at', [$from, $to])->count();
        $open = Ticket::where('status', 'open')->count();
        $resolutionRate = $total > 0 ? round($resolved / $total * 100, 1) : 0.0;

        return [
            'conversations' => AiConversation::whereBetween('created_at', [$from, $to])->count(),
            'open_tickets' => $open,
            'resolution_rate' => number_format($resolutionRate, 1),
        ];
    }
}
