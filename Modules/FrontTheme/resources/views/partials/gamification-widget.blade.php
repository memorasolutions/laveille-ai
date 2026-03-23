{{-- Composant réutilisable — widget gamification membre
     Usage: @include('fronttheme::partials.gamification-widget')
     Affiche le niveau, la barre de progression et les points de l'utilisateur connecté.
--}}
@auth
@php
    $user = auth()->user();
    $points = $user->reputation_points ?? 0;
    $level = $user->trust_level ?? 0;
    $levelInfo = ['name' => 'Membre', 'emoji' => '👤', 'next_threshold' => 15];

    if (class_exists(\Modules\Directory\Services\ReputationService::class)) {
        $levelInfo = \Modules\Directory\Services\ReputationService::getLevelInfo($level);
    }

    $nextThreshold = $levelInfo['next_threshold'];
    $isMax = empty($nextThreshold);
    $pct = $isMax ? 100 : min(100, max(0, ($points / $nextThreshold) * 100));
    $remaining = $isMax ? 0 : max(0, $nextThreshold - $points);
@endphp
<div style="display: flex; align-items: center; gap: 14px; background: #F0FDF4; border: 1px solid #DCFCE7; border-radius: 8px; padding: 10px 16px; margin-bottom: 16px;">
    <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; white-space: nowrap; flex-shrink: 0;">
        <span style="font-size: 1.2em;">{{ $levelInfo['emoji'] }}</span>
        <span style="font-size: 13px; color: #14532D;">{{ $levelInfo['name'] }}</span>
    </div>
    <div style="flex: 1; min-width: 80px;">
        <div style="height: 5px; width: 100%; background: #DCFCE7; border-radius: 99px; overflow: hidden;">
            <div style="height: 100%; width: {{ $pct }}%; background: #16A34A; border-radius: 99px; transition: width 0.5s;"></div>
        </div>
        @if(!$isMax)
        <div style="font-size: 10px; color: #6B7280; margin-top: 3px;">{{ __('Plus que') }} <strong>{{ $remaining }} pts</strong> {{ __('pour le prochain niveau') }}</div>
        @else
        <div style="font-size: 10px; color: #16A34A; margin-top: 3px; font-weight: 600;">{{ __('Niveau maximum !') }}</div>
        @endif
    </div>
    <div style="white-space: nowrap; flex-shrink: 0;">
        <span style="font-weight: 700; font-size: 14px; color: #15803D;">{{ $points }}</span>
        @if(!$isMax)<span style="color: #9CA3AF; font-size: 11px;">/ {{ $nextThreshold }}</span>@endif
        <span style="color: #9CA3AF; font-size: 11px;">pts</span>
    </div>
</div>
@endauth
