@props(['title', 'value', 'icon' => null, 'color' => 'indigo', 'description' => null])

@php
    $colors = [
        'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400',
        'green' => 'bg-green-50 text-green-600 dark:bg-green-900/50 dark:text-green-400',
        'yellow' => 'bg-yellow-50 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400',
        'red' => 'bg-red-50 text-red-600 dark:bg-red-900/50 dark:text-red-400',
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400',
    ];
    $colorClass = $colors[$color] ?? $colors['indigo'];
@endphp

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
    <div class="flex items-center gap-4">
        @if($icon)
            <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ $colorClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
            </div>
        @endif
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
            @if($description)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    </div>
</div>
