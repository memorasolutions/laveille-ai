<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Préférences de notification') . ' - ' . config('app.name'))

@section('user-content')

<div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; flex-wrap: wrap !important; margin-bottom: 20px;">
    <div>
        <h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Préférences de notification') }}</h2>
        <p style="color: #777; margin: 0;">{{ __('Choisissez comment vous souhaitez être notifié.') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="border-radius: 4px; margin-bottom: 20px;">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('user.notification-preferences.update') }}">
    @csrf
    @method('PUT')

    <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #e5e5e5;">
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #333;">{{ __('Type de notification') }}</th>
                    <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #333; width: 120px;">{{ __('Courriel') }}</th>
                    <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #333; width: 120px;">{{ __('Sur le site') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($types as $type => $config)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 12px 16px; color: #555;">{{ $config['label'] }}</td>
                        @foreach(['mail', 'database'] as $channel)
                            <td style="padding: 12px 16px; text-align: center;">
                                @if(in_array($channel, $config['channels']))
                                    @php
                                        $key = $type . '.' . $channel;
                                        $checked = isset($preferences[$key]) ? $preferences[$key] : true;
                                    @endphp
                                    <input type="checkbox"
                                           name="preferences[{{ $key }}]"
                                           value="1"
                                           {{ $checked ? 'checked' : '' }}
                                           style="width: 18px; height: 18px; cursor: pointer;">
                                @else
                                    <span style="color: #ccc;">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="padding: 8px 24px;">
            {{ __('Sauvegarder') }}
        </button>
        <a href="{{ route('user.notifications') }}" style="margin-left: 10px; color: #777;">{{ __('Retour aux notifications') }}</a>
    </div>
</form>

@endsection
