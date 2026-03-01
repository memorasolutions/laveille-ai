<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div x-data="{ open: false }" class="relative" wire:poll.30s>
    <button
        @click="open = !open"
        class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none rounded-full"
        aria-label="Notifications"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-cloak
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50 border border-gray-200 dark:bg-gray-800 dark:border-gray-700"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
            @if($unreadCount > 0)
                <button
                    wire:click="markAllRead"
                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium"
                >
                    Tout lire
                </button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($notifications as $notif)
                <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex justify-between items-start gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 dark:text-gray-200 truncate">
                                {{ $notif->data['message'] ?? class_basename($notif->type) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $notif->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <button
                            wire:click="markRead('{{ $notif->id }}')"
                            class="text-gray-400 hover:text-gray-600 flex-shrink-0"
                            title="Marquer comme lu"
                        >
                            <svg class="h-4 w-4" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-300" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aucune notification</p>
                </div>
            @endforelse
        </div>

        <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
            <a
                href="{{ route('admin.notifications.index') }}"
                class="block text-center text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium py-1"
            >
                Voir toutes les notifications
            </a>
        </div>
    </div>
</div>
