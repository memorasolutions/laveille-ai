<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Notifications') . ' - ' . config('app.name'))

@section('user-content')

<div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; flex-wrap: wrap !important; margin-bottom: 20px;">
    <div>
        <h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Notifications') }}</h2>
        <p style="color: #777; margin: 0;">
            @if($unreadCount > 0)
                <strong style="color: #337ab7;">{{ $unreadCount }}</strong> {{ __('non lue(s)') }}
            @else
                {{ __('Tout est à jour.') }}
            @endif
        </p>
    </div>
    <div style="margin-top: 5px;">
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('user.notifications.markAllRead') }}" style="display: inline;">
                @csrf
                <a href="javascript:void(0)" onclick="this.closest('form').submit()" class="btn btn-default btn-sm" style="-webkit-appearance:none;text-decoration:none;">
                    {{ __('Tout marquer comme lu') }}
                </a>
            </form>
        @endif
        @if($notifications->total() > 0)
            <form method="POST" action="{{ route('user.notifications.destroyAll') }}" style="display: inline;"
                  data-confirm="{{ __('Supprimer toutes les notifications ?') }}">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
                <a href="javascript:void(0)" onclick="this.closest('form').submit()" class="btn btn-danger btn-sm" style="-webkit-appearance:none;text-decoration:none;">
                    {{ __('Tout supprimer') }}
                </a>
            </form>
        @endif
    </div>
</div>

<div class="panel panel-default">
    @if($notifications->isEmpty())
        <div class="panel-body" style="text-align: center; padding: 40px 20px; color: #999;">
            <div style="font-size: 48px; margin-bottom: 10px;">🔔</div>
            <p style="font-weight: 600;">{{ __('Aucune notification.') }}</p>
            <p><small>{{ __('Vous recevrez ici les alertes et messages importants.') }}</small></p>
        </div>
    @else
        <ul class="list-group" style="margin-bottom: 0;">
            @foreach($notifications as $notification)
            @php
                $emojiMap = [
                    'PasswordChangedNotification' => '🔒',
                    'SystemAlertNotification'     => '⚠️',
                    'SuggestionApproved'          => '✅',
                    'SuggestionRejected'          => '❌',
                    'VoteThresholdNotification'   => '⭐',
                    'NewResourceNotification'     => '📹',
                ];
                $type = class_basename($notification->type);
                $emoji = $emojiMap[$type] ?? '🔔';
            @endphp
            <li class="list-group-item"
                style="{{ is_null($notification->read_at) ? 'background: #f0f7ff; border-left: 3px solid var(--c-primary, #0B7285);' : '' }}">
                <div style="display: flex !important; align-items: flex-start !important;">
                    <span style="font-size: 18px; margin-right: 12px; margin-top: 2px; flex-shrink: 0;">{{ $emoji }}</span>
                    <div style="flex: 1 !important; min-width: 0;">
                        <p style="font-weight: 600; font-size: 14px; margin: 0 0 3px;">
                            {{ $notification->data['message'] ?? $notification->data['title'] ?? $type }}
                        </p>
                        @if(!empty($notification->data['body'] ?? $notification->data['details'] ?? null))
                            <p style="color: #777; font-size: 13px; margin: 0 0 3px;">
                                {{ $notification->data['body'] ?? $notification->data['details'] }}
                            </p>
                        @endif
                        <small style="color: #999;">
                            {{ $notification->created_at->diffForHumans() }}
                            @if(is_null($notification->read_at))
                                <span style="display: inline-block; width: 8px; height: 8px; background: #337ab7; border-radius: 50%; vertical-align: middle; margin-left: 5px;"></span>
                            @endif
                        </small>
                    </div>
                    <div style="flex-shrink: 0; margin-left: 10px; white-space: nowrap;">
                        @if(is_null($notification->read_at))
                            <form method="POST" action="{{ route('user.notifications.markRead', $notification->id) }}" style="display: inline;">
                                @csrf
                                <a href="javascript:void(0)" onclick="this.closest('form').submit()" class="btn btn-default btn-xs" title="{{ __('Marquer comme lue') }}" style="-webkit-appearance:none;text-decoration:none;">
                                    {{ __('Lu') }}
                                </a>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('user.notifications.destroy', $notification->id) }}" style="display: inline;"
                              data-confirm="{{ __('Supprimer cette notification ?') }}">
                            @csrf
                            <input type="hidden" name="_method" value="DELETE">
                            <a href="javascript:void(0)" onclick="this.closest('form').submit()" class="btn btn-danger btn-xs" title="{{ __('Supprimer') }}" style="-webkit-appearance:none;text-decoration:none;">
                                {{ __('X') }}
                            </a>
                        </form>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
        @if($notifications->hasPages())
            <div class="panel-footer" style="text-align: center;">
                {{ $notifications->links() }}
            </div>
        @endif
    @endif
</div>

@endsection
