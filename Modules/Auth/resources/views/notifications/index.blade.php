@extends('auth::layouts.app')

@section('title', __('Notifications'))

@section('content')

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h1 class="fw-semibold mb-1">{{ __('Notifications') }}</h1>
        <p class="text-muted mb-0">
            @if($unreadCount > 0)
                <span class="text-primary fw-semibold">{{ $unreadCount }}</span> {{ __('non lue(s)') }}
            @else
                {{ __('Tout est à jour.') }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('user.notifications.markAllRead') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary rounded-2">
                <i data-lucide="check"></i>
                {{ __('Tout marquer comme lu') }}
            </button>
        </form>
        @endif
        @if($notifications->total() > 0)
        <form method="POST" action="{{ route('user.notifications.destroyAll') }}"
              onsubmit="return confirm('{{ __('Supprimer toutes les notifications ?') }}')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger rounded-2">
                <i data-lucide="trash-2"></i>
                {{ __('Tout supprimer') }}
            </button>
        </form>
        @endif
    </div>
</div>

<div class="card">
    @if($notifications->isEmpty())
        <div class="card-body py-5 text-center text-muted">
            <i data-lucide="bell-off" class="d-block mx-auto mb-2" style="width:48px;height:48px;"></i>
            <p class="fw-medium mb-1">{{ __('Aucune notification.') }}</p>
            <p class="text-sm mb-0">{{ __('Vous recevrez ici les alertes et messages importants.') }}</p>
        </div>
    @else
        <ul class="list-unstyled mb-0">
            @foreach($notifications as $notification)
            @php
                $type = class_basename($notification->type);
                $iconMap = [
                    'PasswordChangedNotification' => ['shield-check', 'text-success', 'bg-success bg-opacity-10'],
                    'SystemAlertNotification'     => ['alert-triangle', 'text-warning', 'bg-warning bg-opacity-10'],
                ];
                [$icon, $iconColor, $iconBg] = $iconMap[$type] ?? ['bell', 'text-primary', 'bg-primary bg-opacity-10'];
            @endphp
            <li class="d-flex align-items-start gap-2 px-3 py-3 border-bottom
                {{ is_null($notification->read_at) ? 'bg-primary bg-opacity-10' : '' }}"
                style="border-color: var(--bs-border-color) !important;">
                <div class="rounded-circle {{ $iconBg }} d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width:36px;height:36px;">
                    <i data-lucide="{{ $icon }}" class="{{ $iconColor }}"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <p class="fw-semibold text-sm mb-1">
                        {{ $notification->data['message'] ?? $notification->data['title'] ?? $type }}
                    </p>
                    @if(! empty($notification->data['body'] ?? $notification->data['details'] ?? null))
                        <p class="text-sm text-muted mb-1">
                            {{ $notification->data['body'] ?? $notification->data['details'] }}
                        </p>
                    @endif
                    <p class="small text-muted mb-0">
                        {{ $notification->created_at->diffForHumans() }}
                        @if(is_null($notification->read_at))
                            <span class="ms-2 d-inline-block" style="width:8px;height:8px;background:#487FFF;border-radius:50%;vertical-align:middle;"></span>
                        @endif
                    </p>
                </div>
                <div class="flex-shrink-0 d-flex align-items-center gap-2">
                    @if(is_null($notification->read_at))
                    <form method="POST" action="{{ route('user.notifications.markRead', $notification->id) }}">
                        @csrf
                        <button type="submit" title="{{ __('Marquer comme lue') }}"
                                class="btn btn-sm btn-outline-secondary rounded-2 px-2 py-1">
                            <i data-lucide="check-circle"></i>
                        </button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('user.notifications.destroy', $notification->id) }}"
                          onsubmit="return confirm('{{ __('Supprimer cette notification ?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" title="{{ __('Supprimer') }}"
                                class="btn btn-sm btn-outline-danger rounded-2 px-2 py-1">
                            <i data-lucide="x-circle"></i>
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
