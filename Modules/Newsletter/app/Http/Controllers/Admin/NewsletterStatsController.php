<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Newsletter\Models\NewsletterEvent;

class NewsletterStatsController extends Controller
{
    public function index()
    {
        $since = now()->subDays(30);

        $eventCounts = NewsletterEvent::where('created_at', '>=', $since)
            ->select('event', DB::raw('COUNT(*) as count'))
            ->groupBy('event')
            ->pluck('count', 'event');

        $delivered = $eventCounts->get('delivered', 0);
        $opened = $eventCounts->get('opened', 0);
        $clicked = $eventCounts->get('clicked', 0);

        $openRate = $delivered > 0 ? round(($opened / $delivered) * 100, 1) : 0;
        $clickRate = $opened > 0 ? round(($clicked / $opened) * 100, 1) : 0;

        $topLinks = NewsletterEvent::where('event', 'clicked')
            ->where('created_at', '>=', $since)
            ->whereNotNull('link')
            ->select('link', DB::raw('COUNT(*) as count'))
            ->groupBy('link')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $topSubscribers = NewsletterEvent::where('created_at', '>=', $since)
            ->whereIn('event', ['opened', 'clicked'])
            ->select(
                'email',
                DB::raw("SUM(CASE WHEN event = 'opened' THEN 1 ELSE 0 END) as opened_count"),
                DB::raw("SUM(CASE WHEN event = 'clicked' THEN 1 ELSE 0 END) as clicked_count"),
            )
            ->groupBy('email')
            ->orderByDesc(DB::raw('opened_count + clicked_count'))
            ->limit(10)
            ->get();

        return view('newsletter::admin.stats', [
            'eventCounts' => $eventCounts,
            'openRate' => $openRate,
            'clickRate' => $clickRate,
            'topLinks' => $topLinks,
            'topSubscribers' => $topSubscribers,
        ]);
    }
}
