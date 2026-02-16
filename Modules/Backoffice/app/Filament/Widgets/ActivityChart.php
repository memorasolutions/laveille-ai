<?php

declare(strict_types=1);

namespace Modules\Backoffice\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Spatie\Activitylog\Models\Activity;

class ActivityChart extends ChartWidget
{
    protected ?string $heading = 'Activité (7 derniers jours)';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $activities = Activity::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->get();

        $labels = [];
        $data = [];

        $currentDate = Carbon::now()->subDays(6)->startOfDay();
        for ($i = 0; $i < 7; $i++) {
            $date = $currentDate->copy()->format('d/m');
            $labels[] = $date;

            $activity = $activities->firstWhere('date', $currentDate->format('Y-m-d'));
            $data[] = $activity ? $activity->count : 0;

            $currentDate->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Activités',
                    'backgroundColor' => '#6366f1',
                    'borderColor' => '#6366f1',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
