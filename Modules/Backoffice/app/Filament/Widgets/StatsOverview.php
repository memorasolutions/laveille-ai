<?php

declare(strict_types=1);

namespace Modules\Backoffice\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return [
            Stat::make('Utilisateurs', User::count())
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Rôles', Role::count())
                ->icon('heroicon-o-shield-check')
                ->color('success'),
            Stat::make('Activités (7j)', Activity::where('created_at', '>=', now()->subDays(7))->count())
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning'),
        ];
    }
}
