<div class="dropdown" wire:poll.30s>
    <button
        class="has-indicator w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center position-relative"
        type="button"
        data-bs-toggle="dropdown"
        aria-label="Notifications"
    >
        <iconify-icon icon="iconoir:bell" class="text-primary-light text-xl"></iconify-icon>
        @if($unreadCount > 0)
            <span class="position-absolute top-0 end-0 w-16-px h-16-px bg-danger-main text-white rounded-circle d-flex justify-content-center align-items-center text-xs fw-bold">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div class="dropdown-menu to-top dropdown-menu-lg p-0">
        <div class="m-16 py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
            <h6 class="text-lg text-primary-light fw-semibold mb-0">Notifications</h6>
            @if($unreadCount > 0)
                <button
                    wire:click="markAllRead"
                    type="button"
                    class="text-primary-600 fw-semibold text-sm border-0 bg-transparent p-0"
                >
                    Tout lire
                </button>
            @endif
        </div>

        <div class="max-h-400-px overflow-y-auto scroll-sm pe-4">
            @forelse($notifications as $notif)
                <div class="px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between border-bottom border-neutral-100">
                    <div class="d-flex align-items-start gap-12 flex-grow-1">
                        <span class="w-36-px h-36-px bg-primary-100 text-primary-600 rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                            <iconify-icon icon="solar:bell-outline" class="text-lg"></iconify-icon>
                        </span>
                        <div class="flex-grow-1">
                            <p class="text-sm fw-medium text-primary-light mb-4">
                                {{ $notif->data['message'] ?? class_basename($notif->type) }}
                            </p>
                            <span class="text-xs text-secondary-light">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <button
                        wire:click="markRead('{{ $notif->id }}')"
                        type="button"
                        class="text-secondary-light hover-text-danger border-0 bg-transparent p-0 flex-shrink-0"
                        title="Marquer comme lu"
                    >
                        <iconify-icon icon="radix-icons:cross-1" class="text-sm"></iconify-icon>
                    </button>
                </div>
            @empty
                <div class="px-16 py-24 text-center">
                    <iconify-icon icon="iconoir:bell" class="text-4xl text-secondary-light mb-8"></iconify-icon>
                    <p class="text-sm text-secondary-light mb-0">Aucune notification</p>
                </div>
            @endforelse
        </div>

        <div class="text-center py-12 px-16 border-top border-neutral-100">
            <a href="{{ route('admin.notifications.index') }}" class="text-primary-600 fw-semibold text-sm">
                Voir toutes les notifications
            </a>
        </div>
    </div>
</div>
