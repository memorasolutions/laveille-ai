@extends('auth::layouts.app')

@section('title', __('Notifications'))

@section('content')

<div class="d-flex flex-wrap align-items-center justify-content-between gap-12 mb-20">
    <div>
        <h1 class="fw-semibold mb-4">{{ __('Notifications') }}</h1>
        <p class="text-secondary-light mb-0">
            @if($unreadCount > 0)
                <span class="text-primary-600 fw-semibold">{{ $unreadCount }}</span> {{ __('non lue(s)') }}
            @else
                {{ __('Tout est à jour.') }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-8 flex-wrap">
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('user.notifications.markAllRead') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary-600 radius-8">
                <iconify-icon icon="solar:check-read-outline"></iconify-icon>
                {{ __('Tout marquer comme lu') }}
            </button>
        </form>
        @endif
        @if($notifications->total() > 0)
        <form method="POST" action="{{ route('user.notifications.destroyAll') }}"
              onsubmit="return confirm('{{ __('Supprimer toutes les notifications ?') }}')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger-600 radius-8">
                <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon>
                {{ __('Tout supprimer') }}
            </button>
        </form>
        @endif
    </div>
</div>

<div class="card">
    @if($notifications->isEmpty())
        <div class="card-body py-48 text-center text-secondary-light">
            <iconify-icon icon="solar:bell-off-outline" class="text-5xl mb-12 d-block"></iconify-icon>
            <p class="fw-medium mb-4">{{ __('Aucune notification.') }}</p>
            <p class="text-sm mb-0">{{ __('Vous recevrez ici les alertes et messages importants.') }}</p>
        </div>
    @else
        <ul class="list-unstyled mb-0">
            @foreach($notifications as $notification)
            @php
                $type = class_basename($notification->type);
                $iconMap = [
                    'PasswordChangedNotification' => ['solar:shield-check-outline', 'text-success-600', 'bg-success-100'],
                    'SystemAlertNotification'     => ['solar:danger-triangle-outline', 'text-warning-600', 'bg-warning-100'],
                ];
                [$icon, $iconColor, $iconBg] = $iconMap[$type] ?? ['solar:bell-outline', 'text-primary-600', 'bg-primary-100'];
            @endphp
            <li class="d-flex align-items-start gap-12 px-20 py-16 border-bottom
                {{ is_null($notification->read_at) ? 'bg-primary-50' : '' }}"
                style="border-color: var(--neutral-200) !important;">
                <div class="w-36-px h-36-px {{ $iconBg }} rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-2">
                    <iconify-icon icon="{{ $icon }}" class="{{ $iconColor }}"></iconify-icon>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <p class="fw-semibold text-sm mb-4">
                        {{ $notification->data['message'] ?? $notification->data['title'] ?? $type }}
                    </p>
                    @if(! empty($notification->data['body'] ?? $notification->data['details'] ?? null))
                        <p class="text-sm text-secondary-light mb-4">
                            {{ $notification->data['body'] ?? $notification->data['details'] }}
                        </p>
                    @endif
                    <p class="text-xs text-secondary-light mb-0">
                        {{ $notification->created_at->diffForHumans() }}
                        @if(is_null($notification->read_at))
                            <span class="ms-8 d-inline-block" style="width:8px;height:8px;background:#487FFF;border-radius:50%;vertical-align:middle;"></span>
                        @endif
                    </p>
                </div>
                <div class="flex-shrink-0 d-flex align-items-center gap-8">
                    @if(is_null($notification->read_at))
                    <form method="POST" action="{{ route('user.notifications.markRead', $notification->id) }}">
                        @csrf
                        <button type="submit" title="{{ __('Marquer comme lue') }}"
                                class="btn btn-sm btn-outline-secondary radius-8 px-8 py-4">
                            <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                        </button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('user.notifications.destroy', $notification->id) }}"
                          onsubmit="return confirm('{{ __('Supprimer cette notification ?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" title="{{ __('Supprimer') }}"
                                class="btn btn-sm btn-outline-danger radius-8 px-8 py-4">
                            <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                        </button>
                    </form>
                </div>
            </li>
            @endforeach
        </ul>
        @if($notifications->hasPages())
        <div class="card-body border-top">
            {{ $notifications->links() }}
        </div>
        @endif
    @endif
</div>

@endsection
