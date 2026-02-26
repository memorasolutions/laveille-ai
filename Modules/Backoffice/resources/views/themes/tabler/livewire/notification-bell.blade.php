<div class="nav-item dropdown" x-data="{ open: false }">
    <a href="#" class="nav-link px-0 position-relative" @click.prevent="open = !open; if(open) $wire.loadNotifications()">
        <i class="ti ti-bell fs-3"></i>
        @if(($unreadCount ?? 0) > 0)
        <span class="badge bg-red badge-notification" style="position:absolute; top:-4px; right:-4px; min-width:18px; height:18px; padding:0 4px; font-size:.65rem; display:flex; align-items:center; justify-content:center;">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
        @endif
    </a>

    <div class="dropdown-menu dropdown-menu-end dropdown-menu-card"
        :class="open ? 'show' : ''"
        @click.outside="open = false"
        style="width: 320px; margin-top: .5rem;">
        <div class="card m-0 border-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="ti ti-bell me-2"></i>Notifications
                    @if(($unreadCount ?? 0) > 0)
                    <span class="badge bg-red ms-1">{{ $unreadCount }}</span>
                    @endif
                </h5>
                @if(($unreadCount ?? 0) > 0)
                <button wire:click="markAllAsRead" class="btn btn-sm btn-ghost-primary">
                    <i class="ti ti-checks me-1"></i>Tout lire
                </button>
                @endif
            </div>

            <div class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                @forelse($notifications ?? [] as $notification)
                <div class="list-group-item list-group-item-action {{ $notification->read_at ? '' : 'bg-azure-lt' }} px-3 py-2">
                    <div class="d-flex align-items-start gap-2">
                        <span class="avatar avatar-sm rounded-circle bg-primary-lt flex-shrink-0">
                            <i class="ti ti-bell-filled text-primary" style="font-size:.8rem;"></i>
                        </span>
                        <div class="flex-fill" style="min-width:0;">
                            <div class="fw-bold small text-truncate">
                                {{ $notification->data['title'] ?? 'Notification' }}
                            </div>
                            @if(!empty($notification->data['message']))
                            <div class="small text-muted text-truncate">{{ $notification->data['message'] }}</div>
                            @endif
                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                        @if(!$notification->read_at)
                        <button wire:click="markAsRead('{{ $notification->id }}')" class="btn btn-sm btn-ghost-secondary p-0" title="Marquer comme lu">
                            <i class="ti ti-x" style="font-size:.75rem;"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center text-muted py-4">
                    <i class="ti ti-bell-off fs-2 d-block mb-2"></i>
                    Aucune notification
                </div>
                @endforelse
            </div>

            <div class="card-footer text-center py-2">
                <a href="{{ route('admin.notifications.index') }}" class="small">
                    <i class="ti ti-arrow-right me-1"></i>Voir toutes les notifications
                </a>
            </div>
        </div>
    </div>
</div>
