<div class="position-relative" x-data="{ open: false }" @click.outside="open = false" wire:poll.30s>
    <button
        @click="open = !open"
        class="position-relative d-flex align-items-center justify-content-center rounded-circle bg-light border-0"
        style="width:36px;height:36px;cursor:pointer;"
        type="button"
        aria-label="Notifications"
    >
        <i data-lucide="bell" class="text-muted" style="width:18px;height:18px;"></i>
        @if($unreadCount > 0)
            <span class="position-absolute top-0 end-0 d-flex align-items-center justify-content-center bg-danger text-white rounded-circle small fw-bold"
                  style="width:16px;height:16px;font-size:9px;">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak
         class="position-absolute bg-white border rounded-3 shadow-lg"
         style="right:0;top:100%;margin-top:0.5rem;width:320px;z-index:50;">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-primary bg-opacity-10 rounded-top-3">
            <h6 class="fw-semibold text-body mb-0">Notifications</h6>
            @if($unreadCount > 0)
                <button
                    wire:click="markAllRead"
                    type="button"
                    class="btn btn-link text-primary fw-medium p-0 text-decoration-none small"
                >
                    Tout lire
                </button>
            @endif
        </div>

        <div class="overflow-auto" style="max-height:320px;">
            @forelse($notifications as $notif)
                <div class="d-flex align-items-start gap-3 px-3 py-3 border-bottom">
                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary flex-shrink-0"
                          style="width:36px;height:36px;">
                        <i data-lucide="bell" style="width:16px;height:16px;"></i>
                    </span>
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="text-body fw-medium mb-0">
                            {{ $notif->data['message'] ?? class_basename($notif->type) }}
                        </p>
                        <span class="small text-muted">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                    <button
                        wire:click="markRead('{{ $notif->id }}')"
                        type="button"
                        class="d-inline-flex align-items-center justify-content-center btn btn-link text-muted p-0 border-0 flex-shrink-0"
                        style="width:20px;height:20px;"
                        title="Marquer comme lu"
                    >
                        <i data-lucide="x" style="width:14px;height:14px;"></i>
                    </button>
                </div>
            @empty
                <div class="px-3 py-5 text-center">
                    <i data-lucide="bell-off" class="text-muted d-block mb-2 mx-auto" style="width:32px;height:32px;"></i>
                    <p class="small text-muted mb-0">Aucune notification</p>
                </div>
            @endforelse
        </div>

        <div class="px-3 py-2 border-top text-center">
            <a href="{{ route('admin.notifications.index') }}" class="text-primary fw-medium small text-decoration-none">
                Voir toutes les notifications
            </a>
        </div>
    </div>
</div>
