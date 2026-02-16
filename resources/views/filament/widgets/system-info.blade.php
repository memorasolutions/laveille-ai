<x-filament-widgets::widget>
    <x-filament::section heading="Informations système">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
            @foreach([
                'PHP' => $php,
                'Laravel' => $laravel,
                'Environnement' => $environment,
                'Cache' => $cache,
                'Queue' => $queue,
                'Fuseau' => $timezone,
            ] as $label => $value)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $value }}</dd>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
