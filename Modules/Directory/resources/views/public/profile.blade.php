<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $user->name . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $user->name])
@endsection

@php
    $nextThreshold = $levelInfo['next_threshold'];
    $isMax = empty($nextThreshold);
    $pct = $isMax ? 100 : min(100, max(0, ($user->reputation_points / $nextThreshold) * 100));
    $remaining = $isMax ? 0 : max(0, $nextThreshold - $user->reputation_points);
@endphp

@section('content')
<section class="section-padding" style="padding-top: 20px;">
<div class="container" style="max-width: 700px; margin: 0 auto;">

    {{-- Profile card --}}
    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 16px; padding: 32px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.04); margin-bottom: 24px;">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: #fff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; margin-bottom: 12px;">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <h1 style="font-family: var(--f-heading); font-size: 1.8rem; font-weight: 800; color: var(--c-dark); margin: 8px 0 4px;">{{ $user->name }}</h1>
        <p style="color: #9CA3AF; font-size: 14px; margin-bottom: 20px;">{{ __('Membre depuis') }} {{ $user->created_at->translatedFormat('F Y') }}</p>

        {{-- Level --}}
        <div style="display: inline-flex; align-items: center; gap: 10px; background: #F0FDF4; border: 1px solid #DCFCE7; border-radius: 50px; padding: 8px 20px; margin-bottom: 16px;">
            <span style="font-size: 1.5rem;">{{ $levelInfo['emoji'] }}</span>
            <span style="font-weight: 700; color: #14532D;">{{ $levelInfo['name'] }}</span>
            <span style="color: #16A34A; font-weight: 600;">{{ $user->reputation_points }} {{ __('pts') }}</span>
        </div>

        {{-- Progress bar --}}
        <div style="max-width: 400px; margin: 0 auto;">
            <div style="height: 8px; background: #F3F4F6; border-radius: 99px; overflow: hidden; margin-bottom: 8px;">
                <div style="height: 100%; width: {{ $pct }}%; background: #059669; border-radius: 99px; transition: width 0.5s;"></div>
            </div>
            @if(!$isMax)
                <p style="font-size: 12px; color: #6B7280;">{{ __('Plus que') }} <strong>{{ $remaining }}</strong> {{ __('pts pour') }} {{ \Modules\Directory\Services\ReputationService::getLevelInfo($user->trust_level + 1)['emoji'] ?? '' }} {{ \Modules\Directory\Services\ReputationService::getLevelInfo($user->trust_level + 1)['name'] ?? __('le prochain niveau') }}</p>
            @else
                <p style="font-size: 12px; color: #16A34A; font-weight: 600;">🏆 {{ __('Niveau maximum atteint !') }}</p>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="row" style="margin-bottom: 24px;">
        @foreach([['⭐', __('Avis'), $stats['reviews']], ['💬', __('Discussions'), $stats['discussions']], ['📚', __('Ressources'), $stats['resources']], ['❤️', __('Likes reçus'), $stats['likes_received']]] as $stat)
        <div class="col-xs-6 col-md-3" style="margin-bottom: 12px;">
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px; text-align: center;">
                <div style="font-size: 1.5rem; margin-bottom: 4px;">{{ $stat[0] }}</div>
                <div style="font-weight: 800; font-size: 20px; color: var(--c-dark);">{{ $stat[2] }}</div>
                <div style="font-size: 12px; color: #6B7280;">{{ $stat[1] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Badges --}}
    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 16px; padding: 24px; margin-bottom: 24px;">
        <h3 style="font-family: var(--f-heading); font-size: 1.2rem; font-weight: 700; color: var(--c-dark); margin: 0 0 16px;">🏅 {{ __('Badges') }}</h3>
        @if(empty($badges))
            <p style="color: #9CA3AF; text-align: center; padding: 20px;">{{ __('Pas encore de badges. Contribuez pour en gagner !') }}</p>
        @else
            <div class="row">
                @foreach($badges as $key)
                @php $badge = \Modules\Directory\Services\ReputationService::getBadgeInfo($key); @endphp
                <div class="col-md-4 col-xs-6" style="margin-bottom: 12px;">
                    <div style="background: #F9FAFB; border-radius: 10px; padding: 14px; text-align: center; height: 100%;">
                        <div style="font-size: 1.8rem;">{{ $badge['emoji'] }}</div>
                        <div style="font-weight: 700; font-size: 14px; color: var(--c-dark); margin: 4px 0;">{{ $badge['name'] }}</div>
                        <div style="font-size: 11px; color: #6B7280;">{{ $badge['description'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- CTA --}}
    <div class="text-center" style="margin-bottom: 40px;">
        <a href="{{ route('directory.index') }}" style="display: inline-block; background: var(--c-primary); color: #fff; padding: 12px 32px; border-radius: 0.5rem; font-weight: 700; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--c-primary-hover)'" onmouseout="this.style.background='var(--c-primary)'">
            🚀 {{ __('Contribuer au répertoire') }}
        </a>
    </div>
</div>
</section>
@endsection
