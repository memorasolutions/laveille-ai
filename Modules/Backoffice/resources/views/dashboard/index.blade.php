@extends('backoffice::layouts.admin', ['title' => __('Tableau de bord'), 'subtitle' => __('Administration')])

@section('content')
    {{-- Stats --}}
    <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @include('backoffice::partials.stat-card', [
            'title' => 'Utilisateurs',
            'value' => $usersCount,
            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            'color' => 'indigo',
        ])

        @include('backoffice::partials.stat-card', [
            'title' => 'Rôles',
            'value' => $rolesCount,
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'color' => 'green',
        ])

        @include('backoffice::partials.stat-card', [
            'title' => 'PHP',
            'value' => $phpVersion,
            'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
            'color' => 'blue',
        ])

        @include('backoffice::partials.stat-card', [
            'title' => 'Environnement',
            'value' => ucfirst($environment),
            'description' => 'Laravel ' . $laravelVersion,
            'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
            'color' => 'yellow',
        ])
    </div>

    {{-- Recent activity --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Activité récente</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($recentActivities as $activity)
                <div class="flex items-center justify-between px-6 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity->description }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $activity->causer?->name ?? 'Système' }} - {{ $activity->subject_type ? class_basename($activity->subject_type) : '' }}
                        </p>
                    </div>
                    <span class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Aucune activité récente.
                </div>
            @endforelse
        </div>
    </div>
@endsection
